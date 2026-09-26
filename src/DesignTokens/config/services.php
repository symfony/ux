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

use Symfony\Component\Yaml\Yaml;
use Symfony\UX\DesignTokens\Bridge\GoogleDesignMd\DesignMdGenerator;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeGenerator;
use Symfony\UX\DesignTokens\Bridge\Tailwind\ThemeImporter;
use Symfony\UX\DesignTokens\CacheWarmer\DesignTokensCacheWarmer;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Command\DebugTokensCommand;
use Symfony\UX\DesignTokens\Command\ExportCommand;
use Symfony\UX\DesignTokens\Command\ImportCommand;
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

return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('.ux_design_tokens.document_loader', JsonDocumentLoader::class)
            ->args([
                param('kernel.project_dir'),
                param('.ux_design_tokens.allowed_roots'),
            ])

        ->alias(DocumentLoaderInterface::class, '.ux_design_tokens.document_loader')

        // Stateful: each consumer needs its own instance.
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

        ->set('.ux_design_tokens.importer.tailwind', ThemeImporter::class)
            ->tag('ux_design_tokens.importer', ['format' => 'tailwind'])

        ->set('.ux_design_tokens.generator.dtcg', DtcgGenerator::class)
            ->tag('ux_design_tokens.generator', ['format' => 'dtcg'])

        ->set('.ux_design_tokens.generator.css', CssGenerator::class)
            ->args([
                param('.ux_design_tokens.css_prefix'),
            ])
            ->tag('ux_design_tokens.generator', ['format' => 'css'])

        ->set('.ux_design_tokens.generator.javascript', JavaScriptGenerator::class)
            ->tag('ux_design_tokens.generator', ['format' => 'javascript'])

        ->set('.ux_design_tokens.generator.tailwind', ThemeGenerator::class)
            ->tag('ux_design_tokens.generator', ['format' => 'tailwind'])

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

        ->set('.ux_design_tokens.command.import', ImportCommand::class)
            ->args([
                tagged_locator('ux_design_tokens.importer', 'format'),
                service('.ux_design_tokens.token_tree_builder'),
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

    if (class_exists(Yaml::class)) {
        $container->services()
            ->set('.ux_design_tokens.generator.design_md', DesignMdGenerator::class)
                ->tag('ux_design_tokens.generator', ['format' => 'design.md'])
        ;
    }
};
