<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class TurboExtension extends Extension
{
    /**
     * @param array<string, array> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = (new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')));
        $loader->load('services.php');

        if ($config['broadcast']['enabled'] && 80000 > \PHP_VERSION_ID) {
            throw new InvalidConfigurationException('Enabling the "broadcast" config option requires PHP 8 or higher.');
        }

        if (class_exists(TwigBundle::class)) {
            $loader->load('twig.php');
            if (isset($config['mercure']['subscribe_url'])) {
                $container->getDefinition('turbo.twig.extension.stream')->addArgument($config['mercure']['subscribe_url']);
            }

            if (class_exists(MercureBundle::class)) {
                $loader->load('broadcaster.php');
                $container
                    ->getDefinition('turbo.broadcaster.twig_mercure')
                    ->addArgument(null)
                    ->addArgument($config['broadcast']['entity_namespace'])
                ;
            }
        }

        if (class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class) && 80000 <= \PHP_VERSION_ID) {
            $loader->load('doctrine.php');
        }
    }
}
