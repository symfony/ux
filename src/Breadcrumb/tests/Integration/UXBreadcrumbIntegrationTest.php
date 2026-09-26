<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Integration;

use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbResolver;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\BreadcrumbTrailProvider;
use Symfony\UX\Breadcrumb\Tests\Fixtures\TestKernel;
use Twig\Environment;

#[CoversNothing]
final class UXBreadcrumbIntegrationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testTheServicesAreWiredUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();

        self::assertInstanceOf(BreadcrumbResolver::class, $container->get('ux_breadcrumb.resolver'));
        self::assertInstanceOf(BreadcrumbTrailProvider::class, $container->get('ux_breadcrumb.trail_provider'));
        self::assertInstanceOf(ExpressionLanguage::class, $container->get('ux_breadcrumb.expression_language'));
    }

    public function testTheTwigFunctionsRenderTheCollectedTrail(): void
    {
        $twig = $this->twigForRequest('/products/blue-sneakers');

        $items = $twig->createTemplate('{{ ux_breadcrumb_items()|map(i => i.label)|join("|") }}')->render();
        self::assertSame('Dashboard|Products|Product released on Jan 1, 2024', $items);

        $html = $twig->createTemplate('{{ ux_breadcrumb() }}')->render();
        self::assertStringContainsString('<nav aria-label="Breadcrumb">', $html);
        self::assertStringContainsString('<a href="/dashboard">Dashboard</a>', $html);
        self::assertStringContainsString('<a href="/products?state=published">Products</a>', $html);
        self::assertStringContainsString('aria-current="page"', $html);
    }

    public function testAbsoluteModeFeedsTheJsonLdShape(): void
    {
        $twig = $this->twigForRequest('/products/blue-sneakers');

        $json = $twig->createTemplate(
            '{{ ux_breadcrumb_items(absolute: true)|map(i => i.url)|json_encode|raw }}',
        )->render();

        /** @var list<string> $urls */
        $urls = json_decode($json, true, 512, \JSON_THROW_ON_ERROR);

        self::assertCount(3, $urls);
        foreach ($urls as $url) {
            self::assertStringStartsWith('http', $url, 'Every crumb carries a URL in absolute mode.');
        }
    }

    public function testARouteWithoutCrumbsRendersNothing(): void
    {
        $twig = $this->twigForRequest('/plain');

        self::assertSame('', trim($twig->createTemplate('{{ ux_breadcrumb() }}')->render()));
        self::assertSame('0', $twig->createTemplate('{{ ux_breadcrumb_items()|length }}')->render());
    }

    public function testAControllerCanAppendACrumbAtRuntime(): void
    {
        $twig = $this->twigForRequest('/products');

        $this->trail()->append(new Breadcrumb(
            label: 'Blue sneakers',
            translationDomain: false,
        ));

        $labels = $twig->createTemplate('{{ ux_breadcrumb_items()|map(i => i.label)|join("|") }}')->render();
        self::assertSame('Dashboard|Products|Blue sneakers', $labels);
    }

    public function testACrumbAppendedAtRuntimeCanCarryItsOwnRouteParameters(): void
    {
        $twig = $this->twigForRequest('/products');

        $this->trail()->append(
            new Breadcrumb(
                label: 'Blue sneakers',
                route: 'product_view',
                parameters: ['slug' => 'blue-sneakers'],
                translationDomain: false,
            ),
            new Breadcrumb(label: 'Edit', translationDomain: false),
        );

        $html = $twig->createTemplate('{{ ux_breadcrumb() }}')->render();
        self::assertStringContainsString('<a href="/products/blue-sneakers">Blue sneakers</a>', $html);
    }

    public function testACrumbPrependedAtRuntimeLandsAboveTheRootCrumb(): void
    {
        $twig = $this->twigForRequest('/products');

        $this->trail()->prepend(new Breadcrumb(
            label: 'Everything',
            translationDomain: false,
        ));

        $labels = $twig->createTemplate('{{ ux_breadcrumb_items()|map(i => i.label)|join("|") }}')->render();
        self::assertSame('Everything|Dashboard|Products', $labels);
    }

    private function trail(): BreadcrumbTrail
    {
        $trailProvider = self::getContainer()->get('ux_breadcrumb.trail_provider');
        self::assertInstanceOf(BreadcrumbTrailProvider::class, $trailProvider);

        $trail = $trailProvider->getTrail();
        self::assertInstanceOf(BreadcrumbTrail::class, $trail);

        return $trail;
    }

    private function twigForRequest(string $uri): Environment
    {
        $kernel = self::bootKernel();
        $request = Request::create($uri);
        $kernel->handle($request);

        $container = self::getContainer();

        $requestStack = $container->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push($request);

        $twig = $container->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig;
    }
}
