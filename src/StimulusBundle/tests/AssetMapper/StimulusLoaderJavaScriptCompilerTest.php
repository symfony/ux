<?php

/*
 * This file is part of the Symfony StimulusBundle package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\AssetMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\AssetMapper\MappedControllerAsset;
use Symfony\UX\StimulusBundle\AssetMapper\StimulusLoaderJavaScriptCompiler;

class StimulusLoaderJavaScriptCompilerTest extends TestCase
{
    public function testCompileDynamicallyAddsContents()
    {
        $controllerMapGenerator = $this->createMock(ControllersMapGenerator::class);
        $controllerMapGenerator->expects($this->once())
            ->method('getControllersMap')
            ->willReturn([
                'foo' => new MappedControllerAsset(
                    $this->createAsset('/assets/controllers/foo-controller.js'),
                    false,
                ),
                'bar' => new MappedControllerAsset(
                    $this->createAsset('/assets/controllers/bar-controller.js'),
                    true,
                ),
                'in-root' => new MappedControllerAsset(
                    $this->createAsset('/assets/in-root_controller.js'),
                    false,
                ),
                'deeper-package' => new MappedControllerAsset(
                    $this->createAsset('/assets/some-vendor/fake-package/deeper-package-controller.js'),
                    true,
                ),
            ]);

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $controllerMapGenerator,
            true,
        );
        $loaderAsset = $this->createAsset('/assets/symfony/stimulus-bundle/loader.js');
        $startingContents = file_get_contents(__DIR__.'/../../assets/dist/loader.js');

        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $this->createMock(AssetMapperInterface::class));
        $this->assertStringContainsString(
            "import controller_0 from '../../controllers/foo-controller.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            "import controller_1 from '../../in-root_controller.js';",
            $compiledContents,
        );
        $this->assertStringContainsString(
            'export const eagerControllers = {"foo": controller_0, "in-root": controller_1};',
            $compiledContents,
        );

        $this->assertStringContainsString(
            'export const lazyControllers = {"bar": () => import("../../controllers/bar-controller.js"), "deeper-package": () => import("../../some-vendor/fake-package/deeper-package-controller.js")};',
            $compiledContents,
        );

        // all 4 controllers should be dependencies
        $this->assertCount(4, $loaderAsset->getDependencies());
    }

    public function testDebugModeIsSetCorrectly()
    {
        $controllerMapGenerator = $this->createMock(ControllersMapGenerator::class);
        $controllerMapGenerator->expects($this->any())
            ->method('getControllersMap')
            ->willReturn([]);

        $loaderAsset = $this->createAsset('/assets/symfony/stimulus-bundle/loader.js');
        $startingContents = file_get_contents(__DIR__.'/../../assets/dist/loader.js');

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $controllerMapGenerator,
            isDebug: true,
        );
        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $this->createMock(AssetMapperInterface::class));
        $this->assertStringContainsString(
            'const isApplicationDebug = true;',
            $compiledContents,
        );

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $controllerMapGenerator,
            isDebug: false,
        );
        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $this->createMock(AssetMapperInterface::class));
        $this->assertStringContainsString(
            'const isApplicationDebug = false;',
            $compiledContents,
        );
    }

    private function createAsset(string $publicPath): MappedAsset
    {
        $asset = new MappedAsset(basename($publicPath), publicPathWithoutDigest: $publicPath);

        return $asset;
    }
}
