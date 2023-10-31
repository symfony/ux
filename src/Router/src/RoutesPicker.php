<?php

namespace Symfony\UX\Router;

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

final class RoutesPicker
{
    public function __construct(
        private RouterInterface $router
    ){
    }

    /**
     * @return iterable<string, Route>
     */
    public function pick(
        bool $onlyExposed,
    ): iterable {
        foreach ($this->router->getRouteCollection() as $name => $route) {
            if ($onlyExposed && !$route->getOption('expose')) {
                continue;
            }

            yield $name => $route;
        }
    }
}