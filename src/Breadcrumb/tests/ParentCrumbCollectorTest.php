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
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Exception\LogicException;
use Symfony\UX\Breadcrumb\ParentCrumbCollector;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\AdminProductController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\CatalogController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\InvalidParentController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductEditController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductIndexController;
use Symfony\UX\Breadcrumb\Tests\Fixtures\Controller\ProductViewController;

#[CoversClass(ParentCrumbCollector::class)]
final class ParentCrumbCollectorTest extends TestCase
{
    public function testAnActionWithoutAParentGetsItsClassAndMethodCrumbs(): void
    {
        self::assertSame(
            ['product.index.breadcrumb', 'product.view.breadcrumb'],
            $this->labels([AdminProductController::class, 'view']),
        );
    }

    public function testAnInvokableClassParentContributesItsWholeTrail(): void
    {
        self::assertSame(
            ['product.index.breadcrumb', 'product.view.breadcrumb', 'product.edit.breadcrumb'],
            $this->labels(new ProductEditController()),
        );
    }

    public function testARouteNameParentResolvesToItsController(): void
    {
        self::assertSame(
            ['product.index.breadcrumb', 'product.view.breadcrumb', 'product.edit.breadcrumb'],
            $this->labels([new CatalogController(), 'edit']),
            'The method-level parent replaces the class head, so the class crumb appears once.',
        );
    }

    public function testAnActionParentChainsAcrossSeveralLevels(): void
    {
        self::assertSame(
            ['product.index.breadcrumb', 'product.view.breadcrumb', 'product.edit.breadcrumb', 'product.history.breadcrumb'],
            $this->labels([CatalogController::class, 'history']),
        );
    }

    public function testAStringActionControllerIsSupported(): void
    {
        self::assertSame(
            ['product.index.breadcrumb', 'product.view.breadcrumb', 'product.edit.breadcrumb', 'product.history.breadcrumb'],
            $this->labels(CatalogController::class.'::history'),
        );
    }

    public function testAClosureControllerChainsFromTheCrumbsItWasGiven(): void
    {
        $trail = $this->collector()->collect(
            static fn (): null => null,
            [new Breadcrumb('closure', parent: ProductIndexController::class)],
        );

        self::assertSame(['product.index.breadcrumb', 'closure'], self::labelsOf($trail));
    }

    public function testAnAncestorKeepsItsOwnRouteAndParameters(): void
    {
        $trail = $this->collector()->collect(new ProductEditController(), $this->attributesOf(new ProductEditController()));

        self::assertSame('product_view', $trail[1]->route);
        self::assertSame(['slug'], $trail[1]->parameters);
    }

