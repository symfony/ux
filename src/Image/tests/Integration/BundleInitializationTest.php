<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Tests\Fixtures\TestKernel;

/**
 * Boots a real, compiled and dumped container via {@see KernelTestCase}: a bare ContainerBuilder
 * hands "%env(...)%" placeholders an internal token, not the real value.
 */
final class BundleInitializationTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        putenv('UX_IMAGE_DSN');
        unset($_ENV['UX_IMAGE_DSN'], $_SERVER['UX_IMAGE_DSN']);
    }

    public function testTheContainerCompilesWithNoBridgeInstalledAndTheNullProviderIsActive(): void
    {
        self::bootKernel(['environment' => 'bare']);

        /** @var ProviderInterface $provider */
        $provider = self::getContainer()->get('ux_image.provider');

        self::assertSame('null', $provider->getName());

        self::assertSame('hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg')));

        self::assertFalse(self::getContainer()->has('ux_image.provider_factory.cloudflare'));
        self::assertFalse(self::getContainer()->has('ux_image.provider_factory.cloudinary'));
        self::assertFalse(self::getContainer()->has('ux_image.provider_factory.keycdn'));
    }

    public function testTheContainerCompilesWithABridgeAvailableAndItsProviderBecomesActive(): void
    {
        self::bootKernel(['environment' => 'test']);

        /** @var ProviderInterface $provider */
        $provider = self::getContainer()->get('ux_image.provider');

        self::assertSame('fake', $provider->getName());
    }

    public function testAnEnvPlaceholderDsnResolvingToTheNullSchemeStillReachesTheNullProvider(): void
    {
        putenv('UX_IMAGE_DSN=null://null');
        $_ENV['UX_IMAGE_DSN'] = 'null://null';
        $_SERVER['UX_IMAGE_DSN'] = 'null://null';

        self::bootKernel(['environment' => 'env_placeholder']);

        /** @var ProviderInterface $provider */
        $provider = self::getContainer()->get('ux_image.provider');

        self::assertSame('null', $provider->getName());
    }

    public function testAnUnsetDefaultingEnvPlaceholderDsnFallsBackToTheNullProviderAtRuntime(): void
    {
        self::bootKernel(['environment' => 'default_placeholder']);

        /** @var ProviderInterface $provider */
        $provider = self::getContainer()->get('ux_image.provider');

        self::assertSame('null', $provider->getName());
        self::assertSame('hero.jpg', $provider->generateUrl(new ImageTransformation('hero.jpg')));
    }

    public function testAnUnresolvedEnvPlaceholderDsnStillResolvesTheActiveProviderAtRuntime(): void
    {
        putenv('UX_IMAGE_DSN=fake://default');
        $_ENV['UX_IMAGE_DSN'] = 'fake://default';
        $_SERVER['UX_IMAGE_DSN'] = 'fake://default';

        self::bootKernel(['environment' => 'env_placeholder']);

        /** @var ProviderInterface $provider */
        $provider = self::getContainer()->get('ux_image.provider');

        self::assertSame('fake', $provider->getName());
    }

    public function testAPresetOperationsKeyNamingNoInstalledProviderFailsAtCompileTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "ux_image.presets.hero.operations" option has a "cloudfare" key, which is not an installed image provider (installed: "fake", "null").');

        self::bootKernel(['environment' => 'preset_typo']);
    }

    public function testAPresetFitCanComeFromAnEnvVar(): void
    {
        $_SERVER['UX_IMAGE_FIT'] = 'contain';

        try {
            self::bootKernel(['environment' => 'preset_env_fit']);

            $url = self::getContainer()->get('test.ux_image.url_generator')->generate('/hero.jpg', preset: 'thumbnail');
        } finally {
            unset($_SERVER['UX_IMAGE_FIT']);
        }

        self::assertStringContainsString('fit=contain', $url);
    }

    public function testTheBundleWorksWithoutTwig(): void
    {
        self::bootKernel(['environment' => 'no_twig']);

        $container = self::getContainer();

        self::assertFalse($container->has('ux_image.twig_runtime'));
        self::assertFalse($container->has('.ux_image.twig_component.image'));
        self::assertSame('/uploads/og.jpg', $container->get('test.ux_image.url_generator')->generate('/uploads/og.jpg', 1200, 630));
    }
}
