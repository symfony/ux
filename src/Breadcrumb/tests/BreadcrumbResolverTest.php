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
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbResolver;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Category;
use Symfony\UX\Breadcrumb\Tests\Fixtures\CountingTranslator;
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
            new Breadcrumb(label: 'product.view.breadcrumb', route: RouteName::ProductView->value, parameters: ['slug']),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/dashboard', $items[0]->url);
        self::assertSame('/products', $items[1]->url);
        self::assertNull($items[2]->url, 'The current page is never a link.');
    }

    public function testRouteParametersInheritOnlyTheNamedKeys(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.view.breadcrumb', route: RouteName::ProductView->value, parameters: ['slug']),
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
                parameters: ['state' => new Expression('product.state')],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?state=published', $items[0]->url);
    }

    public function testAnInheritedParameterThatIsNotAPlaceholderLandsInTheQueryString(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.index.breadcrumb', route: RouteName::ProductIndex->value, parameters: ['slug']),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?slug=blue-sneakers', $items[0]->url);
    }

    public function testOneMapMixesInheritedGivenAndComputedParameters(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.view.breadcrumb',
                route: RouteName::ProductView->value,
                parameters: ['slug', 'page' => 2, 'state' => new Expression('product.state')],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products/blue-sneakers?page=2&state=published', $items[0]->url);
    }

    public function testAnInheritedNameTheRouteDoesNotHaveIsSkipped(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.index.breadcrumb', route: RouteName::ProductIndex->value, parameters: ['missing']),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products', $items[0]->url);
    }

    public function testAComputedParameterWinsOverAnInheritedOneOfTheSameName(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.view.breadcrumb',
                route: RouteName::ProductView->value,
                parameters: ['slug' => new Expression('product.state'), 'slug'],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products/published', $items[0]->url, 'A keyed entry wins over a bare name, whatever their order.');
    }

    public function testAGivenParameterFillsAPlaceholderWithoutBeingEvaluated(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.view.breadcrumb', route: RouteName::ProductView->value, parameters: ['slug' => 'red-boots']),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products/red-boots', $items[0]->url, 'A given value is used as-is: as an expression, red-boots would subtract two unknown variables and degrade to no URL.');
    }

    public function testAGivenParameterThatIsNotAPlaceholderLandsInTheQueryString(): void
    {
        $trail = $this->trail(
            new Breadcrumb(label: 'product.index.breadcrumb', route: RouteName::ProductIndex->value, parameters: ['state' => 'published']),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products?state=published', $items[0]->url);
    }

    public function testAGivenParameterWinsOverAnInheritedOneOfTheSameName(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.view.breadcrumb',
                route: RouteName::ProductView->value,
                parameters: ['slug', 'slug' => 'red-boots'],
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertSame('/products/red-boots', $items[0]->url);
    }

    public function testATaggedExpressionFunctionProviderIsAvailableToCrumbExpressions(): void
    {
        $trail = $this->trail(
            new Breadcrumb(
                label: 'product.index.breadcrumb',
                route: RouteName::ProductIndex->value,
                parameters: ['state' => new Expression('filter_state(product.state)')],
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
                parameters: ['type' => new Expression('enum("Symfony\\\\UX\\\\Breadcrumb\\\\Tests\\\\Fixtures\\\\RouteName::ProductIndex").value')],
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
            translationParameters: ['released_at' => new Expression('product.releasedAt')],
        ));

        $french = $this->resolveWithLocale($trail, 'fr');
        $english = $this->resolveWithLocale($trail, 'en');

        self::assertStringContainsString('2024', $french);
        self::assertMatchesRegularExpression('/janv/i', $french, 'ICU formats the date for the active locale.');
        self::assertNotSame($english, $french);
    }

    public function testAPlainTranslationParameterIsUsedAsGiven(): void
    {
        $trail = $this->trail(new Breadcrumb(
            label: 'product.name.breadcrumb',
            translationParameters: ['name' => 'product.name'],
        ));

        self::assertSame('Product product.name', $this->resolver()->resolve($trail)[0]->label, 'Only an Expression is evaluated.');
    }

    public function testTranslationDomainFalseReturnsTheRawKey(): void
    {
        $trail = $this->trail(new Breadcrumb(
            label: 'product.index.breadcrumb',
            translationDomain: false,
        ));

        self::assertSame('product.index.breadcrumb', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAnExpressionLabelIsTheLabelItselfAndIsNotTranslated(): void
    {
        $translator = new CountingTranslator();
        $resolver = new BreadcrumbResolver(
            self::getContainer()->get('router'),
            new ExpressionLanguage(),
            $translator,
        );

        $items = $resolver->resolve($this->trail(new Breadcrumb(label: new Expression('product.name'))));

        self::assertSame('Blue sneakers', $items[0]->label);
        self::assertSame(0, $translator->calls);
    }

    public function testAnExpressionLabelReturningATranslatableIsTranslated(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('product_view', [], [
            'message' => new TranslatableMessage('product.name.breadcrumb', ['name' => 'Blue sneakers']),
        ]);
        $trail->append(new Breadcrumb(label: new Expression('message')));

        self::assertSame('Product Blue sneakers', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAnExpressionLabelNamingAnAbsentArgumentDegradesToAnEmptyLabel(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('product_view', [], []);
        $trail->append(new Breadcrumb(label: new Expression('product.name')));

        self::assertSame('', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAnExpressionLabelThatIsNotAStringDegradesToAnEmptyLabel(): void
    {
        $trail = $this->trail(new Breadcrumb(label: new Expression('product')));

        self::assertSame('', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAPatternLabelReadsAPropertyAndIsNotTranslated(): void
    {
        $translator = new CountingTranslator();
        $resolver = new BreadcrumbResolver(
            self::getContainer()->get('router'),
            new ExpressionLanguage(),
            $translator,
        );

        $items = $resolver->resolve($this->trail(new Breadcrumb(label: '{name:product}')));

        self::assertSame('Blue sneakers', $items[0]->label);
        self::assertSame(0, $translator->calls);
    }

    public function testAPatternLabelFollowsAPropertyPathUnderAnAlias(): void
    {
        $trail = $this->trail(new Breadcrumb(label: 'Edit {title:product.name} ({state:product})'));

        self::assertSame('Edit Blue sneakers (published)', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAPatternLabelReadsAGetter(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('category_view', [], ['category' => new Category()]);
        $trail->append(new Breadcrumb(label: '{name:category}'));

        self::assertSame('Shoes', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testABarePlaceholderPrefersTheArgumentOverTheRouteParameter(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('product_view', ['slug' => 'from-route', 'page' => '2'], ['slug' => 'from-argument']);
        $trail->append(new Breadcrumb(label: '{slug} page {page}'));

        self::assertSame('from-argument page 2', $this->resolver()->resolve($trail)[0]->label);
    }

    public function testAPlaceholderThatCannotBeReadDegradesToAnEmptyString(): void
    {
        $trail = $this->trail(new Breadcrumb(label: '[{name:category}][{missing:product}][{product}][{unknown}]'));

        self::assertSame('[][][][]', $this->resolver()->resolve($trail)[0]->label);
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

    public function testTheAbsoluteFallbackReusesEveryRouteParameter(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('product_view', ['slug' => 'blue-sneakers', '_locale' => 'fr']);
        $trail->append(new Breadcrumb(label: 'product.view.breadcrumb', translationDomain: false));

        $url = (string) $this->resolver()->resolve($trail, UrlGeneratorInterface::ABSOLUTE_URL)[0]->url;

        self::assertStringContainsString('/products/blue-sneakers', $url);
    }

    public function testAnExpressionNamingAnAbsentArgumentDegradesInsteadOfThrowing(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('product_index', [], []);
        $trail->append(
            new Breadcrumb(
                label: 'product.index.breadcrumb',
                route: RouteName::ProductIndex->value,
                parameters: ['state' => new Expression('product.state')],
                translationDomain: false,
            ),
            new Breadcrumb(label: 'leaf', translationDomain: false),
        );

        $items = $this->resolver()->resolve($trail);

        self::assertNull($items[0]->url, 'A crumb whose expression cannot be evaluated loses its URL, it does not 500.');
        self::assertSame('product.index.breadcrumb', $items[0]->label);
    }

    public function testAFailedTranslationParameterLeavesThePlaceholderRatherThanThrowing(): void
    {
        self::bootKernel();

        $trail = new BreadcrumbTrail('product_view', [], []);
        $trail->append(new Breadcrumb(
            label: 'product.view.breadcrumb',
            translationParameters: ['released_at' => new Expression('product.releasedAt')],
        ));

        self::assertNotSame('', $this->resolver()->resolve($trail)[0]->label);
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
