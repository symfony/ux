<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Svelte\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\Svelte\AssetMapper\SvelteControllerLoaderAssetCompiler;

class SvelteControllerLoaderAssetCompilerTest extends TestCase
{
    public function testCompileDynamicallyAddsContents()
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->exactly(2))
            ->method('getAssetFromSourcePath')
            ->with($this->logicalOr(
                $this->equalTo(realpath(__DIR__.'/../fixtures/svelte/controllers/MySvelteController.js')),
                $this->equalTo(realpath(__DIR__.'/../fixtures/svelte/controllers/subdir/DeeperSvelteController.js')),
            ))
            ->willReturnCallback(function ($sourcePath) {
                if (str_contains($sourcePath, 'MySvelteController')) {
                    return new MappedAsset(
                        'MySvelteController.js',
                        publicPathWithoutDigest: '/assets/svelte/controllers/MySvelteController.js',
                    );
                }

                if (str_contains($sourcePath, 'DeeperSvelteController')) {
                    return new MappedAsset(
                        'subdir/DeeperSvelteController.js',
                        publicPathWithoutDigest: '/assets/svelte/controllers/subdir/DeeperSvelteController.js',
                    );
                }

                throw new \Exception('Unexpected source path: '.$sourcePath);
            });

        $compiler = new SvelteControllerLoaderAssetCompiler(
            __DIR__.'/../fixtures/svelte/controllers',
            ['*.js']
        );

        $loaderAsset = new MappedAsset('loader.js', publicPathWithoutDigest: '/assets/symfony/ux-svelte/loader.js');
        $startingContents = file_get_contents(__DIR__.'/../../assets/dist/loader.js');

        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $assetMapper);
        $this->assertStringContainsString(
            "from '../../svelte/controllers/subdir/DeeperSvelteController.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            "from '../../svelte/controllers/MySvelteController.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            'export const components = {"',
            $compiledContents,
        );
        $this->assertStringContainsString(
            '"subdir/DeeperSvelteController": component_',
            $compiledContents,
        );
    }
}
