<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Notify\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Notify\Tests\Kernel\EmptyAppKernel;
use Symfony\UX\Notify\Tests\Kernel\FrameworkAppKernel;
use Symfony\UX\Notify\Tests\Kernel\TwigAppKernel;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @internal
 */
class NotifyBundleTest extends TestCase
{
    /**
     * @dataProvider provideKernels
     */
    public function testBootKernel(Kernel $kernel)
    {
        $kernel->boot();
        $this->assertArrayHasKey('NotifyBundle', $kernel->getBundles());
    }

    /**
     * @return iterable<Kernel>
     */
    public function provideKernels(): iterable
    {
        yield 'empty' => [new EmptyAppKernel('test', true)];
        yield 'framework' => [new FrameworkAppKernel('test', true)];
        yield 'twig' => [new TwigAppKernel('test', true)];
    }
}
