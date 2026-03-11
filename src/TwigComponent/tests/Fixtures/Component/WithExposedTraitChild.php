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

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * Child component that inherits from WithExposedTraitParent without
 * redeclaring HasExposedVariablesTrait itself.
 *
 * Bug: PHP's ReflectionClass::getProperties() called on this class does not
 * return private properties from traits used in parent classes.
 * The #[ExposeInTemplate] property from HasExposedVariablesTrait must
 * still be exposed in this component's template.
 */
#[AsTwigComponent]
class WithExposedTraitChild extends WithExposedTraitParent
{
    // Intentionally does not redeclare: use HasExposedVariablesTrait;
    // The inherited trait property must still be discoverable.
}
