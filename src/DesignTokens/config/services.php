<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\DesignTokens\CacheWarmer\DesignTokensCacheWarmer;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Command\DebugTokensCommand;
use Symfony\UX\DesignTokens\Command\ExportCommand;
use Symfony\UX\DesignTokens\Command\LintDesignTokensCommand;
use Symfony\UX\DesignTokens\Generator\ColorScheme;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Generator\DtcgGenerator;
use Symfony\UX\DesignTokens\Generator\JavaScriptGenerator;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\DocumentLoaderInterface;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\TokenResolverInterface;
use Symfony\UX\DesignTokens\Resolver\TokenTreeBuilder;
use Symfony\UX\DesignTokens\TokenRegistry;
use Symfony\UX\DesignTokens\TokenRegistryInterface;
use Symfony\UX\DesignTokens\Validation\DtcgValidator;
use Symfony\UX\DesignTokens\Validation\Normalizer;

/*
 * Internal services use dot-prefixed ids so they stay out of reach of the
 * application. What an application is meant to consume is exposed through an
 * alias on its class name.
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_design_tokens.document_loader', JsonDocumentLoader::class)
            ->args([
                param('kernel.project_dir'),
                param('.ux_design_tokens.allowed_roots'),
            ])

        ->alias(DocumentLoaderInterface::class, '.ux_design_tokens.document_loader')

        // A resolution is a stateful operation: the resolver records the
        // documents it pulled in, the origin of every path and the references
        // it is walking. Handing the same instance to two consumers would let
        // one wipe what the other is still reading, so every injection point
        // gets its own.
        ->set('.ux_design_tokens.token_tree_builder', TokenTreeBuilder::class)
            ->share(false)
            ->args([
                service(DocumentLoaderInterface::class),
            ])

        ->set('.ux_design_tokens.validator', DtcgValidator::class)
            ->args([
                service('.ux_design_tokens.token_tree_builder'),
                null,
                service(DocumentLoaderInterface::class),
            ])

        ->set('.ux_design_tokens.normalizer', Normalizer::class)
            ->args([
                service('.ux_design_tokens.validator'),
            ])

        ->set('.ux_design_tokens.resolver.configured', ConfiguredTokenResolver::class)
            ->args([
                service(DocumentLoaderInterface::class),
                param('.ux_design_tokens.paths'),
                param('.ux_design_tokens.resolver_path'),
                service('.ux_design_tokens.cache')->nullOnInvalid(),
                param('kernel.debug'),
            ])

        ->alias('.ux_design_tokens.resolver', '.ux_design_tokens.resolver.configured')

        ->alias(TokenResolverInterface::class, '.ux_design_tokens.resolver')

        ->set('.ux_design_tokens.registry', TokenRegistry::class)
            ->args([
                service('.ux_design_tokens.resolver'),
                param('.ux_design_tokens.resolver_inputs'),
            ])
            ->tag('kernel.reset', ['method' => 'reset'])

        ->alias(TokenRegistryInterface::class, '.ux_design_tokens.registry')

        // Built-in export formats. Any other service implementing
        // GeneratorInterface joins them through the tag the bundle registers
        // for autoconfiguration.
        ->set('.ux_design_tokens.generator.dtcg', DtcgGenerator::class)
            ->tag('ux_design_tokens.generator', ['format' => 'dtcg'])

        ->set('.ux_design_tokens.generator.css', CssGenerator::class)
            ->args([
                param('.ux_design_tokens.css_prefix'),
            ])
            ->tag('ux_design_tokens.generator', ['format' => 'css'])

        ->set('.ux_design_tokens.generator.javascript', JavaScriptGenerator::class)
            ->tag('ux_design_tokens.generator', ['format' => 'javascript'])

        ->set('.ux_design_tokens.color_scheme', ColorScheme::class)
            ->args([
                param('.ux_design_tokens.color_scheme.modifier'),
                param('.ux_design_tokens.color_scheme.light'),
                param('.ux_design_tokens.color_scheme.dark'),
            ])

        ->set('.ux_design_tokens.stylesheet_cache', StylesheetCache::class)
            ->args([
                service('.ux_design_tokens.resolver'),
                service('.ux_design_tokens.generator.css'),
                param('kernel.build_dir'),
                param('.ux_design_tokens.resolver_inputs'),
                service('.ux_design_tokens.color_scheme'),
                param('kernel.debug'),
                abstract_arg('fingerprint of the configuration, set by the bundle'),
            ])

        ->set('.ux_design_tokens.cache_warmer', DesignTokensCacheWarmer::class)
            ->args([
                service('.ux_design_tokens.stylesheet_cache'),
            ])
            ->tag('kernel.cache_warmer')

        ->set('.ux_design_tokens.command.export', ExportCommand::class)
            ->args([
                service('.ux_design_tokens.registry'),
                tagged_locator('ux_design_tokens.generator', 'format'),
                param('.ux_design_tokens.css_prefix'),
                service('.ux_design_tokens.color_scheme'),
            ])
            ->tag('console.command')

        ->set('.ux_design_tokens.command.lint', LintDesignTokensCommand::class)
            ->args([
                service('.ux_design_tokens.validator'),
                service('.ux_design_tokens.normalizer'),
                service('.ux_design_tokens.registry'),
                param('.ux_design_tokens.paths'),
                param('.ux_design_tokens.resolver_path'),
            ])
            ->tag('console.command')

        ->set('.ux_design_tokens.command.debug', DebugTokensCommand::class)
            ->args([
                service('.ux_design_tokens.registry'),
                service('.ux_design_tokens.resolver.configured'),
                param('.ux_design_tokens.resolver_inputs'),
            ])
            ->tag('console.command')
    ;
};
