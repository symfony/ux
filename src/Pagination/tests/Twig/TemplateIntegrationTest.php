<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\ArrayPaginationAdapter;
use Symfony\UX\Pagination\Cursor\CursorCodec;
use Symfony\UX\Pagination\Cursor\CursorOrder;
use Symfony\UX\Pagination\CursorPagination;
use Symfony\UX\Pagination\Navigation\PaginationUrlGenerator;
use Symfony\UX\Pagination\Pagination;
use Symfony\UX\Pagination\Twig\PaginationRenderer;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Loader\ArrayLoader;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Integration test: renders templates through a real Twig environment
 * and asserts the actual HTML output.
 */
final class TemplateIntegrationTest extends TestCase
{
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(\dirname(__DIR__, 2).'/templates', 'UXPagination');

        $this->twig = new Environment($loader, [
            'strict_variables' => true,
        ]);
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

    public function testDefaultTemplateRendersNativeNavigationWithoutRuntimeAttributes(): void
    {
        $html = $this->renderTheme(
            '@UXPagination/theme/default.html.twig',
            range(1, 50),
            2,
            10,
        );

        self::assertStringNotContainsString('data-controller=', $html);
        self::assertStringNotContainsString('data-current-page=', $html);
        self::assertStringNotContainsString('data-total-pages=', $html);
        self::assertStringNotContainsString('role="navigation"', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeKeepsTheSharedServerRenderedContract(string $theme): void
    {
        $html = $this->renderTheme($theme, range(1, 50), 2, 10);

        self::assertStringContainsString('<nav', $html);
        self::assertStringNotContainsString('role="navigation"', $html);
        self::assertStringContainsString('aria-label=', $html);
        self::assertStringContainsString('rel="prev"', $html);
        self::assertStringContainsString('rel="next"', $html);
        self::assertStringNotContainsString('symfony--ux-pagination--pagination', $html);
    }

    public static function themes(): iterable
    {
        yield 'default' => ['@UXPagination/theme/default.html.twig'];
        yield 'bootstrap' => ['@UXPagination/theme/bootstrap.html.twig'];
        yield 'tailwind' => ['@UXPagination/theme/tailwind.html.twig'];
    }

    #[DataProvider('themes')]
    public function testEveryThemeSupportsLookaheadPagination(
        string $theme,
    ): void {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
            lookahead: true,
        );

        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            theme: $theme,
        );

        self::assertStringContainsString('<nav', $html);
        self::assertStringContainsString('rel="prev"', $html);
        self::assertStringContainsString('rel="next"', $html);
        self::assertStringNotContainsString('aria-current="page"', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeSupportsCursorPagination(
        string $theme,
    ): void {
        $source = array_map(
            static fn (int $id): array => ['id' => $id],
            range(1, 30),
        );
        $pagination = new CursorPagination(
            source: $source,
            adapter: new ArrayPaginationAdapter(),
            cursor: null,
            perPage: 10,
            order: CursorOrder::byFields(['id'], 'ASC'),
            cursorCodec: new CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            theme: $theme,
        );

        self::assertStringContainsString('<nav', $html);
        self::assertStringContainsString('rel="next"', $html);
        self::assertStringContainsString('cursor=', $html);
        self::assertStringNotContainsString('aria-current="page"', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeHidesSinglePageNavigation(
        string $theme,
    ): void {
        $html = $this->renderTheme($theme, range(1, 5), 1, 10);

        self::assertStringNotContainsString('<nav', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeHidesSingleLookaheadPage(
        string $theme,
    ): void {
        $pagination = new Pagination(
            source: range(1, 5),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
            lookahead: true,
        );

        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            theme: $theme,
        );

        self::assertStringNotContainsString('<nav', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeHidesSingleCursorPage(
        string $theme,
    ): void {
        $pagination = new CursorPagination(
            source: [['id' => 1]],
            adapter: new ArrayPaginationAdapter(),
            cursor: null,
            perPage: 10,
            order: CursorOrder::byFields(['id'], 'ASC'),
            cursorCodec: new CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            theme: $theme,
        );

        self::assertStringNotContainsString('<nav', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeCanHideTheResultSummary(
        string $theme,
    ): void {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            theme: $theme,
            showInfo: false,
        );

        self::assertStringNotContainsString('Showing 11-20 of 50', $html);
    }

    public function testStandardTemplateDoesNotEmitPaginationJavaScriptHooks(): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination($pagination);

        self::assertStringNotContainsString('data-controller=', $html);
        self::assertStringNotContainsString('data-action=', $html);
        self::assertStringNotContainsString('data-announcement-template=', $html);
    }

    public function testIntegrationDataAttributesRemainPlain(): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination($pagination, [
            'data-turbo-frame' => 'products',
        ]);

        self::assertStringContainsString('data-turbo-frame="products"', $html);
    }

    #[DataProvider('themes')]
    public function testAttributesAreRenderedByEveryTheme(string $theme): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination($pagination, [
            'id' => 'product-pages',
            'data-turbo-frame' => 'products',
        ], theme: $theme);

        self::assertStringContainsString('id="product-pages"', $html);
        self::assertStringContainsString('data-turbo-frame="products"', $html);
    }

    #[DataProvider('themes')]
    public function testCustomClassAlwaysDecoratesTheNavigationElement(string $theme): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            ['class' => 'product-pages'],
            theme: $theme,
        );

        self::assertMatchesRegularExpression('/<nav[^>]+class="[^"]*product-pages[^"]*"/', $html);
    }

    public function testDefaultTemplateRendersPageLinksWithoutClientState(): void
    {
        $html = $this->renderTheme(
            '@UXPagination/theme/default.html.twig',
            range(1, 50),
            3,
            10,
        );

        self::assertStringContainsString('href="/items?page=2"', $html);
        self::assertStringContainsString('href="/items?page=4"', $html);
        self::assertStringNotContainsString('data-page=', $html);
        self::assertStringContainsString('aria-current="page"', $html);
    }

    public function testDefaultTemplateRendersRelPrevNext(): void
    {
        $html = $this->renderTheme(
            '@UXPagination/theme/default.html.twig',
            range(1, 50),
            3,
            10,
        );

        self::assertStringContainsString('rel="prev"', $html);
        self::assertStringContainsString('rel="next"', $html);
    }

    public function testBootstrapTemplateRendersWithBootstrapClasses(): void
    {
        $html = $this->renderTheme(
            '@UXPagination/theme/bootstrap.html.twig',
            range(1, 50),
            2,
            10,
        );

        self::assertStringContainsString('class="pagination', $html);
        self::assertStringContainsString('page-item', $html);
        self::assertStringContainsString('page-link', $html);
        self::assertStringNotContainsString('data-controller=', $html);
    }

    public function testTailwindTemplateRendersFocusRingClasses(): void
    {
        $html = $this->renderTheme(
            '@UXPagination/theme/tailwind.html.twig',
            range(1, 50),
            2,
            10,
        );

        self::assertStringContainsString('focus:ring-2', $html);
        self::assertStringNotContainsString('data-controller=', $html);
    }

    public function testExplicitBundleThemePathIsRendered(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../Fixtures/templates', 'UXPagination');
        $twig = new Environment($loader);
        $pagination = new Pagination(
            source: range(1, 5),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 5,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        self::assertSame(
            'custom template',
            trim(new PaginationRenderer($twig)->renderPagination(
                $pagination,
                theme: '@UXPagination/theme/custom.html.twig',
            )),
        );
    }

    public function testApplicationTemplateCanComposePartials(): void
    {
        $twig = new Environment(new ArrayLoader([
            'pagination/product.html.twig' => <<<'TWIG'
                {% include 'pagination/_summary.html.twig' %}
                <nav aria-label="Product pages">
                    {% for link in pagination.pages %}
                        {% include 'pagination/_page.html.twig' with {link: link} only %}
                    {% endfor %}
                    {% if pagination.hasNext() %}
                        <a rel="next" href="{{ pagination.nextUrl }}">Next</a>
                    {% endif %}
                </nav>
                TWIG,
            'pagination/_summary.html.twig' => '<p class="product-summary">{{ pagination.info }}</p>',
            'pagination/_page.html.twig' => <<<'TWIG'
                {% if link.isGap %}
                    <span aria-hidden="true">…</span>
                {% elseif link.isCurrent %}
                    <span aria-current="page">{{ link.page }}</span>
                {% else %}
                    <a class="product-page" href="{{ link.url }}">{{ link.page }}</a>
                {% endif %}
                TWIG,
        ]));

        $pagination = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/products'),
        );

        $html = new PaginationRenderer($twig)
            ->renderPagination($pagination, theme: 'pagination/product.html.twig');

        self::assertStringContainsString('class="product-summary"', $html);
        self::assertStringContainsString('class="product-page"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
        self::assertStringContainsString('rel="next"', $html);
    }

    public function testApplicationTemplateCanExtendTheDefaultBlocks(): void
    {
        $bundleLoader = new FilesystemLoader();
        $bundleLoader->addPath(\dirname(__DIR__, 2).'/templates', 'UXPagination');
        $twig = new Environment(new ChainLoader([
            new ArrayLoader([
                'pagination/product.html.twig' => <<<'TWIG'
                    {% extends '@UXPagination/theme/default.html.twig' %}

                    {% block info %}
                        <p class="product-summary">Product catalogue</p>
                    {% endblock %}
                    TWIG,
            ]),
            $bundleLoader,
        ]));
        $twig->addExtension(new class extends AbstractExtension {
            public function getFilters(): array
            {
                return [
                    new TwigFilter('trans', static fn (string $key) => $key),
                ];
            }
        });

        $pagination = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/products'),
        );

        $html = new PaginationRenderer($twig)
            ->renderPagination($pagination, theme: 'pagination/product.html.twig');

        self::assertStringContainsString('<p class="product-summary">Product catalogue</p>', $html);
        self::assertStringContainsString('class="ux-pagination__list"', $html);
        self::assertStringContainsString('aria-current="page"', $html);
    }

    public function testExtendedThemeOverridesTheLabelBlocks(): void
    {
        $twig = $this->createExtendingEnvironment(<<<'TWIG'
            {% extends '@!UXPagination/theme/default.html.twig' %}

            {% block previous_label %}&lsaquo; {{ 'Previous'|trans({}, 'UXPaginationBundle') }}{% endblock %}
            {% block next_label %}{{ 'Next'|trans({}, 'UXPaginationBundle') }} &rsaquo;{% endblock %}
            TWIG);
        $renderer = new PaginationRenderer($twig);

        $middlePage = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/products'),
        );
        $html = $renderer->renderPagination($middlePage, theme: 'pagination/application.html.twig');

        self::assertStringContainsString('&lsaquo; Previous', $html);
        self::assertStringContainsString('Next &rsaquo;', $html);
        self::assertStringNotContainsString('&larr;', $html);

        $firstPage = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/products'),
        );
        $html = $renderer->renderPagination($firstPage, theme: 'pagination/application.html.twig');

        // The disabled previous control uses the same overridden label
        self::assertStringContainsString('ux-pagination__link--disabled', $html);
        self::assertStringContainsString('&lsaquo; Previous', $html);
    }

    public function testExtendedThemeOverridesThePageLabelWithLinkContext(): void
    {
        $twig = $this->createExtendingEnvironment(<<<'TWIG'
            {% extends '@!UXPagination/theme/default.html.twig' %}

            {% block page_label %}p{{ link.page }}{% endblock %}
            TWIG);

        $pagination = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/products'),
        );
        $html = new PaginationRenderer($twig)
            ->renderPagination($pagination, theme: 'pagination/application.html.twig');

        self::assertStringContainsString('aria-current="page">p2<', $html);
        self::assertStringContainsString('>p1</a>', $html);
        self::assertStringContainsString('>p3</a>', $html);
    }

