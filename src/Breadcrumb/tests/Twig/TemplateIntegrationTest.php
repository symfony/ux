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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Breadcrumb\BreadcrumbItem;
use Symfony\UX\Breadcrumb\Twig\BreadcrumbRenderer;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;

final class TemplateIntegrationTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'UXBreadcrumb');

        $this->twig = new Environment($loader, ['strict_variables' => true]);
        // Stub |trans filter: returns the key as-is (no real translator in tests)
        $this->twig->addExtension(new class extends AbstractExtension {
            public function getFilters(): array
            {
                return [
                    new TwigFilter('trans', static fn (string $key) => $key),
                ];
            }
        });
    }

    public function testTheThemeRendersAnAccessibleNavigation(): void
    {
        $html = $this->render();

        self::assertStringContainsString('<nav aria-label="Breadcrumb">', $html);
        self::assertStringContainsString('<ol>', $html);
        self::assertStringContainsString('<a href="/dashboard">Dashboard</a>', $html);
        self::assertStringContainsString('<a href="/products">Products</a>', $html);
        self::assertStringContainsString('<span aria-current="page">Blue sneakers</span>', $html);
    }

    public function testTheThemeShipsNoClasses(): void
    {
        self::assertStringNotContainsString('class=', $this->render());
    }

    public function testTheCurrentPageIsNeverALink(): void
    {
        $html = $this->render();

        self::assertSame(2, substr_count($html, '<a href='));
        self::assertSame(1, substr_count($html, 'aria-current="page"'));
    }

    public function testACrumbWithoutAUrlDegradesToAPageRatherThanAnEmptyHref(): void
    {
        $html = $this->render([
            new BreadcrumbItem('Products'),
            new BreadcrumbItem('Blue sneakers'),
        ]);

        self::assertStringNotContainsString('<a href=""', $html);
        self::assertSame(1, substr_count($html, 'aria-current="page"'), 'Only the current page claims aria-current.');
    }

    public function testExtraDataIsReachableFromAnOverridingTheme(): void
    {
        $this->twig->setLoader(new ChainLoader([
            new ArrayLoader(['custom.html.twig' => <<<'TWIG'
                {% extends '@UXBreadcrumb/theme/default.html.twig' %}
                {% block item_label %}{{ item.extra.icon|default('-') }}:{{ item.label }}{% endblock %}
                TWIG]),
            $this->twig->getLoader(),
        ]));

        $html = new BreadcrumbRenderer($this->twig, 'custom.html.twig')->renderBreadcrumb([
            new BreadcrumbItem('Products', '/products', ['icon' => 'tabler:package']),
            new BreadcrumbItem('Blue sneakers'),
        ]);

        self::assertStringContainsString('tabler:package:Products', $html);
        self::assertStringContainsString('-:Blue sneakers', $html);
    }

    public function testNoItemsRendersNothing(): void
    {
        self::assertSame('', trim($this->render([])));
    }

    public function testUserAttributesAreRenderedOnTheNav(): void
    {
        $html = $this->render(attributes: ['class' => 'my-trail', 'id' => 'trail']);

        self::assertStringContainsString('class="my-trail"', $html);
        self::assertStringContainsString('id="trail"', $html);
    }

    /**
     * @param list<BreadcrumbItem>|null                 $items
     * @param array<string, bool|float|int|string|null> $attributes
     */
    private function render(?array $items = null, array $attributes = []): string
    {
        $items ??= [
            new BreadcrumbItem('Dashboard', url: '/dashboard'),
            new BreadcrumbItem('Products', url: '/products'),
            new BreadcrumbItem('Blue sneakers'),
        ];

        return new BreadcrumbRenderer($this->twig)->renderBreadcrumb($items, $attributes);
    }
}
