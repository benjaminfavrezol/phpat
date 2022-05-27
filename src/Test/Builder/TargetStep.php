<?php

namespace PHPat\Test\Builder;

use PHPat\Selector\SelectorInterface;
use PHPat\Test\Rule;

class TargetStep
{
    private Rule $rule;

    public function __construct(Rule $rule)
    {
        $this->rule = $rule;
    }

    public function classes(SelectorInterface ...$selectors): TargetExcludeOrAssertionStep
    {
        $this->rule->targets = [...$selectors];

        return new TargetExcludeOrAssertionStep($this->rule);
    }
}
