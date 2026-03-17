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

class Configuration
{
    /**
     * @param ?array<string, mixed> $settings
     * @param ?array<Rule>          $rules
     */
    public function __construct(
        public ?array $settings = null,
        public ?array $rules = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'settings' => $this->settings,
            'rules' => null === $this->rules ? null : array_map(static fn (Rule $rule) => $rule->toArray(), $this->rules),
        ], static fn ($value) => null !== $value);
    }
}
