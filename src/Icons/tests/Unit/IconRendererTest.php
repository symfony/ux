<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconRegistryInterface;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\Tests\Util\InMemoryIconRegistry;

/**
 * @author Simon André <smn.andre@gmail.com>
 */
class IconRendererTest extends TestCase
{
    public function testRenderIcon(): void
    {
        $registry = $this->createRegistry([
            'user' => '<circle ',
        ]);
        $iconRenderer = new IconRenderer($registry);

        $icon = $iconRenderer->renderIcon('user');

        $this->assertStringContainsString('<svg', $icon);
        $this->assertStringContainsString('<circle', $icon);
    }

    public function testRenderIconThrowsExceptionWhenIconNotFound(): void
    {
        $registry = $this->createRegistry([]);
        $iconRenderer = new IconRenderer($registry);

        $this->expectException(IconNotFoundException::class);

        $iconRenderer->renderIcon('foo');
    }

    public function testRenderIconThrowsExceptionWhenAttributesAreInvalid(): void
    {
        $registry = $this->createRegistry(['foo' => '<path d="M0 0L12 12"/>']);
        $iconRenderer = new IconRenderer($registry);

        $this->expectException(\InvalidArgumentException::class);

        $iconRenderer->renderIcon('foo', [1, 2, null]);
    }

    public function testRenderIconWithAttributes(): void
    {
        $registry = $this->createRegistry([
            'foo' => '<path d="M0 0L12 12"/>',
        ]);
        $iconRenderer = new IconRenderer($registry);
        $attributes = ['viewBox' => '0 0 24 24', 'class' => 'icon', 'id' => 'FooBar'];

        $svg = $iconRenderer->renderIcon('foo', $attributes);

        $this->assertSame('<svg viewBox="0 0 24 24" class="icon" id="FooBar" aria-hidden="true"><path d="M0 0L12 12"/></svg>', $svg);
    }

    public function testRenderIconWithDefaultAttributes(): void
    {
        $registry = $this->createRegistry([
            'foo' => '<path d="M0 0L12 12"/>',
        ]);
        $iconRenderer = new IconRenderer($registry, ['viewBox' => '0 0 24 24', 'class' => 'icon']);

        $svg = $iconRenderer->renderIcon('foo');

        $this->assertSame('<svg viewBox="0 0 24 24" class="icon" aria-hidden="true"><path d="M0 0L12 12"/></svg>', $svg);
    }

    /**
     * @dataProvider provideRenderIconWithAttributeCascadeCases
     */
    public function testRenderIconWithAttributeCascade(
        array $iconAttributes,
        array $defaultAttributes = [],
        array $iconSetAttributes = [],
        array $renderAttributes = [],
        string $expectedTag = '',
    ): void {
        $registry = $this->createRegistry([
            'ux:icon' => ['', $iconAttributes],
        ]);
        $iconRenderer = new IconRenderer($registry, $defaultAttributes, [], ['ux' => $iconSetAttributes]);

        $svg = $iconRenderer->renderIcon('ux:icon', $renderAttributes);
        $svg = str_replace(' aria-hidden="true"', '', $svg);
        $this->assertStringStartsWith($expectedTag, $svg);
    }

