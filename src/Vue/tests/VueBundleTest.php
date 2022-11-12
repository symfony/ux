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
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Vue\Tests\Kernel\FrameworkAppKernel;
use Symfony\UX\Vue\Tests\Kernel\TwigAppKernel;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Thibault RICHARD <thibault.richard62@gmail.com>
 *
 * @internal
 */
class VueBundleTest extends TestCase
{
    public function provideKernels()
    {
        yield 'framework' => [new FrameworkAppKernel('test', true)];
        yield 'twig' => [new TwigAppKernel('test', true)];
    }

    /**
     * @dataProvider provideKernels
     */
    public function testBootKernel(Kernel $kernel)
    {
        $kernel->boot();
        $this->assertArrayHasKey('VueBundle', $kernel->getBundles());
    }
}
