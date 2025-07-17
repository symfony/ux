<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Map\Icon\Icon;
use Symfony\UX\Map\Icon\SvgIcon;
use Symfony\UX\Map\Icon\UrlIcon;
use Symfony\UX\Map\Icon\UxIcon;

class IconTest extends TestCase
{
    public static function provideIcons(): iterable
    {
        yield 'url' => [
            'icon' => Icon::url('https://image.png')->width(12)->height(12),
            'expectedInstance' => UrlIcon::class,
            'expectedToArray' => ['type' => 'url', 'width' => 12, 'height' => 12, 'url' => 'https://image.png'],
        ];
        yield 'svg' => [
            'icon' => Icon::svg('<svg></svg>'),
            'expectedInstance' => SvgIcon::class,
            'expectedToArray' => ['type' => 'svg', 'width' => 24, 'height' => 24, 'html' => '<svg></svg>'],
        ];
        yield 'ux' => [
            'icon' => Icon::ux('bi:heart')->width(48)->height(48),
            'expectedInstance' => UxIcon::class,
            'expectedToArray' => ['type' => 'ux-icon', 'width' => 48, 'height' => 48, 'name' => 'bi:heart'],
        ];
    }

    /**
     * @dataProvider provideIcons
     *
     * @param class-string<Icon> $expectedInstance
     */
    public function testIconConstruction(Icon $icon, string $expectedInstance, array $expectedToArray): void
    {
        self::assertInstanceOf($expectedInstance, $icon);
    }

    /**
     * @dataProvider provideIcons
     */
    public function testToArray(Icon $icon, string $expectedInstance, array $expectedToArray): void
    {
        self::assertSame($expectedToArray, $icon->toArray());
    }

    /**
     * @dataProvider provideIcons
     */
    public function testFromArray(Icon $icon, string $expectedInstance, array $expectedToArray): void
    {
        self::assertEquals($icon, Icon::fromArray($expectedToArray));
    }

    public static function dataProviderForTestSvgIconCustomizationMethodsCanNotBeCalled(): iterable
    {
        $refl = new \ReflectionClass(SvgIcon::class);
        $customizationMethods = array_diff(
            array_map(
                fn (\ReflectionMethod $method) => $method->name,
                array_filter($refl->getMethods(\ReflectionMethod::IS_PUBLIC), fn (\ReflectionMethod $method) => SvgIcon::class === $method->getDeclaringClass()->getName())
            ),
            ['toArray', 'fromArray']
        );

        foreach ($customizationMethods as $method) {
            if (\in_array($method, ['width', 'height'], true)) {
                yield $method => [$method, 12];
            } elseif (\in_array($method, $customizationMethods, true)) {
                throw new \LogicException(\sprintf('The "%s" method is not supposed to be called on the SvgIcon, please modify the test provider.', $method));
            }
        }
    }

    /**
     * @dataProvider dataProviderForTestSvgIconCustomizationMethodsCanNotBeCalled
     */
    public function testSvgIconCustomizationMethodsCanNotBeCalled(string $method, mixed ...$args): void
    {
        $this->expectException(\LogicException::class);
        if (\in_array($method, ['width', 'height'], true)) {
            $this->expectExceptionMessage(\sprintf('Unable to configure the SvgIcon %s, please configure it in the HTML with the "%s" attribute on the root element instead.', $method, $method));
        }

        $icon = Icon::svg('<svg></svg>');
        $icon->{$method}(...$args);
    }
}
