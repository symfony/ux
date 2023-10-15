<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\React\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\Compiler\AssetCompilerInterface;
use Symfony\Component\AssetMapper\MappedAsset;

/**
 * Compiles the render_controller.js file to replace process.env dynamically.
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class ReactReplaceProcessEnvAssetCompiler implements AssetCompilerInterface
{
    public function __construct(
        private bool $isDebug
    ) {
    }

    public function supports(MappedAsset $asset): bool
    {
        return $asset->sourcePath === realpath(__DIR__.'/../../assets/dist/render_controller.js');
    }

    public function compile(string $content, MappedAsset $asset, AssetMapperInterface $assetMapper): string
    {
        if (!str_contains($content, 'process.env.NODE_ENV')) {
            return $content;
        }

        $env = $this->isDebug ? 'development' : 'production';

        return str_replace('process.env.NODE_ENV', "'$env'", $content);
    }
}
