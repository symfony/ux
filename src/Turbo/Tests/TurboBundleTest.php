<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Turbo\Streams\MercureStreamAdapter;
use Symfony\UX\Turbo\Tests\Kernel\EmptyAppKernel;
use Symfony\UX\Turbo\Tests\Kernel\FrameworkAppKernel;
use Symfony\UX\Turbo\Tests\Kernel\FullAppKernel;
use Symfony\UX\Turbo\Tests\Kernel\TwigAppKernel;
use Symfony\UX\Turbo\Twig\FrameTwigExtension;
use Symfony\UX\Turbo\Twig\StreamTwigExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class TurboBundleTest extends TestCase
{
    public function provideKernels()
    {
        yield 'empty' => [new EmptyAppKernel('test', true)];
        yield 'framework' => [new FrameworkAppKernel('test', true)];
        yield 'twig' => [new TwigAppKernel('test', true)];
    }

    /**
     * @dataProvider provideKernels
     */
    public function testBootKernel(Kernel $kernel)
    {
        $kernel->boot();
        $this->assertArrayHasKey('TurboBundle', $kernel->getBundles());
    }

    public function testFull()
    {
        $kernel = new FullAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $this->assertArrayHasKey('TurboBundle', $kernel->getBundles());

        // Check Turbo Streams adapter was wired
        $adapter = $container->get('turbo.streams.adapter');
        $this->assertInstanceOf(MercureStreamAdapter::class, $adapter);

        // Check Twig extensions were loaded
        $twig = $container->get('twig');
        $this->assertNotNull($twig->getExtension(FrameTwigExtension::class));
        $this->assertNotNull($twig->getExtension(StreamTwigExtension::class));
    }
}
