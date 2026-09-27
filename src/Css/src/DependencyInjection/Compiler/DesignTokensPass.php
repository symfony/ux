<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\UX\Css\DependencyInjection\DesignTokensConverter;
use Symfony\UX\Css\DependencyInjection\PandaConfigConverter;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Engine\PandaConfig;
use Symfony\UX\Css\Engine\StaticCss;
use Symfony\UX\Css\Exception\InvalidStyleException;
use Symfony\UX\Css\Validation\StyleValidator;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Resolver\JsonDocumentLoader;
use Symfony\UX\DesignTokens\Token\TokenInterface;
use Symfony\UX\DesignTokens\TokenTree;

/**
 * Builds the Panda config of the engine from the design tokens UX Design Tokens resolves.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class DesignTokensPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('.ux_css.config')) {
            return;
        }
        if (!$container->hasParameter('.ux_design_tokens.paths')) {
            throw new LogicException('UX CSS reads its tokens from UX Design Tokens. Register "Symfony\UX\DesignTokens\UXDesignTokensBundle" in "config/bundles.php".');
        }
        $config = $container->getParameter('.ux_css.config');
        $container->getParameterBag()->remove('.ux_css.config');

        $converter = new DesignTokensConverter($container->getParameter('.ux_design_tokens.css_prefix'));
        $converted = $converter->convert(self::resolve($container));
        $project = PandaConfigConverter::convert(
            $config['conditions'],
            $converted['breakpoints'],
            $converted['tokens'],
        );
        $strictness = [$config['strict_tokens'], $config['strict_property_values']];
        self::validateStaticCss($project, $config['static_css'], ...$strictness);

        $classNames = ClassNameGenerator::fromPandaConfig(PandaConfig::create($project))->toArray();
        $container->getDefinition('ux_css.class_name_generator')->setArguments(array_values($classNames));
        $container->getDefinition('ux_css.engine')->setArguments([$project]);
        if ($container->hasDefinition('ux_css.stylesheet_dumper')) {
            $configHash = hash('xxh128', serialize([$project, $strictness, $config['static_css']]));
            $container->getDefinition('ux_css.stylesheet_dumper')->replaceArgument(10, $configHash);
        }
    }

    /**
     * @return list<array<string, TokenInterface>> the tokens of each Resolver permutation, by path
     */
    private static function resolve(ContainerBuilder $container): array
    {
        $paths = $container->getParameter('.ux_design_tokens.paths');
        $resolverPath = $container->getParameter('.ux_design_tokens.resolver_path');
        foreach ([...$paths, $resolverPath] as $path) {
            if (null === $path) {
                continue;
            }
            $usedEnvs = [];
            $shown = $container->resolveEnvPlaceholders($path, '%%env(%s)%%', $usedEnvs);
            if ([] !== $usedEnvs) {
                $message = \sprintf(
                    'UX CSS reads the design tokens when the container compiles, so the "%s" design token path cannot come from an environment variable.',
                    $shown,
                );

                throw new InvalidConfigurationException($message);
            }
        }

        $loader = new JsonDocumentLoader(
            $container->getParameter('kernel.project_dir'),
            $container->getParameter('.ux_design_tokens.allowed_roots'),
        );
        $resolver = new ConfiguredTokenResolver($loader, $paths, $resolverPath);
        $resolutions = [];
        foreach ($resolver->getPermutations() ?: [[]] as $inputs) {
            $resolution = $resolver->resolve($inputs);
            foreach ($resolution->getDocuments() as $document) {
                if (is_file($document)) {
                    $container->addResource(new FileResource($document));
                }
            }
            $resolutions[] = TokenTree::flatten($resolution->getTokens());
        }

        return $resolutions;
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
}
