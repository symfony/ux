<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\DependencyInjection;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Symfony\UX\Turbo\Twig\TurboStreamListenRendererInterface;

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

        $loader = (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config')));
        $loader->load('services.php');
        $container->getDefinition('turbo.twig.extension')->replaceArgument(1, $config['default_transport']);

        $this->registerTwig($config, $container);
        $this->registerBroadcast($config, $container, $loader);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerTwig(array $config, ContainerBuilder $container): void
    {
        if (!class_exists(TwigBundle::class)) {
            return;
        }

        $container->getDefinition('turbo.broadcaster.action_renderer')
            ->replaceArgument(2, $config['broadcast']['entity_template_prefixes']);

        $container
            ->registerForAutoconfiguration(TurboStreamListenRendererInterface::class)
            ->addTag('turbo.renderer.stream_listen');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerBroadcast(array $config, ContainerBuilder $container, LoaderInterface $loader): void
    {
        if (!$config['broadcast']['enabled']) {
            $container->removeDefinition('turbo.twig.extension');
            $container->removeDefinition('turbo.doctrine.event_listener');

            return;
        }

        $container
            ->registerForAutoconfiguration(BroadcasterInterface::class)
            ->addTag('turbo.broadcaster')
        ;

        if (!$config['broadcast']['doctrine_orm']['enabled']) {
            $container->removeDefinition('turbo.doctrine.event_listener');

            return;
        }

        if (!class_exists(DoctrineBundle::class) || !interface_exists(EntityManagerInterface::class)) {
            throw new InvalidConfigurationException('You cannot use the Doctrine ORM integration as the Doctrine bundle is not installed. Try running "composer require symfony/orm-pack".');
        }
    }
}
