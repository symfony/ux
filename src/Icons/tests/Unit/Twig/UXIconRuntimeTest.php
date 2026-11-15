<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\IconRendererInterface;
use Symfony\UX\Icons\Twig\UXIconRuntime;
use Twig\Extra\Html\HtmlExtension;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class UXIconRuntimeTest extends TestCase
{
    public function testRenderIconIgnoreNotFound(): void
    {
        $renderer = $this->createMock(IconRendererInterface::class);
        $renderer->method('renderIcon')
            ->willThrowException(new IconNotFoundException('Icon "foo" not found.'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('warning')
            ->with('Icon "foo" not found.');

        $runtime = new UXIconRuntime($renderer, true, $logger);
        $this->assertEquals('', $runtime->renderIcon('not_found'));

        $runtime = new UXIconRuntime($renderer, false);
        $this->expectException(IconNotFoundException::class);
        $runtime->renderIcon('not_found');
    }

    public function testRenderIconResolvesAttributeValues(): void
    {
        $renderer = $this->createMock(IconRendererInterface::class);
        $renderer->expects($this->once())
            ->method('renderIcon')
            ->with('foo', self::identicalTo([
                'data-action' => 'click->menu#toggle',
                'class' => 'a b',
                'aria-expanded' => 'false',
                'data-open' => 'true',
                'hidden' => '',
                'title' => 'User',
                'gone' => false,
            ]))
            ->willReturn('<svg></svg>');

        $runtime = new UXIconRuntime($renderer);

        $this->assertSame('<svg></svg>', $runtime->renderIcon('foo', [
            'data-action' => HtmlExtension::htmlAttrType('click->menu#toggle'),
            'class' => ['a', 'b'],
            'aria-expanded' => false,
            'data-open' => true,
            'hidden' => true,
            'title' => new class implements \Stringable {
                public function __toString(): string
                {
                    return 'User';
                }
            },
            'gone' => null,
        ]));
    }

    #[DataProvider('provideStringAttributes')]
    public function testStringAttributesReachTheRendererExactlyAsHtmlAttrValueWouldLeaveThem(string $name, string $value): void
    {
        $expected = HtmlExtension::htmlAttrValue($name, $value) ?? false;

        $renderer = $this->createMock(IconRendererInterface::class);
        $renderer->expects($this->once())
            ->method('renderIcon')
            ->with('foo', self::identicalTo([$name => $expected]))
            ->willReturn('<svg></svg>');

        new UXIconRuntime($renderer)->renderIcon('foo', [$name => $value]);
    }

    public static function provideStringAttributes(): iterable
    {
        yield 'class' => ['class', 'size-4 text-muted'];
        yield 'empty string' => ['class', ''];
        yield 'aria- with "true"' => ['aria-hidden', 'true'];
        yield 'aria- with "false"' => ['aria-expanded', 'false'];
        yield 'data- with a word' => ['data-icon', 'inline-start'];
        yield 'data- with JSON' => ['data-config', '{"a":1}'];
        yield 'data- with "true"' => ['data-open', 'true'];
        yield 'style' => ['style', 'color: red; width: 1em'];
        yield 'numeric string' => ['width', '24'];
        yield 'zero' => ['width', '0'];
        yield 'quotes and entities' => ['title', 'a "b" & <c>'];
        yield 'utf-8' => ['title', 'Étoile ★'];
    }
}