    private function createExtendingEnvironment(string $applicationTheme): Environment
    {
        $bundleLoader = new FilesystemLoader();
        $bundleLoader->addPath(\dirname(__DIR__, 2).'/templates', 'UXPagination');
        $bundleLoader->addPath(\dirname(__DIR__, 2).'/templates', '!UXPagination');

        $twig = new Environment(new ChainLoader([
            new ArrayLoader([
                'pagination/application.html.twig' => $applicationTheme,
            ]),
            $bundleLoader,
        ]));
        $twig->addExtension(new class extends AbstractExtension {
            public function getFilters(): array
            {
                return [
                    new TwigFilter('trans', static fn (string $key) => $key),
                ];
            }
        });

        return $twig;
    }

    #[DataProvider('themes')]
    public function testEveryThemeExposesTheDocumentedBlocks(
        string $theme,
    ): void {
        $bundleLoader = new FilesystemLoader();
        $bundleLoader->addPath(
            \dirname(__DIR__, 2).'/templates',
            'UXPagination',
        );
        $applicationTheme = str_replace(
            '__PARENT_THEME__',
            $theme,
            <<<'TWIG'
                {% extends '__PARENT_THEME__' %}

                {% block pagination %}
                    <div data-block="pagination">{{ parent() }}</div>
                {% endblock %}
                {% block info %}<span data-block="info"></span>{% endblock %}
                {% block navigation %}
                    <div data-block="navigation">{{ parent() }}</div>
                {% endblock %}
                {% block previous %}<span data-block="previous"></span>{% endblock %}
                {% block pages %}<span data-block="pages"></span>{% endblock %}
                {% block next %}<span data-block="next"></span>{% endblock %}
                TWIG,
        );
        $twig = new Environment(new ChainLoader([
            new ArrayLoader([
                'pagination/application.html.twig' => $applicationTheme,
            ]),
            $bundleLoader,
        ]));
        $twig->addExtension(new class extends AbstractExtension {
            public function getFilters(): array
            {
                return [
                    new TwigFilter(
                        'trans',
                        static fn (string $key) => $key,
                    ),
                ];
            }
        });

        $pagination = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/products',
            ),
        );

        $html = new PaginationRenderer($twig)->renderPagination(
            $pagination,
            theme: 'pagination/application.html.twig',
        );

        foreach (
            ['pagination', 'info', 'navigation', 'previous', 'pages', 'next'] as $block
        ) {
            self::assertStringContainsString(
                \sprintf('data-block="%s"', $block),
                $html,
            );
        }
    }

    public function testExplicitCustomTemplatePathIsPreserved(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath(__DIR__.'/../Fixtures/templates', 'App');
        $twig = new Environment($loader);
        $pagination = new Pagination(
            source: range(1, 5),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 1,
            perPage: 5,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        self::assertSame('explicit template', trim(new PaginationRenderer($twig)->renderPagination($pagination, theme: '@App/explicit.html.twig')));
    }

    public function testSinglePageDoesNotRenderNav(): void
    {
        $html = $this->renderTheme(
            '@UXPagination/theme/default.html.twig',
            range(1, 5),
            1,
            10,
        );

        self::assertStringNotContainsString('<nav', $html);
    }

    public function testCustomQueryParameterIsKeptInNativeLinks(): void
    {
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(queryParam: 'p', basePath: '/items'),
        );

        $renderer = new PaginationRenderer($this->twig);
        $html = $renderer->renderPagination(
            $pagination,
            theme: '@UXPagination/theme/default.html.twig',
        );

        self::assertStringContainsString('href="/items?p=3"', $html);
        self::assertStringNotContainsString('data-query-param=', $html);
    }

    public function testApplicationCanAttachItsOwnControllerAttribute(): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $html = new PaginationRenderer($this->twig)->renderPagination($pagination, [
            'data-controller' => 'app--catalog',
        ]);

        self::assertStringContainsString('data-controller="app--catalog"', $html);
    }

    public function testDocumentedAdjacentLinkMarkupUsesGuardedResultUrls(): void
    {
        $pagination = new Pagination(
            source: range(1, 30),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $html = $this->twig->createTemplate(<<<'TWIG'
            {% if pagination.hasPrevious %}
                <a href="{{ pagination.previousUrl }}" rel="prev">Previous</a>
            {% endif %}
            {% if pagination.hasNext %}
                <a href="{{ pagination.nextUrl }}" rel="next">Next</a>
            {% endif %}
            TWIG
        )->render(['pagination' => $pagination]);

        self::assertStringContainsString('href="/items" rel="prev"', $html);
        self::assertStringContainsString('href="/items?page=3" rel="next"', $html);
    }

    public function testDefaultTemplateAdaptsToLookaheadPagination(): void
    {
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
            lookahead: true,
        );

        $renderer = new PaginationRenderer($this->twig);
        $html = $renderer->renderPagination($pagination);

        self::assertStringContainsString('href="/items"', $html);
        self::assertStringContainsString('href="/items?page=3"', $html);
        self::assertStringNotContainsString('data-controller=', $html);
        self::assertStringNotContainsString('data-page=', $html);
    }

    public function testLookaheadPaginationKeepsTheCustomQueryParameter(): void
    {
        $pagination = new Pagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(queryParam: 'p', basePath: '/items'),
            lookahead: true,
        );

        $html = new PaginationRenderer($this->twig)->renderPagination($pagination);

        self::assertStringContainsString('href="/items?p=3"', $html);
        self::assertStringNotContainsString('data-query-param=', $html);
    }

    public function testDefaultTemplateAdaptsToCursorPagination(): void
    {
        $pagination = new CursorPagination(
            source: range(1, 100),
            adapter: new ArrayPaginationAdapter(),
            cursor: null,
            perPage: 10,
            order: CursorOrder::byFields(['id'], 'ASC'),
            cursorCodec: new CursorCodec('test-secret'),
            context: 'test',
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $renderer = new PaginationRenderer($this->twig);
        $html = $renderer->renderPagination($pagination);

        self::assertStringNotContainsString('data-controller=', $html);
        self::assertStringNotContainsString('data-current-page', $html);
        self::assertStringNotContainsString('data-page', $html);
        self::assertStringNotContainsString('data-query-param', $html);
    }

    public function testLinkAttributesKeepHrefFallbackAndAddLiveActions(): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );
        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            linkAttributes: static fn (array $link): array => [
                'data-action' => 'live#action:prevent',
                'data-live-action-param' => 'goToPage',
                'data-live-page-param' => $link['page'],
            ],
        );

        self::assertStringContainsString('href="/items"', $html);
        self::assertStringContainsString('href="/items?page=3"', $html);
        self::assertStringContainsString('data-action="live#action:prevent"', $html);
        self::assertStringContainsString('data-live-action-param="goToPage"', $html);
        self::assertStringContainsString('data-live-page-param="1"', $html);
        self::assertStringContainsString('data-live-page-param="3"', $html);
        self::assertStringNotContainsString('symfony--ux-pagination--pagination', $html);
    }

    public function testAttributeValuesAreEscapedAndClassesAreMerged(): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );
        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            ['class' => 'product-pages', 'data-label' => '"><script>alert(1)</script>'],
            navigationAttributes: ['class' => 'product-pages__controls'],
            linkAttributes: ['class' => 'product-pages__link', 'data-label' => 'A&B'],
        );

        self::assertStringContainsString('class="ux-pagination product-pages"', $html);
        self::assertStringContainsString('class="ux-pagination__list product-pages__controls"', $html);
        self::assertStringContainsString('class="ux-pagination__link product-pages__link"', $html);
        self::assertStringContainsString('data-label="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"', $html);
        self::assertStringContainsString('data-label="A&amp;B"', $html);
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeEscapesAttributeValues(string $theme): void
    {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
        );
        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            ['data-label' => '"><script>alert(1)</script>'],
            theme: $theme,
            navigationAttributes: ['data-label' => 'A&B'],
            linkAttributes: ['data-label' => '"unsafe"'],
        );

        self::assertStringContainsString(
            'data-label="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"',
            $html,
        );
        self::assertStringContainsString('data-label="A&amp;B"', $html);
        self::assertStringContainsString(
            'data-label="&quot;unsafe&quot;"',
            $html,
        );
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    #[DataProvider('themes')]
    public function testEveryThemeEscapesTwigMarkupAttributes(
        string $theme,
    ): void {
        $pagination = new Pagination(
            source: range(1, 50),
            adapter: new ArrayPaginationAdapter(),
            currentPage: 2,
            perPage: 10,
            paginationUrlGenerator: new PaginationUrlGenerator(
                basePath: '/items',
            ),
        );
        $markup = new Markup('"><script>alert(1)</script>', 'UTF-8');
        $html = new PaginationRenderer($this->twig)->renderPagination(
            $pagination,
            ['data-label' => $markup],
            theme: $theme,
            linkAttributes: ['data-label' => $markup],
        );

        self::assertStringContainsString(
            'data-label="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"',
            $html,
        );
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    /**
     * @param list<mixed> $source
     */
    private function renderTheme(
        string $theme,
        array $source,
        int $page,
        int $perPage,
    ): string {
        $pagination = new Pagination(
            source: $source,
            adapter: new ArrayPaginationAdapter(),
            currentPage: $page,
            perPage: $perPage,
            paginationUrlGenerator: new PaginationUrlGenerator(basePath: '/items'),
        );

        $renderer = new PaginationRenderer($this->twig);

        return $renderer->renderPagination($pagination, theme: $theme);
    }
}
