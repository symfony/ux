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

use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Image\Bridge\Cloudflare\CloudflareProviderFactory;
use Symfony\UX\Image\Bridge\KeyCdn\KeyCdnProviderFactory;
use Symfony\UX\Image\DependencyInjection\ProviderNamesPass;
use Symfony\UX\Image\Provider\NullProviderFactory;
use Symfony\UX\Image\Renderer\LayoutResolver;
use Symfony\UX\TwigComponent\ComponentAttributes;

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
        'keycdn' => ['factory' => KeyCdnProviderFactory::class],
    ];

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ProviderNamesPass());
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
                ->arrayNode('resolutions')
                    ->integerPrototype()->end()
                    ->defaultValue(LayoutResolver::DEFAULT_RESOLUTIONS)
                ->end()
                ->integerNode('quality')
                    ->info('The quality of every generated image that sets none itself; null leaves it to the provider.')
                    ->min(1)
                    ->max(100)
                    ->defaultNull()
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        if (ContainerBuilder::willBeAvailable('symfony/twig-bundle', TwigBundle::class, ['symfony/ux-image'])
            && ContainerBuilder::willBeAvailable('symfony/ux-twig-component', ComponentAttributes::class, ['symfony/ux-image'])) {
            $container->import('../config/twig.php');
            $container->import('../config/twig_component.php');
        }

        $config['provider'] ??= 'null://null';

        $container->services()
            ->set('ux_image.provider_factory.null', NullProviderFactory::class)
            ->tag('ux_image.provider_factory', ['provider' => 'null']);

        $container->services()->get('ux_image.provider')->arg(0, $config['provider']);
        $container->services()->get('ux_image.renderer')->arg(2, $config['formats']);
        $container->services()->get('ux_image.layout_resolver')->arg(0, $config['resolutions']);
        $container->services()->get('ux_image.url_generator')->arg('$defaultQuality', $config['quality']);

        foreach (self::$bridges as $name => $bridge) {
            if (ContainerBuilder::willBeAvailable('symfony/ux-'.$name.'-image', $bridge['factory'], ['symfony/ux-image'])) {
                $container->services()
                    ->set('ux_image.provider_factory.'.$name, $bridge['factory'])
                    ->tag('ux_image.provider_factory', ['provider' => $name]);
            }
        }
    }
}
