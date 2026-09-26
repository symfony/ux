<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver\RequestPayloadValueResolver;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\EventListener\BreadcrumbListener;
use Symfony\UX\Breadcrumb\Tests\Fixtures\CountingTranslator;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Product;
use Symfony\UX\Breadcrumb\Tests\Fixtures\RouteName;
use Symfony\UX\Breadcrumb\Tests\Fixtures\TestKernel;

#[CoversClass(BreadcrumbListener::class)]
final class BreadcrumbListenerTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testItRunsAfterArgumentsHaveStoppedChanging(): void
    {
        $ours = BreadcrumbListener::getSubscribedEvents()[KernelEvents::CONTROLLER_ARGUMENTS][0][1];
        $payloadResolver = self::priorityOf(RequestPayloadValueResolver::getSubscribedEvents());

        self::assertLessThan(
            $payloadResolver,
            $ours,
            'A crumb expression naming a #[MapQueryString] argument must see the mapped DTO, not the attribute.',
        );
    }

    public function testTheStoredTrailHoldsRawAttributesAndRawTranslationKeys(): void
    {
        $trail = $this->handle('/products/blue-sneakers');

        $crumbs = $trail->all();
        self::assertContainsOnlyInstancesOf(Breadcrumb::class, $crumbs);
        self::assertSame(
            ['dashboard.home.breadcrumb', 'product.index.breadcrumb', 'product.view.breadcrumb'],
            array_map(static fn (Breadcrumb $crumb): string => $crumb->label, $crumbs),
        );
        self::assertSame(RouteName::ProductView->value, $crumbs[2]->route, 'The route is stored, not generated.');
    }

    public function testTheRootCrumbIsPrependedByListenerNotDeclaredByTheController(): void
    {
        $trail = $this->handle('/products');

        self::assertSame('dashboard.home.breadcrumb', $trail->all()[0]->label);
        self::assertCount(2, $trail->all());
    }

    public function testASectionHomePageIsItsOwnLoneRootCrumb(): void
    {
        $trail = $this->handle('/dashboard');

        self::assertTrue($trail->isEmpty(), 'The provider opts the dashboard home route out of its own root crumb.');
    }

    public function testContextIsEmptyWhenNoCrumbReferencesAnArgument(): void
    {
        self::assertSame([], $this->handle('/products')->context);
    }

    public function testARouteWithNoCrumbsAndNoRootStillGetsAnEmptyTrail(): void
    {
        $trail = $this->handle('/plain');

        self::assertInstanceOf(BreadcrumbTrail::class, $trail);
        self::assertTrue($trail->isEmpty());
        self::assertSame('plain', $trail->route);
    }

    public function testZeroRootCrumbProvidersIsASupportedConfiguration(): void
    {
        $trail = $this->handle('/products', environment: 'no_root_provider');

        self::assertCount(1, $trail->all());
        self::assertSame('product.index.breadcrumb', $trail->all()[0]->label);
    }

    public function testARedirectingControllerTranslatesNothingAndNarrowsTheContext(): void
    {
        $kernel = self::bootKernel(['environment' => 'counting_translator']);
        $request = Request::create('/products/blue-sneakers/redirect');
        $response = $kernel->handle($request);

        self::assertSame(Response::HTTP_FOUND, $response->getStatusCode());

        $translator = self::getContainer()->get('translator');
        self::assertInstanceOf(CountingTranslator::class, $translator);
        self::assertSame(0, $translator->calls, 'Nothing may be translated before a template asks for the crumbs.');

        $trail = $request->attributes->get(BreadcrumbTrail::ATTRIBUTE);
        self::assertInstanceOf(BreadcrumbTrail::class, $trail);
        self::assertSame(
            ['dashboard.home.breadcrumb', 'product.index.breadcrumb', 'product.view.breadcrumb'],
            array_map(static fn (Breadcrumb $crumb): string => $crumb->label, $trail->all()),
            'The trail is still collected in full, it is simply never resolved.',
        );

        self::assertSame(['product'], array_keys($trail->context), 'Only the argument the expressions name is kept.');
        self::assertInstanceOf(Product::class, $trail->context['product']);
    }

    /**
     * @param array<string, mixed> $subscribedEvents
     */
    private static function priorityOf(array $subscribedEvents): int
    {
        $listener = $subscribedEvents[KernelEvents::CONTROLLER_ARGUMENTS] ?? null;

        if (\is_array($listener)) {
            return (int) ($listener['priority'] ?? $listener[1] ?? 0);
        }

        return 0;
    }

    private function handle(string $uri, string $environment = 'test'): BreadcrumbTrail
    {
        $kernel = self::bootKernel(['environment' => $environment]);
        $request = Request::create($uri);
        $kernel->handle($request);

        $trail = $request->attributes->get(BreadcrumbTrail::ATTRIBUTE);
        self::assertInstanceOf(BreadcrumbTrail::class, $trail);

        return $trail;
    }
}
