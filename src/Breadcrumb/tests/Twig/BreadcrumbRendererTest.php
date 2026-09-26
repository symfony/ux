<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Breadcrumb\BreadcrumbItem;
use Symfony\UX\Breadcrumb\Exception\InvalidArgumentException;
use Symfony\UX\Breadcrumb\Twig\BreadcrumbRenderer;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

#[CoversClass(BreadcrumbRenderer::class)]
final class BreadcrumbRendererTest extends TestCase
{
    public function testRenderUsesTheDefaultTheme(): void
    {
        $renderer = new BreadcrumbRenderer($this->createTwig([
            '@UXBreadcrumb/theme/default.html.twig' => 'default:{{ items|length }}',
        ]));

        self::assertSame('default:2', $renderer->renderBreadcrumb($this->items()));
    }

    public function testRenderWithAnExplicitTheme(): void
    {
        $renderer = new BreadcrumbRenderer($this->createTwig([
            '@UXBreadcrumb/theme/default.html.twig' => 'default',
            '@App/breadcrumb.html.twig' => 'custom:{{ items[0].label }}',
        ]));

        self::assertSame('custom:Products', $renderer->renderBreadcrumb($this->items(), theme: '@App/breadcrumb.html.twig'));
    }

    public function testRenderNormalizesAttributes(): void
    {
        $renderer = new BreadcrumbRenderer($this->createTwig([
            '@UXBreadcrumb/theme/default.html.twig' => '{{ attributes|json_encode|raw }}',
        ]));

        $result = $renderer->renderBreadcrumb($this->items(), [
            'class' => new class implements \Stringable {
                public function __toString(): string
                {
                    return 'my-trail';
                }
            },
            'id' => 'trail',
        ]);

        self::assertSame('{"class":"my-trail","id":"trail"}', $result);
    }

    public function testBlankThemeIsRejected(): void
    {
        $renderer = new BreadcrumbRenderer($this->createTwig([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "theme" argument must be a non-empty string or null.');

        $renderer->renderBreadcrumb($this->items(), theme: ' ');
    }

    public function testInvalidAttributeNameIsRejected(): void
    {
        $renderer = new BreadcrumbRenderer($this->createTwig([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid attribute name in "attributes".');

        $renderer->renderBreadcrumb($this->items(), ['aria label' => 'Trail']);
    }

    public function testNonScalarAttributeValueIsRejected(): void
    {
        $renderer = new BreadcrumbRenderer($this->createTwig([]));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Attribute "data-items" in "attributes" is not scalar or Stringable.');

        $renderer->renderBreadcrumb($this->items(), ['data-items' => ['a']]);
    }

    /**
     * @return list<BreadcrumbItem>
     */
    private function items(): array
    {
        return [
            new BreadcrumbItem('Products', url: '/products'),
            new BreadcrumbItem('Blue sneakers'),
        ];
    }

    /**
     * @param array<string, string> $templates
     */
    private function createTwig(array $templates): Environment
    {
        return new Environment(new ArrayLoader($templates), ['strict_variables' => true]);
    }
}
