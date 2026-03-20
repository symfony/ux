<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Configuration;

class Rule
{
    /**
     * @param array<string>        $patterns
     * @param array<string, mixed> $properties
     */
    public function __construct(
        public ?array $patterns = null,
        public ?array $properties = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'patterns' => $this->patterns,
            'properties' => $this->properties,
        ], static fn ($value) => null !== $value);
    }
}
