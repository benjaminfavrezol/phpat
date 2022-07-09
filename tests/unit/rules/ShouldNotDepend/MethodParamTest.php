<?php

declare(strict_types=1);

namespace Tests\PHPat\unit\rules\ShouldNotDepend;

use PHPat\Rule\Assertion\ShouldNotDepend\MethodParamRule;
use PHPat\Rule\Assertion\ShouldNotDepend\ShouldNotDepend;
use PHPat\Selector\Classname;
use PHPat\Statement\Builder\StatementBuilderFactory;
use PHPStan\PhpDoc\ResolvedPhpDocBlock;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPStan\Type\FileTypeMapper;
use Tests\PHPat\fixtures\SuperClass;
use Tests\PHPat\fixtures\Simple\SimpleClass;
use Tests\PHPat\fixtures\Simple\SimpleInterface;
use Tests\PHPat\unit\FakeTestParser;

/**
 * @extends RuleTestCase<MethodParamRule>
 */
class MethodParamTest extends RuleTestCase
{
    public function testRule(): void
    {
        $this->analyse(['tests/fixtures/SuperClass.php'], [
            [sprintf('%s should not depend on %s', SuperClass::class, SimpleInterface::class), 19],
        ]);
    }

    protected function getRule(): Rule
    {
        $testParser = FakeTestParser::create(
            ShouldNotDepend::class,
            [new Classname(SuperClass::class)],
            [new Classname(SimpleInterface::class)]
        );

        $fileTypeMapper = $this->createMock(FileTypeMapper::class);
        $fileTypeMapper->method('getResolvedPhpDoc')->willReturn(ResolvedPhpDocBlock::createEmpty());

        return new MethodParamRule(
            new StatementBuilderFactory($testParser),
            $this->createReflectionProvider(),
            $fileTypeMapper
        );
    }
}