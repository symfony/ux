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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\UXImageBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Twig\Environment;

/**
 * @requires class Symfony\Bundle\FrameworkBundle\FrameworkBundle
 */
#[CoversClass(UXImageBundle::class)]
#[RunTestsInSeparateProcesses]
final class BundleInitializationTest extends TestCase
{
    public function testBundleBoots()
    {
        $kernel = new UxImageTestKernel('test', true);
        $kernel->boot();

        $container = $kernel->getContainer();

        // The container compiled without errors, which is the main assertion
        self::assertNotNull($container);

        $kernel->shutdown();
    }

    public function testBundleSetsParameters()
    {
        $kernel = new UxImageTestKernel('test', true);
        $kernel->boot();

        $container = $kernel->getContainer();

        self::assertTrue($container->hasParameter('ux_image.driver'));
        self::assertSame('gd', $container->getParameter('ux_image.driver'));
        self::assertTrue($container->hasParameter('ux_image.default_sizes'));
        self::assertTrue($container->hasParameter('ux_image.preferred_formats'));
        self::assertTrue($container->hasParameter('ux_image.storages'));
        self::assertTrue($container->hasParameter('ux_image.profiles'));

        $kernel->shutdown();
    }

    public function testTwigComponentUsesRuntimeRenderer()
    {
        $kernel = new UxImageTestKernel('test', true);
        $kernel->boot();

        $twig = $kernel->getContainer()->get('test.service_container')->get('twig');
        self::assertInstanceOf(Environment::class, $twig);
        $template = $twig->createTemplate('<twig:ux:image :src="asset" alt="Photo" :lazy="false" id="hero" />');
        $html = $template->render([
            'asset' => new ImageAsset('default', 'https://example.com/photo.jpg', width: 1200, height: 800),
        ]);

        self::assertStringStartsWith('<picture>', $html);
        self::assertStringContainsString('src="https://example.com/photo.jpg"', $html);
        self::assertStringContainsString('alt="Photo"', $html);
        self::assertStringContainsString('loading="eager"', $html);
        self::assertStringContainsString('fetchpriority="high"', $html);
        self::assertStringContainsString('id="hero"', $html);

        $kernel->shutdown();
    }
}

class UxImageTestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new TwigBundle(),
            new TwigComponentBundle(),
            new UXImageBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(static function (ContainerBuilder $container) {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'test' => true,
                'http_method_override' => false,
                'handle_all_throwables' => true,
                'php_errors' => ['log' => true],
            ]);
            $container->loadFromExtension('twig_component', [
                'defaults' => [
                    'App\\Twig\\Components\\' => 'components/',
                ],
                'anonymous_template_directory' => 'components',
            ]);
            $container->loadFromExtension('ux_image', []);
        });
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/ux_image_test_'.spl_object_id($this).'/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/ux_image_test_'.spl_object_id($this).'/log';
    }
}