    /**
     * @return iterable<array{
     *     array<string, string|bool>,
     *     array<string, string|bool>,
     *     array<string, string|bool>,
     *     array<string, string|bool>,
     *     string,
     * }>
     */
    public static function provideRenderIconWithAttributeCascadeCases(): iterable
    {
        yield 'no_attributes' => [
            [],
            [],
            [],
            [],
            '<svg>',
        ];
        yield 'icon_attributes_are_used' => [
            ['ico' => 'ICO'],
            [],
            [],
            [],
            '<svg ico="ICO">',
        ];
        yield 'default_attributes_are_used' => [
            [],
            ['def' => 'DEF'],
            [],
            [],
            '<svg def="DEF">',
        ];
        yield 'default_attributes_overwrite_icon_attributes' => [
            ['ico' => 'ICO'],
            ['ico' => 'DEF'],
            [],
            [],
            '<svg ico="DEF">',
        ];
        yield 'default_attributes_removes_icon_attributes' => [
            ['ico' => 'ICO'],
            ['ico' => false],
            [],
            [],
            '<svg>',
        ];
        yield 'default_attributes_are_merged_with_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            ['ico' => 'DEF', 'bar' => 'DEF'],
            [],
            [],
            '<svg ico="DEF" foo="ICO" bar="DEF">',
        ];
        yield 'icon_set_attributes_are_used' => [
            [],
            [],
            ['ico' => 'SET'],
            [],
            '<svg ico="SET">',
        ];
        yield 'icon_set_attributes_overwrite_icon_attributes' => [
            ['ico' => 'ICO'],
            [],
            ['ico' => 'SET'],
            [],
            '<svg ico="SET">',
        ];
        yield 'icon_set_attributes_overwrite_default_attributes' => [
            [],
            ['ico' => 'DEF'],
            ['ico' => 'SET'],
            [],
            '<svg ico="SET">',
        ];
        yield 'icon_set_attributes_are_merged_with_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            [],
            ['ico' => 'SET', 'bar' => 'SET'],
            [],
            '<svg ico="SET" foo="ICO" bar="SET">',
        ];
        yield 'icon_set_attributes_are_merged_with_default_attributes' => [
            [],
            ['ico' => 'DEF', 'foo' => 'DEF'],
            ['ico' => 'SET', 'bar' => 'SET'],
            [],
            '<svg ico="SET" foo="DEF" bar="SET">',
        ];
        yield 'icon_set_attributes_are_merged_with_default_attributes_and_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            ['ico' => 'DEF', 'bar' => 'DEF'],
            ['ico' => 'SET', 'baz' => 'SET'],
            [],
            '<svg ico="SET" foo="ICO" bar="DEF" baz="SET">',
        ];
        yield 'render_attributes_are_used' => [
            [],
            [],
            [],
            ['ren' => 'REN'],
            '<svg ren="REN">',
        ];
        yield 'render_attributes_overwrite_icon_attributes' => [
            ['ico' => 'ICO'],
            [],
            [],
            ['ico' => 'REN'],
            '<svg ico="REN">',
        ];
        yield 'render_attributes_overwrite_default_attributes' => [
            [],
            ['ico' => 'DEF'],
            [],
            ['ico' => 'REN'],
            '<svg ico="REN">',
        ];
        yield 'render_attributes_overwrite_icon_set_attributes' => [
            [],
            [],
            ['ico' => 'SET'],
            ['ico' => 'REN'],
            '<svg ico="REN">',
        ];
        yield 'render_attributes_are_merged_with_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            [],
            [],
            ['ico' => 'REN', 'bar' => 'REN'],
            '<svg ico="REN" foo="ICO" bar="REN">',
        ];
        yield 'render_attributes_are_merged_with_default_attributes' => [
            [],
            ['ico' => 'DEF', 'foo' => 'DEF'],
            [],
            ['ico' => 'REN', 'bar' => 'REN'],
            '<svg ico="REN" foo="DEF" bar="REN">',
        ];
        yield 'render_attributes_are_merged_with_icon_set_attributes' => [
            [],
            [],
            ['ico' => 'SET', 'foo' => 'SET'],
            ['ico' => 'REN', 'bar' => 'REN'],
            '<svg ico="REN" foo="SET" bar="REN">',
        ];
        yield 'render_attributes_are_merged_with_default_attributes_and_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            ['ico' => 'DEF', 'bar' => 'DEF'],
            [],
            ['ico' => 'REN', 'baz' => 'REN'],
            '<svg ico="REN" foo="ICO" bar="DEF" baz="REN">',
        ];
        yield 'render_attributes_are_merged_with_icon_set_attributes_and_default_attributes' => [
            [],
            ['ico' => 'DEF', 'foo' => 'DEF'],
            ['ico' => 'SET', 'bar' => 'SET'],
            ['ico' => 'REN', 'baz' => 'REN'],
            '<svg ico="REN" foo="DEF" bar="SET" baz="REN">',
        ];
        yield 'render_attributes_are_merged_with_icon_set_attributes_and_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            [],
            ['ico' => 'SET', 'bar' => 'SET'],
            ['ico' => 'REN', 'baz' => 'REN'],
            '<svg ico="REN" foo="ICO" bar="SET" baz="REN">',
        ];
        yield 'render_attributes_are_merged_with_icon_set_attributes_and_default_attributes_and_icon_attributes' => [
            ['ico' => 'ICO', 'foo' => 'ICO'],
            ['ico' => 'DEF', 'bar' => 'DEF'],
            ['ico' => 'SET', 'baz' => 'SET'],
            ['ico' => 'REN', 'qux' => 'REN'],
            '<svg ico="REN" foo="ICO" bar="DEF" baz="SET" qux="REN">',
        ];
    }

    /**
     * @dataProvider provideAriaHiddenCases
     *
     * @param string|array{string, array<string, string|bool>} $icon
     * @param array<string, string|bool>                       $attributes
     */
    public function testRenderIconWithAutoAriaHidden(string|array $icon, array $attributes, string $expectedSvg): void
    {
        $icon = (array) $icon;
        $registry = $this->createRegistry([
            'foo' => $icon,
        ]);
        $iconRenderer = new IconRenderer($registry);

        $svg = $iconRenderer->renderIcon('foo', $attributes);
        $this->assertSame($expectedSvg, $svg);
    }

    /**
     * @return iterable<array{string|array{string, array<string, string|bool>}, array<string, string|bool>, string}>
     */
    public static function provideAriaHiddenCases(): iterable
    {
        yield 'no attributes' => [
            '<path d="M0 0L12 12"/>',
            [],
            '<svg aria-hidden="true"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'no attributes and icon attribute' => [
            ['<path d="M0 0L12 12"/>', ['aria-hidden' => 'false']],
            [],
            '<svg aria-hidden="false"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'no attributes and icon label' => [
            ['<path d="M0 0L12 12"/>', ['aria-label' => 'foo']],
            [],
            '<svg aria-label="foo"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'aria-hidden attribute' => [
            '<path d="M0 0L12 12"/>',
            ['aria-hidden' => 'true'],
            '<svg aria-hidden="true"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'aria-hidden attribute and icon attribute' => [
            ['<path d="M0 0L12 12"/>', ['aria-hidden' => 'false']],
            ['aria-hidden' => 'true'],
            '<svg aria-hidden="true"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'aria-hidden false + aria-label' => [
            '<path d="M0 0L12 12"/>',
            ['aria-hidden' => 'false', 'aria-label' => 'foo'],
            '<svg aria-hidden="false" aria-label="foo"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'title attribute' => [
            '<path d="M0 0L12 12"/>',
            ['title' => 'foo'],
            '<svg title="foo"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'aria-labelledby attribute' => [
            '<path d="M0 0L12 12"/>',
            ['aria-labelledby' => 'foo'],
            '<svg aria-labelledby="foo"><path d="M0 0L12 12"/></svg>',
        ];
        yield 'aria-label attribute' => [
            '<path d="M0 0L12 12"/>',
            ['aria-label' => 'foo'],
            '<svg aria-label="foo"><path d="M0 0L12 12"/></svg>',
        ];
    }

    public function testRenderIconWithAliases(): void
    {
        $registry = $this->createRegistry([
            'foo' => '<path d="M0 FOO"/>',
            'bar' => '<path d="M0 BAR"/>',
            'baz' => '<path d="M0 BAZ"/>',
        ]);
        $iconRenderer = new IconRenderer($registry, [], ['foo' => 'bar']);

        $svg = $iconRenderer->renderIcon('bar');
        $this->assertSame('<svg aria-hidden="true"><path d="M0 BAR"/></svg>', $svg);

        $svg = $iconRenderer->renderIcon('foo');
        $this->assertSame('<svg aria-hidden="true"><path d="M0 BAR"/></svg>', $svg);

        $svg = $iconRenderer->renderIcon('baz');
        $this->assertSame('<svg aria-hidden="true"><path d="M0 BAZ"/></svg>', $svg);
    }

    /**
     * @param array<string, string> $attributes
     *
     * @dataProvider provideRenderIconWithIconSetAttributes
     */
    public function testRenderIconWithIconSetAttributes(string $name, array $attributes, string $expectedSvg): void
    {
        $registry = $this->createRegistry([
            'a' => '<path d="a"/>',
            'a:b' => '<path d="a:b"/>',
            'a:b:c' => '<path d="a:b:c"/>',
        ]);
        $defaultIconAttributes = ['class' => 'def', 'x' => 'def_x'];
        $iconSetsAttributes = [
            'a' => ['class' => 'icons_a', 'x' => 'a'],
            'a:b' => ['class' => 'icons_ab', 'x' => 'ab'],
        ];

        $iconRenderer = new IconRenderer($registry, $defaultIconAttributes, [], $iconSetsAttributes);

        $svg = $iconRenderer->renderIcon($name, $attributes);
        $this->assertSame($expectedSvg, $svg);
    }

    public static function provideRenderIconWithIconSetAttributes(): iterable
    {
        yield 'icon set attributes (a:b)' => [
            'a:b',
            [],
            '<svg class="icons_a" x="a" aria-hidden="true"><path d="a:b"/></svg>',
        ];
        yield 'icon set attributes (a:b:c)' => [
            'a:b:c',
            [],
            '<svg class="icons_a" x="a" aria-hidden="true"><path d="a:b:c"/></svg>',
        ];
        yield 'icon set attributes and render attributes' => [
            'a:b',
            ['class' => 'render', 'y' => 'a'],
            '<svg class="render" x="a" y="a" aria-hidden="true"><path d="a:b"/></svg>',
        ];
    }

    private function createRegistry(array $icons): IconRegistryInterface
    {
        $registryIcons = [];
        foreach ($icons as $name => $data) {
            $data = (array) $data;
            if (array_is_list($data)) {
                $data = ['innerSvg' => $data[0], 'attributes' => $data[1] ?? []];
            }
            $registryIcons[$name] = new Icon(...$data);
        }

        return new InMemoryIconRegistry($registryIcons);
    }
}
