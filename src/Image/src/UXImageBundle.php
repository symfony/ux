<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image;

use League\Glide\Server;
use League\Glide\Signatures\SignatureInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Image\Bridge\Cloudflare\CloudflareProviderFactory;
use Symfony\UX\Image\Bridge\Glide\Controller\GlideController;
use Symfony\UX\Image\Bridge\Glide\GlideProvider;
use Symfony\UX\Image\Bridge\Glide\GlideProviderFactory;
use Symfony\UX\Image\Bridge\Glide\ServerFactory as GlideServerFactory;
use Symfony\UX\Image\Bridge\Glide\SignatureFactory as GlideSignatureFactory;
use Symfony\UX\Image\Bridge\KeyCdn\KeyCdnProviderFactory;
use Symfony\UX\Image\Provider\NullProviderFactory;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class UXImageBundle extends AbstractBundle
{
    protected string $extensionAlias = 'ux_image';

    /**
     * @var array<string, array{factory: class-string}>
     *
     * @internal
     */
    public static array $bridges = [
        'cloudflare' => ['factory' => CloudflareProviderFactory::class],
        'glide' => ['factory' => GlideProviderFactory::class],
        'keycdn' => ['factory' => KeyCdnProviderFactory::class],
    ];

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('provider')->defaultNull()->end()
                ->arrayNode('formats')
                    ->scalarPrototype()->end()
                    ->defaultValue(['avif', 'webp', 'jpeg'])
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');
        $container->import('../config/twig_component.php');

        $config['provider'] ??= 'null://null';

        $container->services()
            ->set('ux_image.provider_factory.null', NullProviderFactory::class)
            ->tag('ux_image.provider_factory');

        // Not a "glide://" prefix check: the documented DSN is an unresolved %env() placeholder at compile time.
        if (ContainerBuilder::willBeAvailable('symfony/ux-glide-image', GlideController::class, ['symfony/ux-image'])) {
            $container->services()
                ->set('ux_image.glide.server', Server::class)
                    ->factory([GlideServerFactory::class, 'createFromDsn'])
                    ->args([$config['provider']])

                ->set('ux_image.glide.signature', SignatureInterface::class)
                    ->factory([GlideSignatureFactory::class, 'createFromDsn'])
                    ->args([$config['provider']])

                ->set(GlideController::class)
                    ->args([
                        '$server' => new Reference('ux_image.glide.server'),
                        '$signature' => new Reference('ux_image.glide.signature'),
                        '$supportedFormats' => array_values(array_intersect($config['formats'], GlideProvider::SUPPORTED_FORMATS)),
                    ])
                    ->tag('controller.service_arguments')
            ;
        }

        $container->services()->get('ux_image.provider')->arg(0, $config['provider']);
        $container->services()->get('ux_image.renderer')->arg(2, $config['formats']);

        foreach (self::$bridges as $name => $bridge) {
            if (ContainerBuilder::willBeAvailable('symfony/ux-'.$name.'-image', $bridge['factory'], ['symfony/ux-image'])) {
                $container->services()
                    ->set('ux_image.provider_factory.'.$name, $bridge['factory'])
                    ->tag('ux_image.provider_factory');
            }
        }
    }
}
