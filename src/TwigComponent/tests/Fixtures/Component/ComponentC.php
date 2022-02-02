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
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsTwigComponent('component_c')]
final class ComponentC
{
    public $propA;
    public $propB;
    public $propC;

    public function mount($propA, $propB = null, $propC = 'default')
    {
        $this->propA = $propA;
        $this->propB = $propB;
        $this->propC = $propC;
    }
}
