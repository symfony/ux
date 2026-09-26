<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbResolver;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;
use Symfony\UX\Breadcrumb\Tests\Fixtures\RouteName;
use Symfony\UX\Breadcrumb\Tests\Fixtures\TestKernel;

#[CoversClass(BreadcrumbResolver::class)]
final class BreadcrumbResolverTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testUntranslatedLabelOnlyCrumbCarriesItsExtraAndNoUrl(): void
    {
        $trail = $this->trail(new Breadcrumb(
            label: 'Products',
            extra: ['icon' => 'tabler:package'],
            translationDomain: false,
        ));

        $items = $this->resolver()->resolve($trail);

        self::assertCount(1, $items);
        self::assertSame('Products', $items[0]->label);
        self::assertSame(['icon' => 'tabler:package'], $items[0]->extra, 'Extra data is forwarded untouched.');
        self::assertNull($items[0]->url);
    }

    public function testEveryCrumbButTheCurrentPageGetsAUrl(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'dashboard.home.breadcrumb', route: RouteName::DashboardHome->value),
            new Breadcrumb(label: 'product.index.breadcrumb', route: RouteName::ProductIndex->value),
            new Breadcrumb(label: 'product.view.breadcrumb', route: RouteName::ProductView->value, inheritedParameters: ['slug']),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/dashboard', $items[0]->url);
        self::assertSame('/products', $items[1]->url);
        self::assertNull($items[2]->url, 'The current page is never a link.');
    }

    public function testRouteParametersInheritOnlyTheNamedKeys(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.view.breadcrumb', route: RouteName::ProductView->value, inheritedParameters: ['slug']),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products/blue-sneakers', $items[0]->url);
        self::assertStringNotContainsString('unrelated', (string) $items[0]->url);
    }

    public function testAComputedParameterThatIsNotAPlaceholderLandsInTheQueryString(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.index.breadcrumb',
                route: RouteName::ProductIndex->value,
                computedParameters: ['state' => 'product.state'],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?state=published', $items[0]->url);
    }

    public function testAnInheritedParameterThatIsNotAPlaceholderLandsInTheQueryString(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.index.breadcrumb', route: RouteName::ProductIndex->value, inheritedParameters: ['slug']),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?slug=blue-sneakers', $items[0]->url);
    }

    public function testAComputedParameterWinsOverAnInheritedOneOfTheSameName(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.view.breadcrumb',
                route: RouteName::ProductView->value,
                inheritedParameters: ['slug'],
                computedParameters: ['slug' => 'product.state'],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products/published', $items[0]->url);
    }

    public function testATaggedExpressionFunctionProviderIsAvailableToCrumbExpressions(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.index.breadcrumb',
                route: RouteName::ProductIndex->value,
                computedParameters: ['state' => 'filter_state(product.state)'],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?state=PUBLISHED', $items[0]->url);
    }

    public function testTheBuiltInEnumFunctionSurvivesAnEscapedFqcn(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.index.breadcrumb',
                route: RouteName::ProductIndex->value,
                computedParameters: ['type' => 'enum("Symfony\\\\UX\\\\Breadcrumb\\\\Tests\\\\Fixtures\\\\RouteName::ProductIndex").value'],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?type=product_index', $items[0]->url);
    }

    #[RequiresPhpExtension('intl')]
    public function testTranslationParametersAreEvaluatedAndFedToIcu(): void
    {
        $trail = $this->trail(new Breadcrumb(
            label: 'product.view.breadcrumb',
            translationParameters: ['released_at' => 'product.releasedAt'],
        ));

        $french = $this->resolveWithLocale($trail, 'fr');
        $english = $this->resolveWithLocale($trail, 'en');

        self::assertStringContainsString('2024', $french);
        self::assertMatchesRegularExpression('/janv/i', $french, 'ICU formats the date for the active locale.');
        self::assertNotSame($english, $french);
    }

    public function testTranslationDomainFalseReturnsTheRawKey(): void
    {
        $trail = $this->trail(new Breadcrumb(
            label: 'product.index.breadcrumb',
            translationDomain: false,
        ));

        self::assertSame('product.index.breadcrumb', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAnUngenerableRouteDegradesToNoUrl(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'missing', route: 'route_that_does_not_exist', translationDomain: false),
            new Breadcrumb(label: 'incomplete', route: RouteName::ProductView->value, translationDomain: false),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertNull($items[0]->url, 'An unknown route renders as plain text, not a 500.');
        self::assertNull($items[1]->url, 'A missing required parameter renders as plain text, not a 500.');
    }

    public function testAbsoluteModeGivesEveryCrumbAUrlIncludingTheCurrentPage(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.index.breadcrumb', route: RouteName::ProductIndex->value),
            new Breadcrumb(label: 'product.view.breadcrumb'),
        );

        $items = $this->resolver()->resolve($trail, UrlGeneratorInterface::ABSOLUTE_URL);

        self::assertStringStartsWith('http', (string) $items[0]->url);
        self::assertStringStartsWith('http', (string) $items[1]->url);
        self::assertStringContainsString(
            '/products/blue-sneakers',
            (string) $items[1]->url,
            "The current page falls back to the trail's own route and its full route parameters.",
        );
    }

    public function testTheAbsoluteFallbackDoesNotLeakFrameworkRouteParameters(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail(
            'product_view',
            ['slug' => 'blue-sneakers', '_locale' => 'fr', '_format' => 'html'],
        );
        $trail->append(new Breadcrumb(label: 'product.view.breadcrumb'));

        $url = (string) $this->resolver()->resolve($trail, UrlGeneratorInterface::ABSOLUTE_URL)[0]->url;

        self::assertStringEndsWith('/products/blue-sneakers', $url);
        self::assertStringNotContainsString('_locale', $url);
        self::assertStringNotContainsString('_format', $url);
    }

    public function testAStringAndAnEnumBackedRouteNameResolveIdentically(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'enum', route: RouteName::ProductIndex->value, translationDomain: false),
            new Breadcrumb(label: 'string', route: 'product_index', translationDomain: false),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products', $items[0]->url);
        self::assertSame($items[1]->url, $items[0]->url);
    }

    private function resolveWithLocale(BreadcrumbTrail $trail, string $locale): string
    {
        $translator = self::getContainer()->get('translator');
        self::assertInstanceOf(LocaleAwareInterface::class, $translator);
        $translator->setLocale($locale);

        return $this->resolver()->resolve($trail)[0]->label;
    }

    private function resolver(): BreadcrumbResolver
    {
        $resolver = self::getContainer()->get('ux_breadcrumb.resolver');
        self::assertInstanceOf(BreadcrumbResolver::class, $resolver);

        return $resolver;
    }

    private function trail(Breadcrumb ...$crumbs): BreadcrumbTrail
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail(
            'product_view',
            ['slug' => 'blue-sneakers', 'unrelated' => 'leaked'],
            ['product' => new Product()],
        );
        $trail->append(...$crumbs);

        return $trail;
    }
}
