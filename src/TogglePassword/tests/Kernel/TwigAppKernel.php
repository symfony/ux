<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TogglePassword\Tests\Kernel;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\TogglePassword\TogglePasswordBundle;

/**
 * @author Félix Eymonot <felix.eymonot@alximy.io>
 *
 * @internal
 */
class TwigAppKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [new FrameworkBundle(), new TwigBundle(), new TogglePasswordBundle()];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container) {
            $frameworkConfig = [
                'secret' => '$ecret',
                'test' => true,
                'http_method_override' => false,
                'php_errors' => [
                    'log' => true,
                ],
                ...(self::VERSION_ID >= 60200 ? [
                    'handle_all_throwables' => true,
                ] : []),
                ...(self::VERSION_ID >= 70300 ? [
                    'property_info' => ['with_constructor_extractor' => false],
                ] : []),
            ];

            $container->loadFromExtension('framework', $frameworkConfig);
            $container->loadFromExtension('twig', [
                'default_path' => __DIR__.'/templates',
                'strict_variables' => true,
                'exception_controller' => null,
                'debug' => '%kernel.debug%',
            ]);

            // create a public alias - FormFactoryInterface is removed otherwise
            $container->setAlias('public_form_factory', new Alias(FormFactoryInterface::class, true));
        });
    }

    public function getCacheDir(): string
    {
        return $this->createTmpDir('cache');
    }

    public function getLogDir(): string
    {
        return $this->createTmpDir('logs');
    }

    private function createTmpDir(string $type): string
    {
        $dir = sys_get_temp_dir().'/toggle_password_bundle/'.uniqid($type.'_', true);

        if (!file_exists($dir)) {
            mkdir($dir, 0o777, true);
        }

        return $dir;
    }
}
