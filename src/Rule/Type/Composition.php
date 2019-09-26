<?php declare(strict_types=1);

namespace PhpAT\Rule\Type;

use PhpAT\File\FileFinder;
use PhpAT\Parser\ClassName;
use PhpAT\Parser\Collector\ClassNameCollector;
use PhpAT\Parser\Collector\InterfaceCollector;
use PhpAT\Rule\Event\StatementNotValidEvent;
use PhpParser\NodeTraverserInterface;
use PhpParser\Parser;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Composition implements RuleType
{
    private $traverser;
    private $finder;
    private $parser;
    /** @var ClassName */
    private $parsedClassClassName;
    /** @var ClassName[] */
    private $parsedClassInterfaces;
    private $eventDispatcher;

    public function __construct(
        FileFinder $finder,
        Parser $parser,
        NodeTraverserInterface $traverser,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->finder = $finder;
        $this->parser = $parser;
        $this->traverser = $traverser;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function validate(
        array $parsedClass,
        array $destinationFiles,
        array $destinationExcludedFiles,
        bool $inverse = false
    ): void {
        $this->extractParsedClassInfo($parsedClass);

        $filesFound = [];
        foreach ($destinationFiles as $file) {
            $found = $this->finder->findFiles($file, $destinationExcludedFiles);
            foreach ($found as $f) {
                $filesFound[] = $f;
            }
        }

        $classNameCollector = new ClassNameCollector();
        $this->traverser->addVisitor($classNameCollector);
        /** @var \SplFileInfo $file */
        foreach ($filesFound as $file) {
            $parsed = $this->parser->parse(file_get_contents($file->getPathname()));
            $this->traverser->traverse($parsed);
        }
        $this->traverser->removeVisitor($classNameCollector);

        //TODO: Change to FatalErrorEvent (could not find any class in the test)
        if (empty($classNameCollector->getResult())) {
            return;
        }

        /** @var ClassName $className */
        foreach ($classNameCollector->getResult() as $className) {
            $result = empty($this->parsedClassInterfaces)
                ? false
                : in_array($className->getFQDN(), $this->parsedClassInterfaces);

            $this->dispatchResult($result, $inverse, $this->parsedClassClassName, $className);
        }
    }

    private function extractParsedClassInfo(array $parsedClass): void
    {
        $interfaceCollector = new InterfaceCollector();
        $classNameCollector = new ClassNameCollector();

        $this->traverser->addVisitor($interfaceCollector);
        $this->traverser->addVisitor($classNameCollector);
        $this->traverser->traverse($parsedClass);
        $this->traverser->removeVisitor($interfaceCollector);

        $this->parsedClassClassName = $classNameCollector->getResult()[0];
        $this->parsedClassInterfaces = $interfaceCollector->getResult();

        /** @var ClassName $v */
        foreach ($this->parsedClassInterfaces as $k => $v) {
            if (empty($v->getNamespace())) {
                $className = new ClassName($this->parsedClassClassName->getNamespace(), $v->getName());
                $this->parsedClassInterfaces[$k] = $className->getFQDN();
            } else {
                $this->parsedClassInterfaces[$k] = $v->getFQDN();
            }
        }
    }

    private function dispatchResult(bool $result, bool $inverse, ClassName $className, ClassName $interfaceName): void
    {
        if (false === ($result xor $inverse)) {
            $error = $inverse ? ' implements ' : ' does not implement ';
            $message = $className->getFQDN() . $error . $interfaceName->getFQDN();
            $this->eventDispatcher->dispatch(new StatementNotValidEvent($message));
        } else {
            echo '-';
        }
    }
}