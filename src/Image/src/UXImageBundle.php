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
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Image\Bridge\Cloudflare\CloudflareProviderFactory;
use Symfony\UX\Image\Bridge\Imgix\ImgixProviderFactory;
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
        'imgix' => ['factory' => ImgixProviderFactory::class],
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
        $fits = array_column(Fit::cases(), 'value');

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
                ->arrayNode('presets')
                    ->info('Named sets of transformations, applied through the "preset" prop or option.')
                    ->useAttributeAsKey('name', false)
                    ->normalizeKeys(false)
                    ->beforeNormalization()
                        ->ifArray()
                        ->then(self::rejectKeyAttributes(...))
                    ->end()
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('width')->min(1)->end()
                            ->integerNode('height')->min(1)->end()
                            ->scalarNode('fit')
                                ->validate()
                                    // An env placeholder is validated as "", so "" passes here and ImagePresets rejects a literal one.
                                    ->ifNotInArray([...$fits, ''])
                                    ->thenInvalid('The value %s is not allowed, expected one of "'.implode('", "', $fits).'".')
                                ->end()
                            ->end()
                            ->stringNode('format')->cannotBeEmpty()->end()
                            ->integerNode('quality')->min(1)->max(100)->end()
                            ->arrayNode('operations')
                                ->useAttributeAsKey('provider')
                                ->normalizeKeys(false)
                                ->arrayPrototype()
                                    ->useAttributeAsKey('name')
                                    ->normalizeKeys(false)
                                    ->scalarPrototype()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
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
        $container->services()->get('.ux_image.presets')->arg(0, $config['presets']);

        foreach (self::$bridges as $name => $bridge) {
            if (ContainerBuilder::willBeAvailable('symfony/ux-'.$name.'-image', $bridge['factory'], ['symfony/ux-image'])) {
                $container->services()
                    ->set('ux_image.provider_factory.'.$name, $bridge['factory'])
                    ->tag('ux_image.provider_factory', ['provider' => $name]);
            }
        }
    }

    /**
     * Config keys a map by these attributes when it finds them, so a "name" option or a "provider" operation would silently rename its preset or provider.
     *
     * @param array<mixed> $presets
     *
     * @return array<mixed>
     */
    private static function rejectKeyAttributes(array $presets): array
    {
        foreach ($presets as $name => $preset) {
            if (\is_array($preset) && \array_key_exists('name', $preset)) {
                throw self::invalidConfiguration(\sprintf('ux_image.presets.%s', $name), 'Unrecognized option "name" under "%s".');
            }

            foreach (\is_array($preset['operations'] ?? null) ? $preset['operations'] : [] as $provider => $operations) {
                if (\is_array($operations) && \array_key_exists('provider', $operations)) {
                    throw self::invalidConfiguration(\sprintf('ux_image.presets.%s.operations.%s', $name, $provider), 'The operation name "provider" under "%s" is reserved.');
                }
            }
        }

        return $presets;
    }

    private static function invalidConfiguration(string $path, string $message): InvalidConfigurationException
    {
        $exception = new InvalidConfigurationException(\sprintf($message, $path));
        $exception->setPath($path);

        return $exception;
    }
}
