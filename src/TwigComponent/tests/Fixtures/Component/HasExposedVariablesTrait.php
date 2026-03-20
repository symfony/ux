<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

/**
 * Minimal reproduction of the private-trait-property reflection gap.
 *
 * Private properties declared in a trait are only discoverable via reflection
 * on the class that directly uses the trait.  When a *child* class inherits
 * from a parent that uses this trait, PHP's ReflectionClass::getProperties()
 * called on the child does not return these private members.
 */
trait HasExposedVariablesTrait
{
    /** Exposed via a custom template-variable name and an explicit getter. */
    #[ExposeInTemplate(name: 'exposed_from_trait', getter: 'getExposedValue()')]
    private string $traitExposedProp = 'trait-value';

    public function getExposedValue(): string
    {
        return $this->traitExposedProp;
    }
}
