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

use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RouteNormalizer
{
    private const GENERATOR_DEFAULTS = ['_canonical_route' => true, '_fragment' => true, '_locale' => true];

    private static ?bool $urlGeneratorUsesQueryDefault = null;

    /**
     * @return array{tokens: list<array<int, mixed>>, defaults: object, hostTokens: list<array<int, mixed>>, schemes: list<string>}
     */
    public function normalize(Route $route): array
    {
        $compiledRoute = $route->compile();
        $keptDefaults = array_flip($compiledRoute->getVariables()) + self::GENERATOR_DEFAULTS;
        if (self::urlGeneratorUsesQueryDefault()) {
            $keptDefaults['_query'] = true;
        }

        return [
            'tokens' => self::normalizeTokens($compiledRoute->getTokens()),
            'defaults' => (object) array_intersect_key($route->getDefaults(), $keptDefaults),
            'hostTokens' => self::normalizeTokens($compiledRoute->getHostTokens()),
            'schemes' => $route->getSchemes(),
        ];
    }

    /**
     * @param list<array<int, mixed>> $tokens
     *
     * @return list<array<int, mixed>>
     */
    private static function normalizeTokens(array $tokens): array
    {
        foreach ($tokens as $i => $token) {
            if ('variable' === $token[0]) {
                $tokens[$i][2] = JavaScriptRegexConverter::convert($token[2], $token[4] ?? false);
            }
        }

        return $tokens;
    }

    /**
     * Older versions of symfony/routing ignore a "_query" route default, so the JavaScript generator must ignore it too.
     */
    private static function urlGeneratorUsesQueryDefault(): bool
    {
        if (null === self::$urlGeneratorUsesQueryDefault) {
            $routes = new RouteCollection();
            $routes->add('route', new Route('/', ['_query' => ['probe' => '1']]));

            self::$urlGeneratorUsesQueryDefault = '/?probe=1' === new UrlGenerator($routes, new RequestContext())->generate('route');
        }

        return self::$urlGeneratorUsesQueryDefault;
    }
}
