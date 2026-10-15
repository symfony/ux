<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Exception\LogicException;

/**
 * Walks the `parent` references of the #[Breadcrumb] attributes up to the top of the trail.
 *
 * Attributes never change at runtime, so the trail of each action is computed once per process.
 * A route name is resolved through a map of the routes to their controllers, kept in the cache pool because loading the route collection is expensive.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class ParentCrumbCollector
{
    private const string CACHE_KEY = 'route_controllers';

    /**
     * @var array<string, list<Breadcrumb>>
     */
    private array $trails = [];

    /**
     * @var array<string, array{class-string, string}>|null
     */
    private ?array $routes = null;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly ?CacheInterface $cache = null,
    ) {
    }

    /**
     * Returns the trails of the controller's ancestors followed by its own crumbs.
     *
     * @param list<Breadcrumb> $crumbs the crumbs the kernel read off the controller, used when the controller cannot be reflected (a closure)
     *
     * @return list<Breadcrumb>
     */
    public function collect(mixed $controller, array $crumbs): array
    {
        if (null !== $action = self::action($controller)) {
            return $this->trail($action[0], $action[1], []);
        }

        self::assertParentOnTop($crumbs, 'closure controller');

        return $this->withAncestors($crumbs, []);
    }

    /**
     * @param class-string $class
     * @param list<string> $visiting
     *
     * @return list<Breadcrumb>
     */
    private function trail(string $class, string $method, array $visiting): array
    {
        $action = $class.'::'.$method;

        if (isset($this->trails[$action])) {
            return $this->trails[$action];
        }

        if (\in_array($action, $visiting, true)) {
            throw new LogicException(\sprintf('The breadcrumb parents form a cycle: "%s".', implode(' -> ', [...$visiting, $action])));
        }

        $classCrumbs = self::crumbs(new \ReflectionClass($class));
        $methodCrumbs = self::crumbs(new \ReflectionMethod($class, $method));
        self::assertParentOnTop($classCrumbs, $class);
        self::assertParentOnTop($methodCrumbs, $action);

        $own = null !== ($methodCrumbs[0]->parent ?? null) ? $methodCrumbs : [...$classCrumbs, ...$methodCrumbs];

        return $this->trails[$action] = $this->withAncestors($own, [...$visiting, $action]);
    }

    /**
     * @param list<Breadcrumb> $own
     * @param list<string>     $visiting
     *
     * @return list<Breadcrumb>
     */
    private function withAncestors(array $own, array $visiting): array
    {
        if ([] === $own || null === $own[0]->parent) {
            return $own;
        }

        [$class, $method] = $this->resolve($own[0]);

        return [...$this->trail($class, $method, $visiting), ...$own];
    }

    /**
     * @return array{class-string, string}
     */
    private function resolve(Breadcrumb $crumb): array
    {
        $parent = $crumb->parent;

        if (\is_array($parent)) {
            [$class, $method] = array_pad(array_values($parent), 2, null);
            if (!\is_string($class) || !\is_string($method) || !class_exists($class)) {
                throw new LogicException(\sprintf('The parent of the breadcrumb "%s" must be an action written as [Controller::class, \'method\'].', $crumb->label));
            }
        } else {
            $parent = (string) $parent;
            [$class, $method] = (class_exists($parent) ? [$parent, '__invoke'] : null) ?? $this->routes()[$parent] ?? throw new LogicException(\sprintf('The parent "%s" of the breadcrumb "%s" is neither a controller class nor the name of a route whose controller is a class. Write an action as [Controller::class, \'method\'].', $parent, $crumb->label));
        }

        if (!method_exists($class, $method)) {
            throw new LogicException(\sprintf('The parent of the breadcrumb "%s" points at "%s::%s", which does not exist.', $crumb->label, $class, $method));
        }

        return [$class, $method];
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    private function routes(): array
    {
        return $this->routes ??= null === $this->cache
            ? $this->loadRoutes()
            : $this->cache->get(self::CACHE_KEY, fn (): array => $this->loadRoutes());
    }

    /**
     * @return array<string, array{class-string, string}>
     */
    private function loadRoutes(): array
    {
        $routes = [];
        foreach ($this->router->getRouteCollection()->all() as $name => $route) {
            if (null !== $action = self::action($route->getDefault('_controller'))) {
                $routes[$name] = $action;
            }
        }

        return $routes;
    }

    /**
     * Turns an array callable, a `Class::method` string, an invokable object or an invokable class name into a class and a method.
     *
     * @return array{class-string, string}|null
     */
    private static function action(mixed $controller): ?array
    {
        [$class, $method] = match (true) {
            \is_string($controller) => str_contains($controller, '::') ? explode('::', $controller, 2) : [$controller, '__invoke'],
            \is_array($controller) && 2 === \count($controller) => array_values($controller),
            \is_object($controller) && !$controller instanceof \Closure => [$controller, '__invoke'],
            default => [null, null],
        };

        if (\is_object($class)) {
            $class = $class::class;
        }

        return \is_string($class) && \is_string($method) && class_exists($class) ? [$class, $method] : null;
    }

    /**
     * @param \ReflectionClass<object>|\ReflectionMethod $reflector
     *
     * @return list<Breadcrumb>
     */
    private static function crumbs(\ReflectionClass|\ReflectionMethod $reflector): array
    {
        return array_map(
            static fn (\ReflectionAttribute $attribute): Breadcrumb => $attribute->newInstance(),
            $reflector->getAttributes(Breadcrumb::class),
        );
    }

    /**
     * @param list<Breadcrumb> $crumbs
     */
    private static function assertParentOnTop(array $crumbs, string $owner): void
    {
        foreach ($crumbs as $index => $crumb) {
            if (0 !== $index && null !== $crumb->parent) {
                throw new LogicException(\sprintf('The breadcrumb "%s" of "%s" names a parent, but only the first crumb of a class or of a method can.', $crumb->label, $owner));
            }
        }
    }
}
