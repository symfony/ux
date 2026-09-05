<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\UX\Image\ConfigurationReporter;

/**
 * Surfaces the bundle configuration the way ux-image actually sees it: the
 * resolved storages and profiles, plus any configuration warnings. This is the
 * information needed to answer "why is my image not rendering / not stored where
 * I expect" from the profiler.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class ImageConfigurationCollector extends DataCollector
{
    /**
     * @param array<string, array<string, mixed>> $storages
     * @param array<string, array<string, mixed>> $profiles
     */
    public function __construct(
        private readonly ConfigurationReporter $reporter,
        private readonly array $storages,
        private readonly array $profiles,
    ) {
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['storages'] = $this->summarizeStorages();
        $this->data['profiles'] = $this->summarizeProfiles();
        $this->data['storage_warnings'] = $this->reporter->getStorageWarnings();
        $this->data['profile_warnings'] = $this->reporter->getProfileWarnings();
    }

    public function reset(): void
    {
        $this->data = [];
    }

    public function getName(): string
    {
        return 'ux_image.configuration';
    }

    /**
     * @return array<int, array{name: string, backend: string, service: ?string, public_url_prefix: ?string, cdn_provider: ?string}>
     */
    public function getStorages(): array
    {
        /** @var array<int, array{name: string, backend: string, service: ?string, public_url_prefix: ?string, cdn_provider: ?string}> $storages */
        $storages = $this->data['storages'] ?? [];

        return $storages;
    }

    /**
     * @return array<int, array{name: string, processing: string, formats: array<int, string>, variant_count: int, sizes: ?string}>
     */
    public function getProfiles(): array
    {
        /** @var array<int, array{name: string, processing: string, formats: array<int, string>, variant_count: int, sizes: ?string}> $profiles */
        $profiles = $this->data['profiles'] ?? [];

        return $profiles;
    }

    /**
     * @return array<int, string>
     */
    public function getStorageWarnings(): array
    {
        /** @var array<int, string> $warnings */
        $warnings = $this->data['storage_warnings'] ?? [];

        return $warnings;
    }

    /**
     * @return array<int, string>
     */
    public function getProfileWarnings(): array
    {
        /** @var array<int, string> $warnings */
        $warnings = $this->data['profile_warnings'] ?? [];

        return $warnings;
    }

    public function hasWarnings(): bool
    {
        return [] !== $this->getStorageWarnings() || [] !== $this->getProfileWarnings();
    }

    /**
     * @return array<int, array{name: string, backend: string, service: ?string, public_url_prefix: ?string, cdn_provider: ?string}>
     */
    private function summarizeStorages(): array
    {
        $summaries = [];
        foreach ($this->storages as $name => $storage) {
            $flysystem = \is_string($storage['flysystem_service'] ?? null) ? $storage['flysystem_service'] : null;
            $adapter = \is_string($storage['adapter_service'] ?? null) ? $storage['adapter_service'] : null;
            $cdn = \is_array($storage['cdn'] ?? null) ? $storage['cdn'] : [];

            $summaries[] = [
                'name' => (string) $name,
                'backend' => null !== $flysystem ? 'flysystem' : (null !== $adapter ? 'adapter' : 'local'),
                'service' => $flysystem ?? $adapter,
                'public_url_prefix' => \is_string($storage['public_url_prefix'] ?? null) ? $storage['public_url_prefix'] : null,
                'cdn_provider' => \is_string($cdn['provider'] ?? null) ? $cdn['provider'] : null,
            ];
        }

        return $summaries;
    }

    /**
     * @return array<int, array{name: string, processing: string, formats: array<int, string>, variant_count: int, sizes: ?string}>
     */
    private function summarizeProfiles(): array
    {
        $summaries = [];
        foreach ($this->profiles as $name => $profile) {
            $formats = \is_array($profile['formats'] ?? null) ? array_values(array_filter($profile['formats'], 'is_string')) : [];
            $variants = \is_array($profile['variants'] ?? null) ? $profile['variants'] : [];

            $summaries[] = [
                'name' => (string) $name,
                'processing' => \is_string($profile['processing'] ?? null) ? $profile['processing'] : 'immediate',
                'formats' => $formats,
                'variant_count' => \count($variants),
                'sizes' => \is_string($profile['sizes'] ?? null) ? $profile['sizes'] : null,
            ];
        }

        return $summaries;
    }
}
