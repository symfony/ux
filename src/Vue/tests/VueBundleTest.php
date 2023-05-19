<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Vue\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Vue\Tests\Kernel\TwigAppKernel;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thibault RICHARD <thibault.richard62@gmail.com>
 *
 * @internal
 */
class VueBundleTest extends TestCase
{
    public function testBootKernel()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $this->assertArrayHasKey('VueBundle', $kernel->getBundles());
    }
}
