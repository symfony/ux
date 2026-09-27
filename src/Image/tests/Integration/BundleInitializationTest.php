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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;
use Symfony\UX\Image\ImageTransformation;
use Symfony\UX\Image\Provider\ProviderInterface;
use Symfony\UX\Image\Tests\Fixtures\TestKernel;

/**
 * Boots a real, compiled and dumped container via {@see KernelTestCase}: a bare ContainerBuilder
 * hands "%env(...)%" placeholders an internal token, not the real value.
 *
 * GlideController is referenced here only by class name -- never autoloaded, since this package
 * never Composer-requires a bridge. The Glide bridge's own DI wiring is tested in its own suite.
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
        self::assertFalse(self::getContainer()->has('ux_image.provider_factory.glide'));
        self::assertFalse(self::getContainer()->has('ux_image.provider_factory.keycdn'));
        self::assertFalse(self::getContainer()->has(GlideController::class));
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

    public function testTheBundleWorksWithoutTwig(): void
    {
        self::bootKernel(['environment' => 'no_twig']);

        $container = self::getContainer();

        self::assertFalse($container->has('ux_image.twig_runtime'));
        self::assertFalse($container->has('.ux_image.twig_component.image'));
        self::assertSame('/uploads/og.jpg', $container->get('test.ux_image.url_generator')->generate('/uploads/og.jpg', 1200, 630));
    }

    public function testTheGlideRouteFileIsImportableWithoutTheGlideBridgeInstalled(): void
    {
        $loader = new PhpFileLoader(new FileLocator(\dirname(__DIR__, 2).'/config/routes'));
        $routes = $loader->load('glide.php');

        $route = $routes->get('ux_image_glide');

        self::assertNotNull($route);
        self::assertSame('/{path}', $route->getPath());
        self::assertSame(GlideController::class, $route->getDefault('_controller'));
        self::assertSame('.+', $route->getRequirement('path'));
        self::assertSame(['GET', 'HEAD'], $route->getMethods());
    }
}
