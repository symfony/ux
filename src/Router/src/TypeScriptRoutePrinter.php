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
final class TypeScriptRoutePrinter
{
    private const JSON_FLAGS = \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE;
    private const LITERALS_REGEX = '/^(?:[A-Za-z0-9_]|\\\\[-.])+(?:\|(?:[A-Za-z0-9_]|\\\\[-.])+)*$/';

    public function print(Route $route): string
    {
        $defaults = $route->getDefaults();
        $variables = $route->compile()->getVariables();
        $properties = [];

        foreach ($variables as $variable) {
            $optional = '_locale' === $variable || \array_key_exists($variable, $defaults);
            $properties[] = \sprintf('%s%s: %s', json_encode($variable, self::JSON_FLAGS), $optional ? '?' : '', $this->printVariableType($route->getRequirement($variable)));
        }

        $canonicalName = $defaults['_canonical_route'] ?? null;
        $locale = $defaults['_locale'] ?? null;
        if (\is_string($canonicalName) && \is_string($locale) && !\in_array('_locale', $variables, true)) {
            $properties[] = '"_locale"?: '.json_encode($locale, self::JSON_FLAGS);
        }

        $parameters = [] === $properties ? 'Record<never, never>' : '{ '.implode('; ', $properties).' }';

        if (\is_string($canonicalName)) {
            return \sprintf('Route<%s, %s>', $parameters, json_encode($canonicalName, self::JSON_FLAGS));
        }

        return [] === $properties ? 'Route' : \sprintf('Route<%s>', $parameters);
    }

    private function printVariableType(?string $requirement): string
    {
        if (null === $requirement || !preg_match(self::LITERALS_REGEX, $requirement)) {
            return 'string | number';
        }

        $types = [];
        foreach (explode('|', $requirement) as $literal) {
            $literal = stripslashes($literal);
            $types[] = json_encode($literal, self::JSON_FLAGS);
            if (ctype_digit($literal) && (string) (int) $literal === $literal) {
                $types[] = $literal;
            }
        }

        return implode(' | ', array_unique($types));
    }
}
