<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Bundle;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\UX\Image\Tests\Isolation\IsolatedBootTrait;
use Symfony\UX\Image\UXImageBundle;

/**
 * Verifies UXImageBundle installs and boots when its optional packages are
 * absent (league/flysystem, intervention/image, doctrine/dbal,
 * symfony/ux-twig-component). Each case boots the bundle in a child process
 * with the relevant package hidden from the autoloader -- see
 * {@see IsolatedBootTrait} for why in-process isolation is not possible here.
 */
#[CoversClass(UXImageBundle::class)]
#[Group('isolation')]
final class MissingOptionalDependencyTest extends TestCase
{
    use IsolatedBootTrait;

    private const FLYSYSTEM = 'League\\Flysystem\\';
    private const INTERVENTION = 'Intervention\\Image\\';
    private const DOCTRINE_DBAL = 'Doctrine\\DBAL\\';
    private const TWIG_COMPONENT = 'Symfony\\UX\\TwigComponent\\';

    /**
     * @return array<string, mixed>
     */
    private static function processProfile(): array
    {
        // A single jpeg variant keeps the gd round-trip deterministic (no avif/webp
        // encoder assumptions) while still exercising store -> resize -> convert.
        return [
            'profiles' => [
                'isolation_test' => [
                    'formats' => ['jpeg'],
                    'variants' => [
                        'thumb' => ['width' => 32, 'mode' => 'fit', 'quality' => 80],
                    ],
                ],
            ],
        ];
    }

    public function testFlysystemStorageThrowsWhenLibraryMissing(): void
    {
        // Running counterpart to the previously skipped
        // UXImageExtensionTest::testLoadWithFlysystemStorageThrowsWhenPackageMissing.
        $result = $this->bootInIsolation([self::FLYSYSTEM], [
            'ux_image' => [
                'storages' => [
                    'media' => ['flysystem_service' => 'app.flysystem.media'],
                ],
            ],
        ]);

        self::assertSame('error', $result['status']);
        self::assertSame(InvalidConfigurationException::class, $result['class'] ?? null);
        self::assertStringContainsString('Flysystem', $result['message'] ?? '');
    }

    public function testBundleBootsWithoutFlysystemUsingLocalStorage(): void
    {
        // No Flysystem storage configured: the bundle falls back to the local
        // filesystem storage and boots cleanly without league/flysystem.
        $result = $this->bootInIsolation([self::FLYSYSTEM], [
            'action' => 'process',
            'ux_image' => self::processProfile(),
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertSame(['jpeg'], $result['process']['variant_formats'] ?? null);
    }

    public function testImagickDriverThrowsWhenInterventionMissing(): void
    {
        $result = $this->bootInIsolation([self::INTERVENTION], [
            'ux_image' => ['driver' => 'imagick'],
        ]);

        self::assertSame('error', $result['status']);
        self::assertSame(InvalidConfigurationException::class, $result['class'] ?? null);
        self::assertStringContainsString('intervention/image', $result['message'] ?? '');
    }

    public function testVipsDriverThrowsWhenInterventionMissing(): void
    {
        $result = $this->bootInIsolation([self::INTERVENTION], [
            'ux_image' => ['driver' => 'vips'],
        ]);

        self::assertSame('error', $result['status']);
        self::assertSame(InvalidConfigurationException::class, $result['class'] ?? null);
        self::assertStringContainsString('intervention/image', $result['message'] ?? '');
    }

    public function testBundleBootsAndProcessesWithoutInterventionOnGdDriver(): void
    {
        // intervention/image absent: the native gd processor still handles the
        // default "gd" driver, so an image can be stored, resized and rendered.
        $result = $this->bootInIsolation([self::INTERVENTION], [
            'action' => 'process',
            'ux_image' => self::processProfile(),
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertSame(['jpeg'], $result['process']['variant_formats'] ?? null);
        self::assertNotSame('', $result['process']['fallback_src'] ?? '');
    }

    public function testBundleBootsWithoutDoctrineDbal(): void
    {
        // doctrine/dbal absent: ImageAssetType is only registered when the
        // "doctrine" DI extension is present, so its registration is skipped
        // silently and nothing autoloads the DBAL type.
        $result = $this->bootInIsolation([self::DOCTRINE_DBAL]);

        self::assertSame('ok', $result['status'], $this->explain($result));
    }

    public function testBundleBootsWithoutTwigComponent(): void
    {
        // symfony/ux-twig-component absent: the companion bundle is not active,
        // so UXImageBundle does not import its component service.
        $result = $this->bootInIsolation([self::TWIG_COMPONENT]);

        self::assertSame('ok', $result['status'], $this->explain($result));
    }

    public function testTwigComponentRegisteredWhenPackagePresent(): void
    {
        // Positive counterpart: with nothing hidden TwigComponent's factory knows
        // the ux:image component, proving the gated registration actually wires it.
        $result = $this->bootInIsolation([]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertTrue($result['component_registered'] ?? false);
    }

    public function testTwigComponentNotRegisteredWhenBundleIsInactive(): void
    {
        $result = $this->bootInIsolation([], [
            'twig_component_enabled' => false,
            'has' => ['ux_image.twig.component'],
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        self::assertNull($result['component_registered'] ?? null);
        self::assertFalse($result['has']['ux_image.twig.component'] ?? false);
    }

    public function testBundleBootsAndProcessesWithNoOptionalPackages(): void
    {
        $result = $this->bootInIsolation([
            self::FLYSYSTEM,
            self::INTERVENTION,
            self::DOCTRINE_DBAL,
            self::TWIG_COMPONENT,
        ], [
            'action' => 'process',
            'ux_image' => self::processProfile(),
        ]);

        self::assertSame('ok', $result['status'], $this->explain($result));
        // Core path (Renderer + local Storage + gd Processor) still works with
        // every optional package absent.
        self::assertSame(['jpeg'], $result['process']['variant_formats'] ?? null);
        self::assertNotSame('', $result['process']['fallback_src'] ?? '');
    }

    /**
     * @param array{status: string, class?: string, message?: string} $result
     */
    private function explain(array $result): string
    {
        if ('ok' === $result['status']) {
            return '';
        }

        return \sprintf('isolated boot failed: %s: %s', $result['class'] ?? '?', $result['message'] ?? '');
    }
}
