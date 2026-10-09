<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Router\Tests\Kernel\EmptyAppKernel;
use Symfony\UX\Router\Tests\Kernel\FrameworkAppKernel;

class UXRouterBundleTest extends TestCase
{
    public static function provideKernels()
    {
        yield 'empty' => [new EmptyAppKernel('test', true)];
        yield 'framework' => [new FrameworkAppKernel('test', true)];
    }

    #[DataProvider('provideKernels')]
    public function testBootKernel(Kernel $kernel): void
    {
        $kernel->boot();
        $this->assertArrayHasKey('UXRouterBundle', $kernel->getBundles());
    }
}
