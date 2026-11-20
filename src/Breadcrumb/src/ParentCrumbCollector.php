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
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\Exception\LogicException;

/**
 * Walks the `parent` references of the #[Breadcrumb] attributes up to the top of the trail.
 *
 * Attributes never change at runtime, so the trail of each action is computed once per process, or once per request in debug mode.
 * A route name is resolved through a map of the routes to their controllers, kept in the cache pool because loading the route collection is expensive.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class ParentCrumbCollector implements ResetInterface
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

    /**
     * @var \WeakMap<Breadcrumb, string>
     */
    private \WeakMap $owners;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly ?CacheInterface $cache = null,
        private readonly bool $debug = false,
    ) {
        $this->owners = new \WeakMap();
    }

    /**
     * Returns the trails of the controller's ancestors followed by its own crumbs.
     *
     * The own crumbs are the ones the kernel gives, which a listener may have replaced through ControllerEvent::setController().
     * The kernel lists the class-level crumbs first, so the class tells how many of them there are.
     *
     * @param list<Breadcrumb> $crumbs              the crumbs the kernel read off the controller
     * @param list<string>     $argumentNames       only read in debug mode
     * @param list<string>     $routeParameterNames only read in debug mode
     *
     * @return list<Breadcrumb>
     */
    public function collect(mixed $controller, array $crumbs, array $argumentNames = [], array $routeParameterNames = []): array
    {
        if (null === $action = self::action($controller)) {
            self::assertParentOnTop($crumbs, 'closure controller');
            $own = $crumbs;
            $visiting = [];
            $current = 'the closure controller';
        } else {
            [$class, $method] = $action;
            $classLevel = \count(new \ReflectionClass($class)->getAttributes(Breadcrumb::class));
            $id = \sprintf('%s::%s', $class, $method);
            $own = self::own(\array_slice($crumbs, 0, $classLevel), $class, \array_slice($crumbs, $classLevel), $id);
            $visiting = [$id];
            $current = \sprintf('"%s::%s()"', $class, $method);
        }

        $trail = $this->withAncestors($own, $visiting);

        if ($this->debug) {
            $this->assertAncestorsResolve(\array_slice($trail, 0, \count($trail) - \count($own)), $current, $argumentNames, $routeParameterNames);
        }

        return $trail;
    }

    public function reset(): void
    {
        $this->trails = [];
        $this->routes = null;
        $this->owners = new \WeakMap();
    }

    /**
     * A bare placeholder falls back to the route parameter of the same name, so it only needs one of the two.
     *
     * @param list<Breadcrumb> $ancestors
     * @param list<string>     $argumentNames
     * @param list<string>     $routeParameterNames
     */
    private function assertAncestorsResolve(array $ancestors, string $current, array $argumentNames, array $routeParameterNames): void
    {
        foreach ($ancestors as $crumb) {
            if (false === $crumb->translationDomain) {
                continue;
            }

            foreach (LabelPattern::parse($crumb->label) as $placeholder) {
                if (null !== $argument = $placeholder['argument']) {
                    if (!\in_array($argument, $argumentNames, true)) {
                        throw new LogicException(\sprintf('The breadcrumb "%s" of "%s" reads the argument "$%s", which %s does not have.', $crumb->label, $this->owners[$crumb], $argument, $current));
                    }

                    continue;
                }

                $variable = $placeholder['variable'];
                if (!\in_array($variable, $argumentNames, true) && !\in_array($variable, $routeParameterNames, true)) {
                    throw new LogicException(\sprintf('The breadcrumb "%s" of "%s" reads "%s", which is neither an argument of %s nor a parameter of its route.', $crumb->label, $this->owners[$crumb], $placeholder['placeholder'], $current));
                }
            }
        }
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
        foreach ($classCrumbs as $crumb) {
            $this->owners[$crumb] = $class;
        }
        foreach ($methodCrumbs as $crumb) {
            $this->owners[$crumb] = $action;
        }

        $own = self::own($classCrumbs, $class, $methodCrumbs, $action);

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

                // A localized route is stored once per locale, as "name.locale", but the URL generator also accepts its canonical name.
                if (\is_string($canonical = $route->getDefault('_canonical_route'))) {
                    $routes[$canonical] ??= $action;
                }
            }
        }

        return $routes;
    }

    /**
     * Turns an array callable, a `Class::method` string, a first-class callable, an invokable object or an invokable class name into a class and a method.
     *
     * @return array{class-string, string}|null
     */
    private static function action(mixed $controller): ?array
    {
        if ($controller instanceof \Closure) {
            $function = new \ReflectionFunction($controller);
            $controller = $function->isAnonymous() ? null : [$function->getClosureCalledClass()?->name, $function->name];
        }

        [$class, $method] = match (true) {
            \is_string($controller) => str_contains($controller, '::') ? explode('::', $controller, 2) : [$controller, '__invoke'],
            \is_array($controller) && 2 === \count($controller) => array_values($controller),
            \is_object($controller) => [$controller, '__invoke'],
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
     * A method-level parent replaces the class-level crumbs.
     *
     * @param list<Breadcrumb> $classCrumbs
     * @param list<Breadcrumb> $methodCrumbs
     *
     * @return list<Breadcrumb>
     */
    private static function own(array $classCrumbs, string $class, array $methodCrumbs, string $action): array
    {
        self::assertParentOnTop($classCrumbs, $class);
        self::assertParentOnTop($methodCrumbs, $action);

        return null !== ($methodCrumbs[0]->parent ?? null) ? $methodCrumbs : [...$classCrumbs, ...$methodCrumbs];
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
