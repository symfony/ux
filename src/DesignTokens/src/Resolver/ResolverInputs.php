<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Resolver;

use Symfony\UX\DesignTokens\Exception\ResolverException;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ResolverInputs
{
    /**
     * @param array<string, string|int|float> $defaults
     * @param array<string, string|int|float> $overrides
     *
     * @return array<string, string|int|float>
     *
     * @throws ResolverException when the overrides name one modifier twice
     */
    public static function merge(array $defaults, array $overrides): array
    {
        $seen = [];
        foreach ($overrides as $name => $value) {
            $name = (string) $name;
            $normalized = strtolower($name);
            if (isset($seen[$normalized])) {
                throw new ResolverException([\sprintf('Modifier input "%s" is provided more than once with different casing.', $name)]);
            }
            $seen[$normalized] = true;

            foreach (array_keys($defaults) as $default) {
                if (0 === strcasecmp((string) $default, $name)) {
                    unset($defaults[$default]);
                }
            }
            $defaults[$name] = $value;
        }

        return $defaults;
    }

    /** @param array<array-key, mixed> $inputs */
    public static function key(array $inputs): string
    {
        $canonical = [];
        foreach ($inputs as $name => $value) {
            if (\is_int($value) || \is_float($value)) {
                $value = (string) $value;
            }
            $canonical[strtolower((string) $name)] = \is_string($value) ? strtolower($value) : $value;
        }
        ksort($canonical);

        return hash('xxh128', serialize($canonical));
    }
}
