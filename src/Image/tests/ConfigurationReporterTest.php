<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\ConfigurationReporter;

#[CoversClass(ConfigurationReporter::class)]
final class ConfigurationReporterTest extends TestCase
{
    #[Test]
    public function noWarningsWhenStoragesAreProperlyConfigured(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [
                'default' => ['public_url_prefix' => '/uploads'],
            ],
            profiles: [],
        );

        self::assertSame([], $reporter->getStorageWarnings());
    }

    #[Test]
    public function privateStorageDoesNotRequirePublicUrlPrefix(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [
                'images' => ['driver' => 'local'],
            ],
            profiles: [],
        );

        self::assertSame([], $reporter->getStorageWarnings());
    }

    #[Test]
    public function warningWhenCdnProviderSetWithoutBaseUrl(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [
                'cdn_storage' => [
                    'public_url_prefix' => '/uploads',
                    'cdn' => ['provider' => 'cloudflare', 'base_url' => ''],
                ],
            ],
            profiles: [],
        );

        $warnings = $reporter->getStorageWarnings();

        self::assertCount(1, $warnings);
        self::assertStringContainsString('Storage "cdn_storage"', $warnings[0]);
        self::assertStringContainsString('cdn.base_url', $warnings[0]);
    }

    #[Test]
    public function missingCdnBaseUrlIsTheOnlyStorageWarning(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [
                'broken' => [
                    'cdn' => ['provider' => 'cloudflare', 'base_url' => ''],
                ],
            ],
            profiles: [],
        );

        $warnings = $reporter->getStorageWarnings();

        self::assertCount(1, $warnings);
        self::assertStringContainsString('cdn.base_url', $warnings[0]);
    }

    #[Test]
    public function noCdnWarningWhenBaseUrlIsProvided(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [
                'ok' => [
                    'public_url_prefix' => '/uploads',
                    'cdn' => ['provider' => 'cloudflare', 'base_url' => 'https://cdn.example.com'],
                ],
            ],
            profiles: [],
        );

        self::assertSame([], $reporter->getStorageWarnings());
    }

    #[Test]
    public function multiplePrivateStoragesDoNotProduceWarnings(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [
                'a' => ['driver' => 'local'],
                'b' => ['driver' => 'local'],
            ],
            profiles: [],
        );

        self::assertSame([], $reporter->getStorageWarnings());
    }

    #[Test]
    public function emptyStoragesProduceNoWarnings(): void
    {
        $reporter = new ConfigurationReporter(storages: [], profiles: []);

        self::assertSame([], $reporter->getStorageWarnings());
    }

    #[Test]
    public function noProfileWarningsWhenProfilesAreProperlyConfigured(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [],
            profiles: [
                'thumbnail' => [
                    'formats' => ['webp'],
                    'variants' => ['small' => ['width' => 100]],
                ],
            ],
        );

        self::assertSame([], $reporter->getProfileWarnings());
    }

    #[Test]
    public function warningWhenProfileDeclaresEmptyFormats(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [],
            profiles: [
                'empty_formats' => [
                    'formats' => [],
                    'variants' => ['small' => ['width' => 100]],
                ],
            ],
        );

        $warnings = $reporter->getProfileWarnings();

        self::assertCount(1, $warnings);
        self::assertStringContainsString('Profile "empty_formats"', $warnings[0]);
        self::assertStringContainsString('no output formats', $warnings[0]);
    }

    #[Test]
    public function warningWhenProfileDeclaresNoVariants(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [],
            profiles: [
                'no_variants' => [
                    'formats' => ['webp'],
                    'variants' => [],
                ],
            ],
        );

        $warnings = $reporter->getProfileWarnings();

        self::assertCount(1, $warnings);
        self::assertStringContainsString('Profile "no_variants"', $warnings[0]);
        self::assertStringContainsString('no variants', $warnings[0]);
    }

    #[Test]
    public function warningWhenProfileMissingVariantsKey(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [],
            profiles: [
                'missing_key' => [
                    'formats' => ['webp'],
                ],
            ],
        );

        $warnings = $reporter->getProfileWarnings();

        self::assertCount(1, $warnings);
        self::assertStringContainsString('Profile "missing_key"', $warnings[0]);
        self::assertStringContainsString('no variants', $warnings[0]);
    }

    #[Test]
    public function bothProfileWarningsCanOccurSimultaneously(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [],
            profiles: [
                'broken' => [
                    'formats' => [],
                    'variants' => [],
                ],
            ],
        );

        $warnings = $reporter->getProfileWarnings();

        self::assertCount(2, $warnings);
        self::assertStringContainsString('no output formats', $warnings[0]);
        self::assertStringContainsString('no variants', $warnings[1]);
    }

    #[Test]
    public function emptyProfilesProduceNoWarnings(): void
    {
        $reporter = new ConfigurationReporter(storages: [], profiles: []);

        self::assertSame([], $reporter->getProfileWarnings());
    }

    #[Test]
    public function noFormatWarningWhenFormatsKeyIsMissing(): void
    {
        $reporter = new ConfigurationReporter(
            storages: [],
            profiles: [
                'no_formats_key' => [
                    'variants' => ['small' => ['width' => 100]],
                ],
            ],
        );

        $warnings = $reporter->getProfileWarnings();

        self::assertSame([], $warnings);
    }
}
