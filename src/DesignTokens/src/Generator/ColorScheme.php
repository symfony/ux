<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Generator;

use Symfony\UX\DesignTokens\Resolver\ResolverInputs;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class ColorScheme
{
    public function __construct(
        public readonly string $modifier = 'scheme',
        public readonly string $light = 'light',
        public readonly string $dark = 'dark',
    ) {
    }

    /**
     * Null when there is only one resolution.
     *
     * @param array<string, array{contexts: list<string>, default: string|null}> $modifiers as TokenResolverInterface::getModifiers() returns them
     * @param array<string, string|int|float>                                    $inputs
     *
     * @return array{array<string, string|int|float>, array<string, string|int|float>}|null
     */
    public function contexts(array $modifiers, array $inputs = []): ?array
    {
        foreach (array_keys($inputs) as $name) {
            if (0 === strcasecmp((string) $name, $this->modifier)) {
                return null;
            }
        }

        foreach ($modifiers as $name => $modifier) {
            if (0 !== strcasecmp((string) $name, $this->modifier)) {
                continue;
            }
            $contexts = array_map(strtolower(...), $modifier['contexts']);
            if (!\in_array(strtolower($this->light), $contexts, true) || !\in_array(strtolower($this->dark), $contexts, true)) {
                return null;
            }

            return [
                ResolverInputs::merge($inputs, [$this->modifier => $this->light]),
                ResolverInputs::merge($inputs, [$this->modifier => $this->dark]),
            ];
        }

        return null;
    }
}
