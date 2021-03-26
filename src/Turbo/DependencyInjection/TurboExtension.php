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
use Symfony\Bundle\MercureBundle\MercureBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Mercure\HubInterface;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Symfony\UX\Turbo\Mercure\Broadcaster;
use Symfony\UX\Turbo\Mercure\TurboStreamListenRenderer;
use Symfony\UX\Turbo\Twig\TurboStreamListenRendererInterface;
use Symfony\UX\Turbo\Twig\TwigExtension;

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
        $container->getDefinition(TwigExtension::class)->replaceArgument(1, $config['default_transport']);

        $this->registerTwig($container);
        $this->registerBroadcast($config, $container, $loader);
        $this->registerMercureTransports($config, $container, $loader);
    }

    private function registerTwig(ContainerBuilder $container): void
    {
        if (!class_exists(TwigBundle::class)) {
            $container->removeDefinition(TwigExtension::class);

            return;
        }

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
            $container->removeDefinition(BroadcasterInterface::class);

            return;
        }

        if (\PHP_VERSION_ID < 80000) {
            throw new InvalidConfigurationException('Enabling the "broadcast" configuration option requires PHP 8 or higher.');
        }

        $container
            ->registerForAutoconfiguration(BroadcasterInterface::class)
            ->addTag('turbo.broadcaster')
        ;

        if (!$config['broadcast']['doctrine_orm']['enabled']) {
            return;
        }

        if (!class_exists(DoctrineBundle::class) || !interface_exists(EntityManagerInterface::class)) {
            throw new InvalidConfigurationException('You cannot use the Doctrine ORM integration as the "doctrine/doctrine-bundle" package is not installed. Try running "composer require symfony/orm-pack".');
        }

        $loader->load('doctrine_orm.php');
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerMercureTransports(array $config, ContainerBuilder $container, LoaderInterface $loader): void
    {
        if (!$config['mercure']) {
            return;
        }

        if (!class_exists(MercureBundle::class)) {
            throw new InvalidConfigurationException('You cannot use the Mercure integration as the "symfony/mercure-bundle" package is not installed. Try running "composer require symfony/mercure-bundle".');
        }

        if (!class_exists(TwigBundle::class)) {
            throw new InvalidConfigurationException('You cannot use the Mercure integration as the "symfony/twig-bundle" package is not installed. Try running "composer require symfony/twig-pack".');
        }

        $loader->load('mercure.php');

        if (!$config['mercure']['hubs']) {
            // Wire the default Mercure hub
            $this->registerMercureTransport($container, $config, $config['default_transport'], HubInterface::class);

            return;
        }

        foreach ($config['mercure']['hubs'] as $hub) {
            $this->registerMercureTransport($container, $config, $hub, "mercure.hub.{$hub}");
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function registerMercureTransport(ContainerBuilder $container, array $config, string $name, string $hubId): void
    {
        $hubService = new Reference($hubId);

        $renderer = $container->setDefinition("turbo.mercure.{$name}.renderer", new ChildDefinition(TurboStreamListenRenderer::class));
        $renderer->replaceArgument(0, $hubService);
        $renderer->addTag('turbo.renderer.stream_listen', ['key' => $name]);

        if (!$config['broadcast']['enabled']) {
            return;
        }

        $broadcaster = $container->setDefinition("turbo.mercure.{$name}.broadcaster", new ChildDefinition(Broadcaster::class));
        $broadcaster->replaceArgument(0, $name);
        $broadcaster->replaceArgument(2, $hubService);
        $broadcaster->replaceArgument(5, $config['broadcast']['entity_namespace']);
        $broadcaster->addTag('turbo.broadcaster');
    }
}
