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
use Symfony\Component\Filesystem\Filesystem;
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
        $file = $configDir.'/reference_css.php';

        $stampFile = self::stampFile($container);
        $inputs = null !== $stampFile ? serialize([$project, $strictness, self::packageFingerprint()]) : null;
        if (null !== $stampFile && is_file($stampFile) && file_get_contents($stampFile) === self::stamp($inputs, $file)) {
            $container->addResource(new FileResource($file));

            return;
        }

        $validator = new StyleValidator(Engine::fromProjectConfig($project), ...$strictness);
        $reference = new ReferenceDumper($validator)->dump();

        if (!is_file($file) || file_get_contents($file) !== $reference) {
            file_put_contents($file, $reference);
        }
        if (null !== $stampFile) {
            clearstatcache(true, $file);
            new Filesystem()->dumpFile($stampFile, self::stamp($inputs, $file));
        }
        $container->addResource(new FileResource($file));
    }

    private static function stampFile(ContainerBuilder $container): ?string
    {
        foreach (['kernel.build_dir', 'kernel.cache_dir'] as $parameter) {
            if ($container->hasParameter($parameter) && \is_string($directory = $container->getParameter($parameter))) {
                return $directory.'/ux_css/reference.hash';
            }
        }

        return null;
    }

    private static function stamp(string $inputs, string $file): string
    {
        return hash('xxh128', $inputs.'|'.(@filemtime($file) ?: 0).'|'.(@filesize($file) ?: 0));
    }

    private static function packageFingerprint(): string
    {
        $root = \dirname(__DIR__, 3);
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root.'/src', \FilesystemIterator::SKIP_DOTS));

        $files = glob($root.'/resources/*.json') ?: [];
        foreach ($iterator as $entry) {
            if ($entry->isFile() && 'php' === $entry->getExtension()) {
                $files[] = $entry->getPathname();
            }
        }
        sort($files);

        $fingerprint = '';
        foreach ($files as $path) {
            $fingerprint .= $path.':'.filemtime($path).':'.filesize($path)."\n";
        }

        return $fingerprint;
    }
}
