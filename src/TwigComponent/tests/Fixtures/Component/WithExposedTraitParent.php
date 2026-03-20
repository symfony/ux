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
 * Parent component that directly uses HasExposedVariablesTrait.
 * The private #[ExposeInTemplate] property from the trait should be
 * available in this component's template.
 */
#[AsTwigComponent]
class WithExposedTraitParent
{
    use HasExposedVariablesTrait;
}
