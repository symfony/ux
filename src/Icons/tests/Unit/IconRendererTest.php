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

        $this->assertSame('<svg viewBox="0 0 24 24" class="icon" id="FooBar"><path d="M0 0L12 12"/></svg>', $svg);
    }

    public function testRenderIconWithDefaultAttributes(): void
    {
        $registry = $this->createRegistry([
            'foo' => '<path d="M0 0L12 12"/>',
        ]);
        $iconRenderer = new IconRenderer($registry, ['viewBox' => '0 0 24 24', 'class' => 'icon']);

        $svg = $iconRenderer->renderIcon('foo');

        $this->assertSame('<svg viewBox="0 0 24 24" class="icon"><path d="M0 0L12 12"/></svg>', $svg);
    }

    /**
     * @dataProvider provideAttributesWithDefaultAttributesCases
     */
    public function testRenderIconWithAttributesAndDefaultAttributes($iconAttrs, $defaultAttrs, $renderAttr, $expectedTag): void
    {
        $registry = $this->createRegistry([
            'foo' => ['', $iconAttrs],
        ]);
        $iconRenderer = new IconRenderer($registry, $defaultAttrs);

        $svg = $iconRenderer->renderIcon('foo', $renderAttr);
        $this->assertStringStartsWith($expectedTag, $svg);
    }

    public static function provideAttributesWithDefaultAttributesCases()
    {
        yield 'no_attributes' => [
            [],
            [],
            [],
            '<svg>',
        ];
        yield 'icon_attributes_are_used' => [
            ['id' => 'icon'],
            [],
            [],
            '<svg id="icon">',
        ];
        yield 'default_attributes_are_used' => [
            [],
            ['id' => 'default'],
            [],
            '<svg id="default">',
        ];
        yield 'render_attributes_are_used' => [
            [],
            [],
            ['id' => 'render'],
            '<svg id="render">',
        ];
        yield 'default_attributes_take_precedence_on_icon' => [
            ['id' => 'icon'],
            ['id' => 'default'],
            [],
            '<svg id="default">',
        ];
        yield 'default_attributes_are_merged_with_icon_attributes' => [
            ['id' => 'icon', 'foo' => 'bar'],
            ['id' => 'default', 'baz' => 'qux'],
            [],
            '<svg id="default" foo="bar" baz="qux">',
        ];
        yield 'render_attributes_take_precedence_on_default' => [
            [],
            ['id' => 'default'],
            ['id' => 'render'],
            '<svg id="render">',
        ];
        yield 'render_attributes_are_merged_with_default_attributes' => [
            [],
            ['id' => 'default', 'foo' => 'bar'],
            ['id' => 'render', 'baz' => 'qux'],
            '<svg id="render" foo="bar" baz="qux">',
        ];
        yield 'render_attributes_take_precedence_on_icon' => [
            ['id' => 'icon'],
            [],
            ['id' => 'render'],
            '<svg id="render">',
        ];
        yield 'render_attributes_are_merged_with_icon_attributes' => [
            ['id' => 'icon', 'foo' => 'bar'],
            [],
            ['id' => 'render', 'baz' => 'qux'],
            '<svg id="render" foo="bar" baz="qux">',
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
