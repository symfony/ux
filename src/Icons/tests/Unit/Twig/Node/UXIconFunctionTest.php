<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Twig\Node;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconRegistryInterface;
use Symfony\UX\Icons\IconRenderer;
use Symfony\UX\Icons\Twig\Node\UXIconFunction;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\TwigFunction;

final class UXIconFunctionTest extends TestCase
{
    public function testCompileRendersIconWithConstantNameAndAttributes(): void
    {
        $iconRegistry = $this->createMock(IconRegistryInterface::class);
        $iconRenderer = new IconRenderer($iconRegistry);
        $iconRegistry->method('get')->with('icon-name')->willReturn(new Icon('<svg>icon</svg>'));

        $environment = $this->createMock(Environment::class);
        $environment->method('getRuntime')->willReturn($iconRenderer);

        $compiler = new Compiler($environment);

        $iconName = new ConstantExpression('icon-name', 1);
        $attributes = new ArrayExpression([
            new ConstantExpression('class', 2),
            new ConstantExpression('icon-class', 3),
        ], 4);

        $arguments = new Node([$iconName, $attributes]);

        $node = new UXIconFunction('ux_icon', $arguments, 1);
        $node->compile($compiler);

        $this->assertSame('"<svg class=\"icon-class\" aria-hidden=\"true\"><svg>icon</svg></svg>"', $compiler->getSource());
    }

    public function testCompileHandlesDynamicAttributeValues(): void
    {
        $iconRegistry = $this->createMock(IconRegistryInterface::class);
        $iconRenderer = new IconRenderer($iconRegistry);
        $iconRegistry->method('get')->with('icon-name')->willReturn(new Icon('<svg>icon</svg>'));

        $environment = $this->createMock(Environment::class);
        $environment->method('getRuntime')->willReturn($iconRenderer);

        $compiler = new Compiler($environment);

        $iconName = new ConstantExpression('icon-name', 1);
        $attributes = new ArrayExpression([
            new ConstantExpression('class', 2),
            new NameExpression('dynamic_value', 3),
        ], 4);

        $arguments = new Node([$iconName, $attributes]);

        $node = new UXIconFunction(new TwigFunction('ux_icon', fn () => null), $arguments, 1);
        $node->compile($compiler);

        $this->assertNotSame('<svg>icon</svg>', $compiler->getSource());
    }

    public function testCompileHandlesNonConstantIconName(): void
    {
        $iconRegistry = $this->createMock(IconRegistryInterface::class);
        $iconRenderer = new IconRenderer($iconRegistry);
        $iconRegistry->method('get')->with('dynamic_icon_name')->willReturn(new Icon('<svg>icon</svg>'));

        $environment = $this->createMock(Environment::class);
        $environment->method('getRuntime')->willReturn($iconRenderer);

        $compiler = new Compiler($environment);

        $iconName = new NameExpression('dynamic_icon_name', 1);
        $arguments = new Node([$iconName]);

        $node = new UXIconFunction(new TwigFunction('ux_icon', fn () => null), $arguments, 1);
        $node->compile($compiler);

        $this->assertNotSame('<svg>icon</svg>', $compiler->getSource());
    }

    public function testCompileWithoutAttributes(): void
    {
        $iconRegistry = $this->createMock(IconRegistryInterface::class);
        $iconRenderer = new IconRenderer($iconRegistry);
        $iconRegistry->method('get')->with('icon_name')->willReturn(new Icon('<svg>icon</svg>'));

        $environment = $this->createMock(Environment::class);
        $environment->method('getRuntime')->willReturn($iconRenderer);

        $compiler = new Compiler($environment);

        $iconName = new ConstantExpression('icon_name', 1);
        $arguments = new Node([$iconName]);

        $node = new UXIconFunction(new TwigFunction('ux_icon', fn () => null), $arguments, 1);
        $node->compile($compiler);

        $this->assertSame('"<svg aria-hidden=\"true\"><svg>icon</svg></svg>"', $compiler->getSource());
    }
}
