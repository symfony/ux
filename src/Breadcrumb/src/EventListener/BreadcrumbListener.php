<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\UX\Breadcrumb\Attribute\Breadcrumb;
use Symfony\UX\Breadcrumb\BreadcrumbTrail;
use Symfony\UX\Breadcrumb\RootCrumbProviderInterface;

/**
 * Collects the #[Breadcrumb] attributes of the matched controller into a trail on the request.
 *
 * Nothing is translated or turned into a URL here. See BreadcrumbResolver.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class BreadcrumbListener implements EventSubscriberInterface
{
    /**
     * @param iterable<RootCrumbProviderInterface> $rootCrumbProviders
     */
    public function __construct(
        private readonly iterable $rootCrumbProviders = [],
        private readonly string $requestAttribute = BreadcrumbTrail::ATTRIBUTE,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Must clear RequestPayloadValueResolver, which maps the #[Map*] arguments
            // into their DTOs at -10100 on Symfony 8.x and at 0 on 7.4, so that
            // getNamedArguments() is final by the time a crumb expression reads it.
            KernelEvents::CONTROLLER_ARGUMENTS => [
                ['buildTrail', -10200],
            ],
        ];
    }

    public function buildTrail(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        $route = $request->attributes->getString('_route');

        $routeParameters = $request->attributes->get('_route_params', []);
        if (!\is_array($routeParameters)) {
            $routeParameters = [];
        }
        // Route parameters are always named, but the attribute bag cannot promise it.
        $routeParameters = array_filter($routeParameters, \is_string(...), \ARRAY_FILTER_USE_KEY);

        /** @var list<Breadcrumb> $crumbs */
        $crumbs = array_values($event->getAttributes(Breadcrumb::class));

        $roots = $this->roots($route, $request);

        $trail = new BreadcrumbTrail(
            $route,
            $routeParameters,
            $this->context([...$roots, ...$crumbs], $event->getNamedArguments()),
        );

        $trail->append(...$crumbs);

        if ([] !== $roots) {
            $trail->prepend(...$roots);
        }

        $request->attributes->set($this->requestAttribute, $trail);
    }

    /**
     * @return list<Breadcrumb>
     */
    private function roots(string $route, Request $request): array
    {
        $roots = [];
        foreach ($this->rootCrumbProviders as $provider) {
            foreach ($provider($route, $request) as $crumb) {
                $roots[] = $crumb;
            }
        }

        return $roots;
    }

    /**
     * Keeps only the controller arguments a crumb expression actually names, so the trail does not pin the whole argument list (notably the Request) into the request attributes until render time.
     *
     * A superset is harmless. Over-keeping a name never breaks evaluation, whereas dropping a referenced one would.
     *
     * @param list<Breadcrumb>     $crumbs
     * @param array<string, mixed> $arguments
     *
     * @return array<string, mixed>
     */
    private function context(array $crumbs, array $arguments): array
    {
        if ([] === $crumbs || [] === $arguments) {
            return [];
        }

        $referenced = [];
        foreach ($crumbs as $crumb) {
            foreach ([...$crumb->computedParameters, ...$crumb->translationParameters] as $expression) {
                if (preg_match_all('/[a-zA-Z_]\w*/', $expression, $matches)) {
                    foreach ($matches[0] as $name) {
                        $referenced[$name] = true;
                    }
                }
            }
        }

        return array_intersect_key($arguments, $referenced);
    }
}