    public function testACycleIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InvalidParentController::class.'::first -> '.InvalidParentController::class.'::second -> '.InvalidParentController::class.'::first');

        $this->labels([InvalidParentController::class, 'first']);
    }

    public function testAnActionThatIsItsOwnParentIsRejected(): void
    {
        $this->expectException(LogicException::class);

        $this->labels([InvalidParentController::class, 'self']);
    }

    public function testAnUnknownRouteIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"no_such_route"');

        $this->labels([InvalidParentController::class, 'unknownRoute']);
    }

    public function testAMissingActionIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(InvalidParentController::class.'::missing');

        $this->labels([InvalidParentController::class, 'missingMethod']);
    }

    public function testAnActionWrittenAsAStringIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"string action"');

        $this->labels([InvalidParentController::class, 'stringAction']);
    }

    public function testAnActionArrayWithoutAMethodIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"malformed"');

        $this->labels([InvalidParentController::class, 'malformedAction']);
    }

    public function testAParentBelowTheFirstCrumbOfALevelIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"misplaced"');

        $this->labels([InvalidParentController::class, 'misplaced']);
    }

    public function testAnUnreflectableRouteControllerIsRejected(): void
    {
        $collector = new ParentCrumbCollector($this->router(['service_route' => 'app.some_service::action']));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"service_route"');

        $collector->collect(static fn (): null => null, [new Breadcrumb('leaf', parent: 'service_route')]);
    }

    public function testTheRouteMapIsReadOnceAndStoredInTheCache(): void
    {
        $cache = new ArrayAdapter();
        $leaf = [new Breadcrumb('leaf', parent: 'catalog_view')];

        new ParentCrumbCollector($this->router(), $cache)->collect(static fn (): null => null, $leaf);

        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('getRouteCollection');

        $trail = new ParentCrumbCollector($router, $cache)->collect(static fn (): null => null, $leaf);

        self::assertSame(['product.index.breadcrumb', 'product.view.breadcrumb', 'leaf'], self::labelsOf($trail));
    }

    public function testALocalizedRouteResolvesByItsCanonicalName(): void
    {
        $routes = new RouteCollection();
        foreach (['en' => '/products/{slug}', 'fr' => '/produits/{slug}'] as $locale => $path) {
            $routes->add('localized_view.'.$locale, new Route($path, ['_controller' => ProductViewController::class, '_canonical_route' => 'localized_view', '_locale' => $locale]));
        }
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);

        $trail = new ParentCrumbCollector($router)->collect(static fn (): null => null, [new Breadcrumb('leaf', parent: 'localized_view')]);

        self::assertSame(['product.index.breadcrumb', 'product.view.breadcrumb', 'leaf'], self::labelsOf($trail));
    }

    public function testTheCrumbsTheKernelGaveWinOverTheDeclaredOnes(): void
    {
        $trail = $this->collector()->collect(
            [CatalogController::class, 'edit'],
            [new Breadcrumb('replaced', parent: ProductIndexController::class)],
        );

        self::assertSame(['product.index.breadcrumb', 'replaced'], self::labelsOf($trail));
    }

    public function testAFirstClassCallableIsCollectedLikeItsAction(): void
    {
        self::assertSame(
            ['product.index.breadcrumb', 'product.view.breadcrumb', 'product.edit.breadcrumb'],
            $this->labels(new CatalogController()->edit(...)),
        );
    }

    public function testReplacedCrumbsDoNotResolveTheDeclaredParent(): void
    {
        $trail = $this->collector()->collect(
            [InvalidParentController::class, 'unknownRoute'],
            [new Breadcrumb('replaced', parent: ProductIndexController::class)],
        );

        self::assertSame(['product.index.breadcrumb', 'replaced'], self::labelsOf($trail));
    }

    public function testAMisplacedParentInReplacedCrumbsIsRejected(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('"late"');

        $this->collector()->collect([CatalogController::class, 'index'], [
            new Breadcrumb('class level', parent: ProductIndexController::class),
            new Breadcrumb('method level'),
            new Breadcrumb('late', parent: ProductIndexController::class),
        ]);
    }

    public function testResetForgetsTheRouteMap(): void
    {
        $before = new RouteCollection();
        $before->add('moved', new Route('/moved', ['_controller' => ProductIndexController::class]));
        $after = new RouteCollection();
        $after->add('moved', new Route('/moved', ['_controller' => ProductViewController::class]));
        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($before, $after);

        $collector = new ParentCrumbCollector($router);
        $leaf = [new Breadcrumb('leaf', parent: 'moved')];

        self::assertSame(['product.index.breadcrumb', 'leaf'], self::labelsOf($collector->collect(static fn (): null => null, $leaf)));

        $collector->reset();

        self::assertSame(['product.index.breadcrumb', 'product.view.breadcrumb', 'leaf'], self::labelsOf($collector->collect(static fn (): null => null, $leaf)));
    }

    /**
     * @return list<string>
     */
    private function labels(mixed $controller): array
    {
        return self::labelsOf($this->collector()->collect($controller, $this->attributesOf($controller)));
    }

    /**
     * @return list<Breadcrumb>
     */
    private function attributesOf(mixed $controller): array
    {
        if (\is_string($controller)) {
            $controller = explode('::', $controller, 2);
        }
        // An instance method is only callable on an instance.
        if (\is_array($controller) && \is_string($controller[0])) {
            $controller[0] = new $controller[0]();
        }

        return new ControllerEvent($this->createStub(HttpKernelInterface::class), $controller, new Request(), HttpKernelInterface::MAIN_REQUEST)->getAttributes(Breadcrumb::class);
    }

    /**
     * @param list<Breadcrumb> $crumbs
     *
     * @return list<string>
     */
    private static function labelsOf(array $crumbs): array
    {
        return array_map(static fn (Breadcrumb $crumb): string => $crumb->label, $crumbs);
    }

    private function collector(): ParentCrumbCollector
    {
        return new ParentCrumbCollector($this->router());
    }

    /**
     * @param array<string, mixed> $controllers
     */
    private function router(array $controllers = []): RouterInterface
    {
        $controllers += [
            'catalog_index' => [CatalogController::class, 'index'],
            'catalog_view' => [CatalogController::class, 'view'],
            'catalog_edit' => CatalogController::class.'::edit',
            'product_view' => ProductViewController::class,
        ];

        $routes = new RouteCollection();
        foreach ($controllers as $name => $controller) {
            $routes->add($name, new Route('/'.$name, ['_controller' => $controller]));
        }

        $router = $this->createStub(RouterInterface::class);
        $router->method('getRouteCollection')->willReturn($routes);

        return $router;
    }
}
