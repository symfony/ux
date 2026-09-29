<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\DependencyInjection;

use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Toolkit\Preview\PreviewAssetsGenerator;
use Symfony\UX\Toolkit\Preview\PreviewFilesResource;

/**
 * Registers the generated preview files of every previewed kit as Tailwind inputs and importmap entrypoints.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PreviewPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('.ux_toolkit.preview.importmap_config_reader')) {
            return;
        }

        if (!$container->hasDefinition('asset_mapper.importmap.config_reader')) {
            throw self::missingPackage('symfony/asset-mapper');
        }

        $outputDir = $container->getParameterBag()->resolveValue('%.ux_toolkit.preview.output_dir%');

        $kitDirs = $container->getParameter('.ux_toolkit.preview.kit_dirs');
        $container->getDefinition('.ux_toolkit.preview.kit_registry')->replaceArgument(1, $kitDirs);
        $container->getDefinition('.ux_toolkit.preview.cache_warmer')->replaceArgument(3, $outputDir);

        $entrypoints = [];
        $tailwindInputs = [];
        $generatedFiles = [];
        foreach ($kitDirs as $kitName => $kitDir) {
            $this->trackKit($container, $kitDir);

            ['script' => $script, 'stylesheet' => $stylesheet] = PreviewAssetsGenerator::getOutputFiles($kitName, $kitDir, $outputDir);
            $entrypoints[PreviewAssetsGenerator::entrypointName($kitName)] = $script;
            $generatedFiles[] = $script;
            if (null !== $stylesheet) {
                $tailwindInputs[] = $stylesheet;
                $generatedFiles[] = $stylesheet;
            }
        }
        $container->addResource(new PreviewFilesResource($generatedFiles));

        if ([] !== $tailwindInputs) {
            $this->addTailwindInputs($container, $tailwindInputs);
        }

        $importMapPath = $container->getDefinition('asset_mapper.importmap.config_reader')->getArgument(0);
        $container->getDefinition('.ux_toolkit.preview.importmap_config_reader')
            ->replaceArgument(1, $entrypoints)
            ->replaceArgument(2, $importMapPath);
    }

    private function trackKit(ContainerBuilder $container, string $kitDir): void
    {
        $container->addResource(new FileResource($kitDir.'/manifest.json'));
        $container->addResource(new GlobResource($kitDir, '/*/manifest.json', false));
        foreach (glob($kitDir.'/*/manifest.json') ?: [] as $recipeManifest) {
            $container->addResource(new FileResource($recipeManifest));
        }
        $container->addResource(new FileExistenceResource($kitDir.'/kit.css'));
        $container->addResource(new FileExistenceResource($kitDir.'/kit.js'));
        $container->addResource(new GlobResource($kitDir, '/*/assets/controllers/**/*_controller.js', false));
    }

    /**
     * @param list<string> $tailwindInputs
     */
    private function addTailwindInputs(ContainerBuilder $container, array $tailwindInputs): void
    {
        if (!$container->hasDefinition('tailwind.builder')) {
            throw self::missingPackage('symfonycasts/tailwind-bundle');
        }

        $tailwindBuilder = $container->getDefinition('tailwind.builder');
        if (!\is_array($tailwindBuilder->getArgument(1))) {
            throw new \LogicException('Previewing a kit that ships a "kit.css" requires "symfonycasts/tailwind-bundle" 0.6 or later.');
        }

        $tailwindBuilder->replaceArgument(1, [...$tailwindBuilder->getArgument(1), ...$tailwindInputs]);
    }

    private static function missingPackage(string $package): \LogicException
    {
        return new \LogicException(\sprintf('The "ux_toolkit.preview" option requires "%s". Install it with "composer require %1$s" and enable its bundle.', $package));
    }
}
