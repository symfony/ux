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

use Symfony\Component\Routing\Route;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RouteFilter
{
    private ?string $includeRegex;
    private ?string $excludeRegex;

    /**
     * @param list<string> $patterns
     */
    public function __construct(array $patterns)
    {
        $includedPatterns = [];
        $excludedPatterns = [];
        foreach ($patterns as $pattern) {
            if (str_starts_with($pattern, '!')) {
                $excludedPatterns[] = substr($pattern, 1);
            } else {
                $includedPatterns[] = $pattern;
            }
        }

        $this->includeRegex = self::buildRegex($includedPatterns);
        $this->excludeRegex = self::buildRegex($excludedPatterns);
    }

    public function isExposed(string $name, Route $route): bool
    {
        $expose = $route->getOption('expose');
        if (\is_bool($expose)) {
            return $expose;
        }

        $names = [$name];
        $canonicalName = $route->getDefault('_canonical_route');
        if (\is_string($canonicalName)) {
            $names[] = $canonicalName;
        }

        if (null === $this->includeRegex || [] === preg_grep($this->includeRegex, $names)) {
            return false;
        }

        return null === $this->excludeRegex || [] === preg_grep($this->excludeRegex, $names);
    }

    /**
     * @param list<string> $patterns
     */
    private static function buildRegex(array $patterns): ?string
    {
        $regexPatterns = [];
        foreach ($patterns as $pattern) {
            $pattern = trim($pattern);
            if ('' !== $pattern) {
                $regexPatterns[$pattern] = str_replace('\\*', '.*', preg_quote($pattern, '/'));
            }
        }

        if ([] === $regexPatterns) {
            return null;
        }

        return '/^(?:'.implode('|', $regexPatterns).')$/';
    }
}
