<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\Route;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @final
 *
 * @experimental
 */
class RoutesDumper
{
    public function __construct(
        private string     $dumpDir,
        private Filesystem $filesystem,
    )
    {
    }

    /**
     * @param iterable<string, Route> $routes
     */
    public function dump(iterable $routes): void
    {
        $this->filesystem->mkdir($this->dumpDir);
        $this->filesystem->remove($this->dumpDir . '/index.js');
        $this->filesystem->remove($this->dumpDir . '/index.d.ts');
        $this->filesystem->remove($this->dumpDir . '/configuration.js');
        $this->filesystem->remove($this->dumpDir . '/configuration.d.ts');

        $routes = [...$routes];
        dump($routes);

        foreach ($routes as $routeName => $route) {
            dd($route);
        }
        // TODO
    }

    /**
     * @return array{
     *     defaults: array<string, mixed>,
     *     requirements: array<string, string>,
     *     tokens: list<list<mixed>>,
     *     hostTokens: list<list<mixed>>
     * }
     */
    private function normalizedRoute(Route $route): array
    {
        $compiledRoute = $route->compile();
        $defaults = array_intersect_key(
            $route->getDefaults(),
            array_fill_keys($compiledRoute->getVariables(), null
            )
        );

        return [
            'defaults' => $defaults,
            'requirements' => $route->getRequirements(),
            'tokens' => $compiledRoute->getTokens(),
            'hostTokens' => $compiledRoute->getHostTokens(),
        ];
    }

    private function getVariableTypeScriptType(Route $route, string $variableName): string
    {
        return 'unknown';
    }
}
