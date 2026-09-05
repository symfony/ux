<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

/**
 * Small helper to expose configured storages and profiles for debugging and
 * for potential profiler integration.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ConfigurationReporter
{
    /**
     * @param array<string, array<string, mixed>> $storages
     * @param array<string, array<string, mixed>> $profiles
     */
    public function __construct(
        private readonly array $storages,
        private readonly array $profiles,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function getStorageWarnings(): array
    {
        $warnings = [];

        foreach ($this->storages as $name => $storage) {
            /** @var array<string, mixed> $cdn */
            $cdn = $storage['cdn'] ?? [];
            if (isset($cdn['provider']) && empty($cdn['base_url'])) {
                $warnings[] = \sprintf('Storage "%s" sets a CDN provider but is missing "cdn.base_url".', $name);
            }
        }

        return $warnings;
    }

    /**
     * @return array<int, string>
     */
    public function getProfileWarnings(): array
    {
        $warnings = [];

        foreach ($this->profiles as $name => $profile) {
            if (isset($profile['formats']) && \is_array($profile['formats']) && [] === $profile['formats']) {
                $warnings[] = \sprintf('Profile "%s" declares no output formats.', $name);
            }

            if (!isset($profile['variants']) || [] === $profile['variants']) {
                $warnings[] = \sprintf('Profile "%s" declares no variants; responsive rendering will fall back to the original asset.', $name);
            }
        }

        return $warnings;
    }
}
