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

use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Reference\ReferenceDumper;
use Symfony\UX\Css\Validation\StyleValidator;

/**
 * Writes config/reference_css.php, the types editors read to complete css() hashes, when its content changes.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ReferenceDumpPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('ux_css.engine') || !$container->hasDefinition('ux_css.validator')) {
            return;
        }

        if ($container->hasParameter('.kernel.config_dir')) {
            $configDir = $container->getParameter('.kernel.config_dir');
        } else {
            $configDir = $container->getParameter('kernel.project_dir').'/config';
        }
        if (!is_dir($configDir) || !is_writable($configDir)) {
            return;
        }

        $project = $container->getDefinition('ux_css.engine')->getArgument(0);
        $strictness = \array_slice($container->getDefinition('ux_css.validator')->getArguments(), 1, 2);
        $validator = new StyleValidator(Engine::fromProjectConfig($project), ...$strictness);
        $reference = new ReferenceDumper($validator)->dump();

        $file = $configDir.'/reference_css.php';
        if (!is_file($file) || file_get_contents($file) !== $reference) {
            file_put_contents($file, $reference);
        }
        $container->addResource(new FileResource($file));
    }
}
