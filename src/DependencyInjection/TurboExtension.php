<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TurboExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $container->setParameter('turbo.mercure_subscribe_url', $config['mercure_subscribe_url']);

        $loader = (new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config')));
        $loader->load('services.php');

        if (class_exists(TwigBundle::class)) {
            $loader->load('twig.php');
        }

        if (class_exists(DoctrineBundle::class) && interface_exists(EntityManagerInterface::class)) {
            $loader->load('doctrine.php');
        }
    }
}
