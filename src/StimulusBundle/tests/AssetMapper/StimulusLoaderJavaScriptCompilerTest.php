<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
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
    public function testCompileDynamicallyAddsContents(): void
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
            'import controller_0 from "../../controllers/foo-controller.js";',
            $compiledContents,
        );
        $this->assertStringContainsString(
            'import controller_1 from "../../in-root_controller.js";',
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

    public function testDebugModeIsSetCorrectly(): void
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

    public function testSupportsApplicationLoaders(): void
    {
        $compiler = new StimulusLoaderJavaScriptCompiler(
            $this->createStub(ControllersMapGenerator::class),
            false,
            [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/../admin/stimulus_loader.js',
                    'generator' => $this->createStub(ControllersMapGenerator::class),
                ],
            ],
        );

        $this->assertTrue($compiler->supports(new MappedAsset('controllers.js', realpath(__DIR__.'/../../assets/dist/controllers.js'))));
        $this->assertTrue($compiler->supports(new MappedAsset('admin/stimulus_loader.js', realpath(__DIR__.'/../fixtures/assets/admin/stimulus_loader.js'))));
        $this->assertFalse($compiler->supports(new MappedAsset('front/stimulus_loader.js', realpath(__DIR__.'/../fixtures/assets/front/stimulus_loader.js'))));
        $this->assertFalse($compiler->supports(new MappedAsset('app.js', realpath(__DIR__.'/../fixtures/assets/app.js'))));
    }

    public function testCompileApplicationLoader(): void
    {
        $globalGenerator = $this->createMock(ControllersMapGenerator::class);
        $globalGenerator->expects($this->never())->method('getControllersMap');

        $adminDir = realpath(__DIR__.'/../fixtures/assets/admin');
        $adminGenerator = $this->createMock(ControllersMapGenerator::class);
        $adminGenerator->expects($this->once())
            ->method('getControllersMap')
            ->willReturn([
                'admin-foo' => new MappedControllerAsset(
                    new MappedAsset('admin/controllers/foo-controller.js', $adminDir.'/controllers/foo-controller.js', publicPathWithoutDigest: '/assets/admin/controllers/foo-controller.js'),
                    false,
                ),
                'shared' => new MappedControllerAsset(
                    new MappedAsset('controllers/shared-controller.js', \dirname($adminDir).'/controllers/shared-controller.js', publicPathWithoutDigest: '/assets/controllers/shared-controller.js'),
                    true,
                ),
            ]);

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $globalGenerator,
            true,
            [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                    'generator' => $adminGenerator,
                ],
            ],
        );

        $loaderAsset = new MappedAsset(
            'admin/stimulus_loader.js',
            realpath(__DIR__.'/../fixtures/assets/admin/stimulus_loader.js'),
            publicPathWithoutDigest: '/assets/admin/stimulus_loader.js',
        );

        $compiledContents = $compiler->compile('', $loaderAsset, $this->createCoreAssetMapper(\dirname($adminDir).'/vendor/stimulus-bundle/core.js'));

        $this->assertStringStartsWith("import { startApplication } from \"../vendor/stimulus-bundle/core.js\";\n", $compiledContents);
        $this->assertStringEndsWith("\nexport const startStimulusApp = () => startApplication(eagerControllers, lazyControllers, isApplicationDebug);", $compiledContents);
        $this->assertStringContainsString('import controller_0 from "./controllers/foo-controller.js";', $compiledContents);
        $this->assertStringContainsString('export const eagerControllers = {"admin-foo": controller_0};', $compiledContents);
        $this->assertStringContainsString('export const lazyControllers = {"shared": () => import("../controllers/shared-controller.js")};', $compiledContents);
        $this->assertStringContainsString('export const isApplicationDebug = true;', $compiledContents);
        $this->assertCount(2, $loaderAsset->getDependencies());
    }

    public function testCompileApplicationLoaderWithCoreInSameDirectory(): void
    {
        $adminGenerator = $this->createStub(ControllersMapGenerator::class);
        $adminGenerator->method('getControllersMap')->willReturn([]);

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $this->createStub(ControllersMapGenerator::class),
            false,
            [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                    'generator' => $adminGenerator,
                ],
            ],
        );

        $loaderAsset = new MappedAsset(
            'admin/stimulus_loader.js',
            realpath(__DIR__.'/../fixtures/assets/admin/stimulus_loader.js'),
            publicPathWithoutDigest: '/assets/admin/stimulus_loader.js',
        );

        $compiledContents = $compiler->compile('', $loaderAsset, $this->createCoreAssetMapper(realpath(__DIR__.'/../fixtures/assets/admin').'/core.js'));

        $this->assertStringStartsWith("import { startApplication } from \"./core.js\";\n", $compiledContents);
    }

    public function testCompileApplicationLoaderThrowsWhenCoreAssetIsMissing(): void
    {
        $compiler = new StimulusLoaderJavaScriptCompiler(
            $this->createStub(ControllersMapGenerator::class),
            false,
            [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus_loader.js',
                    'generator' => $this->createStub(ControllersMapGenerator::class),
                ],
            ],
        );

        $loaderAsset = new MappedAsset(
            'admin/stimulus_loader.js',
            realpath(__DIR__.'/../fixtures/assets/admin/stimulus_loader.js'),
            publicPathWithoutDigest: '/assets/admin/stimulus_loader.js',
        );

        $assetMapper = $this->createStub(AssetMapperInterface::class);
        $assetMapper->method('getAsset')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "@symfony/stimulus-bundle/core.js" asset cannot be found.');
        $compiler->compile('', $loaderAsset, $assetMapper);
    }

    private function createCoreAssetMapper(string $coreSourcePath): AssetMapperInterface
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->once())
            ->method('getAsset')
            ->with('@symfony/stimulus-bundle/core.js')
            ->willReturn(new MappedAsset(
                '@symfony/stimulus-bundle/core.js',
                $coreSourcePath,
                publicPathWithoutDigest: '/assets/@symfony/stimulus-bundle/core.js',
            ));

        return $assetMapper;
    }

    private function createAsset(string $publicPath): MappedAsset
    {
        $asset = new MappedAsset(basename($publicPath), '/path/to/project/'.$publicPath, publicPathWithoutDigest: $publicPath);

        return $asset;
    }
}
