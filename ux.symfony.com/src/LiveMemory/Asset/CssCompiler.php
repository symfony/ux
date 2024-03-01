<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\LiveMemory\Asset;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\Component\Filesystem\Path;

/**
 * Experimental CSS compiler.
 *
 * This compiler combines all the SCSS files imported in `live-memory.min.css`
 * into a single CSS file, and removes SASS comments and empty lines.
 *
 * @internal
 */
class CssCompiler implements AssetCompilerInterface
{
    public function supports(MappedAsset $asset): bool
    {
        return 'css' === $asset->publicExtension && str_starts_with($asset->logicalPath, 'styles/demos/live-memory');
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        return preg_replace_callback('#@import (?:"|\')(.*)(?:\'|");#m', function ($matches) use ($asset) {
            $file = $matches[1];
            $file = substr_replace($file, '/_', strrpos($file, '/'), 1);
            $file = './'.$file.'.scss';

            $filePath = Path::makeAbsolute($file, \dirname($asset->sourcePath));
            if (!file_exists($filePath)) {
                throw new \RuntimeException(sprintf('The file "%s" does not exist.', $filePath));
            }
            $content = file_get_contents($filePath);

            $asset->addFileDependency($filePath);

            // Remove SASS comments
            $content = preg_replace('#^//.*\n?#m', '', $content);
            // Remove empty lines
            $content = preg_replace('/^\s*\n/m', '', $content);

            return $content;
        }, $content);
    }
}
