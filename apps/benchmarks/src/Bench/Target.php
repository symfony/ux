<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class Target
{
    /**
     * @param array<string, int> $minimumCounts
     */
    public function __construct(
        public readonly string $name,
        public readonly string $package,
        public readonly string $path,
        public readonly int $expectedStatus = 200,
        public readonly ?LiveScenario $scenario = null,
        public readonly array $minimumCounts = [],
    ) {
    }

    public function isLive(): bool
    {
        return null !== $this->scenario;
    }

    public function label(): string
    {
        return match ($this->name) {
            'css/tailwind' => 'Tailwind CSS (`/css/tailwind`)',
            'css/ux-css' => 'UX CSS (`/css/ux-css`)',
            default => \sprintf('`%s`', $this->name),
        };
    }

    public function matches(string $prefix): bool
    {
        return str_starts_with($this->name, $prefix);
    }
}
