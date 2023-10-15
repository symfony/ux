<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Vue\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\Vue\AssetMapper\VueControllerLoaderAssetCompiler;

class VueControllerLoaderAssetCompilerTest extends TestCase
{
    public function testCompileDynamicallyAddsContents()
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->exactly(2))
            ->method('getAssetFromSourcePath')
            ->with($this->logicalOr(
                $this->equalTo(realpath(__DIR__.'/../fixtures/vue/controllers/MyVueController.js')),
                $this->equalTo(realpath(__DIR__.'/../fixtures/vue/controllers/subdir/DeeperVueController.js')),
            ))
            ->willReturnCallback(function ($sourcePath) {
                if (str_contains($sourcePath, 'MyVueController')) {
                    return new MappedAsset(
                        'MyVueController.js',
                        publicPathWithoutDigest: '/assets/vue/controllers/MyVueController.js',
                    );
                }

                if (str_contains($sourcePath, 'DeeperVueController')) {
                    return new MappedAsset(
                        'subdir/DeeperVueController.js',
                        publicPathWithoutDigest: '/assets/vue/controllers/subdir/DeeperVueController.js',
                    );
                }

                throw new \Exception('Unexpected source path: '.$sourcePath);
            });

        $compiler = new VueControllerLoaderAssetCompiler(
            __DIR__.'/../fixtures/vue/controllers',
            ['*.js']
        );

        $loaderAsset = new MappedAsset('loader.js', publicPathWithoutDigest: '/assets/symfony/ux-vue/loader.js');
        $startingContents = file_get_contents(__DIR__.'/../../assets/dist/loader.js');

        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $assetMapper);
        $this->assertStringContainsString(
            "from '../../vue/controllers/subdir/DeeperVueController.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            "from '../../vue/controllers/MyVueController.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            'export const components = {"',
            $compiledContents,
        );
        $this->assertStringContainsString(
            '"subdir/DeeperVueController": component_',
            $compiledContents,
        );
    }
}
