<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Threejs\Tests\Kernel\TwigAppKernel;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 * @internal
 */
class ThreejsBundleTest extends TestCase
{
    public function testBootKernel()
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();
        $this->assertArrayHasKey('ThreejsBundle', $kernel->getBundles());
    }
}
