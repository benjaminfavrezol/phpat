<?php declare(strict_types=1);

namespace PHPat\Selector;

use PHPStan\Reflection\ClassReflection;

final class IsAnonymous implements SelectorInterface
{
    public function getName(): string
    {
        return '-anonymous classes-';
    }

    public function matches(ClassReflection $classReflection): bool
    {
        return $classReflection->isAnonymous();
    }
}
