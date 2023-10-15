<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\React\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\React\AssetMapper\ReactControllerLoaderAssetCompiler;

class ReactControllerLoaderAssetCompilerTest extends TestCase
{
    public function testCompileDynamicallyAddsContents()
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->exactly(2))
            ->method('getAssetFromSourcePath')
            ->with($this->logicalOr(
                $this->equalTo(realpath(__DIR__.'/../fixtures/react/controllers/MyReactController.js')),
                $this->equalTo(realpath(__DIR__.'/../fixtures/react/controllers/subdir/DeeperReactController.js')),
            ))
            ->willReturnCallback(function ($sourcePath) {
                if (str_contains($sourcePath, 'MyReactController')) {
                    return new MappedAsset(
                        'MyReactController.js',
                        publicPathWithoutDigest: '/assets/react/controllers/MyReactController.js',
                    );
                }

                if (str_contains($sourcePath, 'DeeperReactController')) {
                    return new MappedAsset(
                        'subdir/DeeperReactController.js',
                        publicPathWithoutDigest: '/assets/react/controllers/subdir/DeeperReactController.js',
                    );
                }

                throw new \Exception('Unexpected source path: '.$sourcePath);
            });

        $compiler = new ReactControllerLoaderAssetCompiler(
            __DIR__.'/../fixtures/react/controllers',
            ['*.js']
        );

        $loaderAsset = new MappedAsset('loader.js', publicPathWithoutDigest: '/assets/symfony/ux-react/loader.js');
        $startingContents = file_get_contents(__DIR__.'/../../assets/dist/loader.js');

        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $assetMapper);
        $this->assertStringContainsString(
            "from '../../react/controllers/subdir/DeeperReactController.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            "from '../../react/controllers/MyReactController.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            'export const components = {"',
            $compiledContents,
        );
        $this->assertStringContainsString(
            '"subdir/DeeperReactController": component_',
            $compiledContents,
        );
    }
}
