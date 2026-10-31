<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\UX\Css\CacheWarmer\StylesheetCacheWarmer;
use Symfony\UX\Css\DependencyInjection\Compiler\ReferenceDumpPass;
use Symfony\UX\Css\DependencyInjection\Compiler\TemplateIteratorPass;
use Symfony\UX\Css\DependencyInjection\PandaConfigConverter;
use Symfony\UX\Css\Dumper\StylesheetDumper;
use Symfony\UX\Css\Dumper\TemplateScanner;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\PandaConfig;
use Symfony\UX\Css\Engine\StaticCss;
use Symfony\UX\Css\EventListener\StylesheetListener;
use Symfony\UX\Css\Exception\InvalidStyleException;
use Symfony\UX\Css\Twig\CssExtension;
use Symfony\UX\Css\Twig\CssNodeVisitor;
use Symfony\UX\Css\Twig\CssRuntime;
use Symfony\UX\Css\Validation\StyleValidator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service_closure;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class UXCssBundle extends AbstractBundle
{
    private const DEFAULT_TOKENS = __DIR__.'/../resources/panda-tokens.json';

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new TemplateIteratorPass());
        if ($container->getParameter('kernel.debug')) {
            $container->addCompilerPass(new ReferenceDumpPass());
        }
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->enumNode('default_tokens')
                    ->info('"panda" adds the default tokens of Panda CSS under the tokens of the project.')
                    ->values([null, 'panda'])
                    ->defaultNull()
                ->end()
                ->arrayNode('tokens')
                    ->info('Raw values, grouped by category: colors, spacing, sizes, radii, fontSizes, fontWeights, lineHeights, fonts, shadows, zIndex, durations, easings.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('category')
                    ->variablePrototype()->end()
                ->end()
                ->arrayNode('semantic_tokens')
                    ->info('References to other tokens, like "{colors.blue.500}". A value with a "base" key changes with the conditions it lists.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('category')
                    ->variablePrototype()->end()
                ->end()
                ->arrayNode('conditions')
                    ->info('Added to Panda\'s default conditions, or replacing the one with the same name. A selector with "&", or an at-rule.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->variablePrototype()->end()
                ->end()
                ->arrayNode('breakpoints')
                    ->info('Minimum widths, replacing Panda\'s default breakpoints.')
                    ->normalizeKeys(false)
                    ->useAttributeAsKey('name')
                    ->scalarPrototype()->end()
                ->end()
                ->booleanNode('strict_tokens')
                    ->info('Only accept tokens for properties bound to a token category; raw values must be written between brackets.')
                    ->defaultTrue()
                ->end()
                ->booleanNode('strict_property_values')
                    ->info('Only accept the keywords of a property whose grammar is made of keywords.')
                    ->defaultTrue()
                ->end()
                ->arrayNode('static_css')
                    ->info('Values to write in the stylesheet even when no template uses them as is, for css() calls with dynamic values.')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('css')
                            ->arrayPrototype()
                                ->children()
                                    ->arrayNode('properties')
                                        ->info('The values of each property, or "*" for every value it declares.')
                                        ->normalizeKeys(false)
                                        ->useAttributeAsKey('name')
                                        ->arrayPrototype()
                                            ->beforeNormalization()->castToArray()->end()
                                            ->scalarPrototype()->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('conditions')
                                        ->info('Conditions and breakpoints each value is also written for.')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->booleanNode('responsive')
                                        ->info('Also write each value for every breakpoint.')
                                        ->defaultFalse()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!self::isAssetMapperAvailable($builder)) {
            return;
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => ['%kernel.project_dir%/var/ux_css' => 'ux_css'],
            ],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if ('panda' === $config['default_tokens']) {
            $defaults = json_decode(file_get_contents(self::DEFAULT_TOKENS), true, flags: \JSON_THROW_ON_ERROR);
            $config['tokens'] = PandaConfigConverter::mergeTokens($defaults['tokens'], $config['tokens']);
        }
        unset($config['default_tokens']);

        $project = PandaConfigConverter::convert($config);
        $staticRules = $config['static_css']['css'];
        self::validateStaticCss($project, $staticRules, $config['strict_tokens'], $config['strict_property_values']);
        $strictness = [$config['strict_tokens'], $config['strict_property_values']];
        $configHash = hash('xxh128', serialize([$project, $strictness, $staticRules]));

        $debug = $builder->getParameter('kernel.debug');
        $services = $container->services();
        $services
            ->set('ux_css.class_name_generator', ClassNameGenerator::class)
                ->args(array_values(ClassNameGenerator::fromPandaConfig(PandaConfig::create($project))->toArray()))
            ->set('ux_css.engine', Engine::class)
                ->factory([Engine::class, 'fromProjectConfig'])
                ->args([$project])
            ->set('ux_css.css_generator', CssGenerator::class)
                ->args([service('ux_css.engine')])
            ->set('ux_css.validator', StyleValidator::class)
                ->args([service('ux_css.engine'), $config['strict_tokens'], $config['strict_property_values']])
            ->set('ux_css.twig.runtime', CssRuntime::class)
                ->args([
                    service('ux_css.class_name_generator'),
                    $debug ? service_closure('ux_css.validator') : null,
                    $debug ? '%kernel.cache_dir%/ux_css/classes.json' : null,
                    $debug ? service('logger')->nullOnInvalid() : null,
                ])
                ->tag('twig.runtime')
                ->tag('monolog.logger', ['channel' => 'ux_css'])
            ->set('ux_css.twig.node_visitor', CssNodeVisitor::class)
                ->args([service_closure('ux_css.engine'), service_closure('ux_css.validator')])
            ->set('ux_css.twig.extension', CssExtension::class)
                ->args([service('ux_css.twig.node_visitor')])
                ->tag('twig.extension');

        if (!isset($builder->getParameter('kernel.bundles')['TwigBundle'])) {
            return;
        }

        $services
            ->set('ux_css.static_css', StaticCss::class)
                ->args([service('ux_css.engine')])
            ->set('ux_css.template_scanner', TemplateScanner::class)
                ->args([service('twig'), service('ux_css.twig.node_visitor')])
            ->set('ux_css.stylesheet_dumper', StylesheetDumper::class)
                ->args([
                    service('ux_css.template_scanner'),
                    service('ux_css.css_generator'),
                    service('twig'),
                    service_closure('twig.template_iterator'),
                    '%kernel.cache_dir%/ux_css/index.json',
                    '%kernel.project_dir%/var/ux_css/styles.css',
                    '%kernel.debug%',
                    service('ux_css.static_css'),
                    $staticRules,
                    '%kernel.cache_dir%/ux_css/classes.json',
                    $configHash,
                ])
            ->set('ux_css.cache_warmer', StylesheetCacheWarmer::class)
                ->args([service('ux_css.stylesheet_dumper')])
                ->tag('kernel.cache_warmer');

        if ($debug) {
            $services
                ->set('ux_css.stylesheet_listener', StylesheetListener::class)
                    ->args([service('ux_css.stylesheet_dumper')])
                    ->tag('kernel.event_listener', ['event' => 'kernel.request', 'priority' => 64]);
        }
    }

    /**
     * @param array<string, mixed>       $project
     * @param list<array<string, mixed>> $rules
     */
    private static function validateStaticCss(
        array $project,
        array $rules,
        bool $strictTokens,
        bool $strictPropertyValues,
    ): void {
        if ([] === $rules) {
            return;
        }

        $engine = Engine::fromProjectConfig($project);
        $validator = new StyleValidator($engine, $strictTokens, $strictPropertyValues);
        foreach (new StaticCss($engine)->styles($rules) as $styles) {
            try {
                $validator->validate($styles);
            } catch (InvalidStyleException $e) {
                $message = 'The ux_css.static_css rules are invalid: '.$e->getMessage();

                throw new InvalidConfigurationException($message, 0, $e);
            }
        }
    }

    private static function isAssetMapperAvailable(ContainerBuilder $builder): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        $bundles = $builder->getParameter('kernel.bundles_metadata');
        if (!isset($bundles['FrameworkBundle'])) {
            return false;
        }

        return isset($bundles['AssetMapperBundle'])
            || is_file($bundles['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
