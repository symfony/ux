<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\DataCollector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Image\ConfigurationReporter;
use Symfony\UX\Image\DataCollector\ImageConfigurationCollector;

#[CoversClass(ImageConfigurationCollector::class)]
final class ImageConfigurationCollectorTest extends TestCase
{
    /**
     * @param array<string, array<string, mixed>> $storages
     * @param array<string, array<string, mixed>> $profiles
     */
    private function createCollector(array $storages = [], array $profiles = []): ImageConfigurationCollector
    {
        return new ImageConfigurationCollector(new ConfigurationReporter($storages, $profiles), $storages, $profiles);
    }

    #[Test]
    public function getNameReturnsTagId(): void
    {
        self::assertSame('ux_image.configuration', $this->createCollector()->getName());
    }

    #[Test]
    public function gettersReturnDefaultsBeforeCollect(): void
    {
        $collector = $this->createCollector(['s' => ['adapter_service' => 'x']], ['p' => []]);

        self::assertSame([], $collector->getStorages());
        self::assertSame([], $collector->getProfiles());
        self::assertSame([], $collector->getStorageWarnings());
        self::assertSame([], $collector->getProfileWarnings());
        self::assertFalse($collector->hasWarnings());
    }

    #[Test]
    public function collectSummarizesStorages(): void
    {
        $collector = $this->createCollector([
            'local_fallback' => ['adapter_service' => 'app.adapter', 'public_url_prefix' => '/media'],
            'remote' => [
                'flysystem_service' => 's3.storage',
                'cdn' => ['provider' => 'cloudinary', 'base_url' => 'https://cdn.example.com'],
            ],
        ]);

        $collector->collect(new Request(), new Response());

        $storages = $collector->getStorages();
        self::assertCount(2, $storages);

        self::assertSame('local_fallback', $storages[0]['name']);
        self::assertSame('adapter', $storages[0]['backend']);
        self::assertSame('app.adapter', $storages[0]['service']);
        self::assertSame('/media', $storages[0]['public_url_prefix']);
        self::assertNull($storages[0]['cdn_provider']);

        self::assertSame('flysystem', $storages[1]['backend']);
        self::assertSame('s3.storage', $storages[1]['service']);
        self::assertSame('cloudinary', $storages[1]['cdn_provider']);
    }

    #[Test]
    public function collectSummarizesProfiles(): void
    {
        $collector = $this->createCollector([], [
            'thumbnail' => [
                'processing' => 'deferred',
                'formats' => ['webp', 'jpeg'],
                'sizes' => '50vw',
                'variants' => ['small' => ['width' => 100], 'large' => ['width' => 400]],
            ],
        ]);

        $collector->collect(new Request(), new Response());

        $profiles = $collector->getProfiles();
        self::assertCount(1, $profiles);
        self::assertSame('thumbnail', $profiles[0]['name']);
        self::assertSame('deferred', $profiles[0]['processing']);
        self::assertSame(['webp', 'jpeg'], $profiles[0]['formats']);
        self::assertSame(2, $profiles[0]['variant_count']);
        self::assertSame('50vw', $profiles[0]['sizes']);
    }

    #[Test]
    public function collectPopulatesWarnings(): void
    {
        $collector = $this->createCollector(
            ['bad' => ['adapter_service' => 'x', 'cdn' => ['provider' => 'test', 'base_url' => '']]],
            ['broken' => ['formats' => [], 'variants' => []]],
        );

        $collector->collect(new Request(), new Response());

        self::assertNotEmpty($collector->getStorageWarnings());
        self::assertNotEmpty($collector->getProfileWarnings());
        self::assertTrue($collector->hasWarnings());
    }

    #[Test]
    public function collectWithCleanConfigProducesNoWarnings(): void
    {
        $collector = $this->createCollector(
            ['ok' => ['adapter_service' => 'x', 'public_url_prefix' => '/uploads']],
            ['thumb' => ['formats' => ['webp'], 'variants' => ['s' => ['width' => 100]]]],
        );

        $collector->collect(new Request(), new Response());

        self::assertSame([], $collector->getStorageWarnings());
        self::assertSame([], $collector->getProfileWarnings());
        self::assertFalse($collector->hasWarnings());
    }

    #[Test]
    public function resetClearsData(): void
    {
        $collector = $this->createCollector(['s' => ['adapter_service' => 'x']], ['p' => ['variants' => []]]);
        $collector->collect(new Request(), new Response());

        self::assertNotEmpty($collector->getStorages());

        $collector->reset();

        self::assertSame([], $collector->getStorages());
        self::assertSame([], $collector->getProfiles());
        self::assertSame([], $collector->getStorageWarnings());
    }
}
