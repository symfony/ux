<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixture\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('component_b', template: 'components/custom1.html.twig')]
final class ComponentB
{
}
