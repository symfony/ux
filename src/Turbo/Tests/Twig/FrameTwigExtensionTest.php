<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Turbo\Tests\Kernel\FullAppKernel;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class FrameTwigExtensionTest extends TestCase
{
    public function provideStartFrame()
    {
        yield 'without-attrs' => [
            'id' => 'frame1',
            'attr' => [],
            'expected' => '<turbo-frame id="frame1">',
        ];

        yield 'with-attrs' => [
            'id' => 'frame1',
            'attr' => ['class' => 'myframe', 'title' => 'mytitle'],
            'expected' => '<turbo-frame id="frame1" class="myframe" title="mytitle">',
        ];
    }

    /**
     * @dataProvider provideStartFrame
     */
    public function testStartFrame(string $id, array $attrs, string $expected)
    {
        $container = $this->bootFullKernel();

        $this->assertSame(
            $expected,
            $container->get('turbo.twig.frame_extension')->startFrame($container->get('twig'), $id, $attrs)
        );
    }

    public function testEndFrame()
    {
        $this->assertSame('</turbo-frame>', $this->bootFullKernel()->get('turbo.twig.frame_extension')->endFrame());
    }

    private function bootFullKernel()
    {
        $kernel = new FullAppKernel('test', true);
        $kernel->boot();

        return $kernel->getContainer()->get('test.service_container');
    }
}
