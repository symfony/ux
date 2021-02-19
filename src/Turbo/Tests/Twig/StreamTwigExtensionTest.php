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
class StreamTwigExtensionTest extends TestCase
{
    public function provideStreamFrom()
    {
        yield 'without-attrs' => [
            'id' => 'mytopic',
            'attr' => [],
            'expected' => '<div data-controller="symfony--ux-turbo--mercure symfony--ux-turbo--core" data-symfony--ux-turbo--mercure-hub-value="http&#x3A;&#x2F;&#x2F;localhost&#x2F;.well-know&#x2F;mecure" data-symfony--ux-turbo--mercure-topic-value="mytopic"></div>',
        ];

        yield 'with-attrs' => [
            'id' => 'mytopic',
            'attr' => ['class' => 'myframe', 'title' => 'mytitle'],
            'expected' => '<div data-controller="symfony--ux-turbo--mercure symfony--ux-turbo--core" data-symfony--ux-turbo--mercure-hub-value="http&#x3A;&#x2F;&#x2F;localhost&#x2F;.well-know&#x2F;mecure" data-symfony--ux-turbo--mercure-topic-value="mytopic" class="myframe" title="mytitle"></div>',
        ];
    }

    /**
     * @dataProvider provideStreamFrom
     */
    public function testStreamFrom(string $id, array $attrs, string $expected)
    {
        $kernel = new FullAppKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer()->get('test.service_container');

        $this->assertSame(
            $expected,
            $container->get('turbo.twig.stream_extension')->streamFrom($container->get('twig'), $id, $attrs)
        );
    }
}
