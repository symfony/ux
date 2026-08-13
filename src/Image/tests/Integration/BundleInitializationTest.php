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
use Symfony\UX\Image\UXImageBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;

/**
 * @requires class Symfony\Bundle\FrameworkBundle\FrameworkBundle
 */
#[CoversClass(UXImageBundle::class)]
#[RunTestsInSeparateProcesses]
final class BundleInitializationTest extends TestCase
{
    public function testBundleBoots(): void
    {
        $kernel = new UxImageTestKernel('test', true);
        $kernel->boot();

        $container = $kernel->getContainer();

        // The container compiled without errors, which is the main assertion
        self::assertNotNull($container);

        $kernel->shutdown();
    }

    public function testBundleSetsParameters(): void
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
