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

use PHPUnit\Framework\Attributes\DataProvider;
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

        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $this->createRuntimeAssetMapper('/path/to/project//assets/symfony/stimulus-bundle/runtime.js'));
        $this->assertStringStartsWith("import { loadControllers, startApplication } from \"./runtime.js\";\n", $compiledContents);
        $this->assertStringNotContainsString('controllers.js', $compiledContents);
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
        $this->assertStringEndsWith(
            "\nexport const startStimulusApp = () => startApplication(eagerControllers, lazyControllers, isApplicationDebug);\nexport { loadControllers };",
            $compiledContents,
        );

        // all 4 controllers should be dependencies
        $this->assertCount(4, $loaderAsset->getDependencies());
    }

    public function testDebugModeIsSetCorrectly(): void
    {
        $controllerMapGenerator = $this->createStub(ControllersMapGenerator::class);
        $controllerMapGenerator->method('getControllersMap')->willReturn([]);

        $loaderAsset = $this->createAsset('/assets/symfony/stimulus-bundle/loader.js');
        $startingContents = file_get_contents(__DIR__.'/../../assets/dist/loader.js');

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $controllerMapGenerator,
            isDebug: true,
        );
        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $this->createRuntimeAssetMapper('/path/to/project/assets/symfony/stimulus-bundle/runtime.js'));
        $this->assertStringContainsString(
            'const isApplicationDebug = true;',
            $compiledContents,
        );

        $compiler = new StimulusLoaderJavaScriptCompiler(
            $controllerMapGenerator,
            isDebug: false,
        );
        $compiledContents = $compiler->compile($startingContents, $loaderAsset, $this->createRuntimeAssetMapper('/path/to/project/assets/symfony/stimulus-bundle/runtime.js'));
        $this->assertStringContainsString(
            'const isApplicationDebug = false;',
            $compiledContents,
        );
    }

    public function testCompileControllersJsKeepsOnlyTheControllers(): void
    {
        $controllerMapGenerator = $this->createStub(ControllersMapGenerator::class);
        $controllerMapGenerator->method('getControllersMap')->willReturn([
            // on the same root as controllers.js, so that the import path can be made relative on Windows
            'foo' => new MappedControllerAsset(new MappedAsset('foo-controller.js', __DIR__.'/../fixtures/assets/controllers/foo-controller.js', publicPathWithoutDigest: '/assets/controllers/foo-controller.js'), false),
        ]);

        $compiler = new StimulusLoaderJavaScriptCompiler($controllerMapGenerator, false);
        $controllersAsset = new MappedAsset(
            '@symfony/stimulus-bundle/controllers.js',
            realpath(__DIR__.'/../../assets/dist/controllers.js'),
            publicPathWithoutDigest: '/assets/@symfony/stimulus-bundle/controllers.js',
        );

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->never())->method('getAsset');

        $compiledContents = $compiler->compile('', $controllersAsset, $assetMapper);

        $this->assertStringContainsString('export const eagerControllers = {"foo": controller_0};', $compiledContents);
        $this->assertStringNotContainsString('runtime.js', $compiledContents);
        $this->assertStringNotContainsString('startStimulusApp', $compiledContents);
    }

    public function testSupports(): void
    {
        $compiler = new StimulusLoaderJavaScriptCompiler(
            $this->createStub(ControllersMapGenerator::class),
            false,
            [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/../admin/stimulus.js',
                    'generator' => $this->createStub(ControllersMapGenerator::class),
                ],
            ],
        );

        $this->assertTrue($compiler->supports(new MappedAsset('loader.js', realpath(__DIR__.'/../../assets/dist/loader.js'))));
        $this->assertTrue($compiler->supports(new MappedAsset('controllers.js', realpath(__DIR__.'/../../assets/dist/controllers.js'))));
        $this->assertTrue($compiler->supports(new MappedAsset('admin/stimulus.js', realpath(__DIR__.'/../fixtures/assets/admin/stimulus.js'))));
        $this->assertFalse($compiler->supports(new MappedAsset('runtime.js', realpath(__DIR__.'/../../assets/dist/runtime.js'))));
        $this->assertTrue($compiler->supports(new MappedAsset('shop/stimulus.js', realpath(__DIR__.'/../fixtures/assets/shop/stimulus.js'))));
        $this->assertFalse($compiler->supports(new MappedAsset('shop/stimulus.js', realpath(__DIR__.'/../fixtures/assets/shop/stimulus.js'), isVendor: true)));
        $this->assertFalse($compiler->supports(new MappedAsset('front/stimulus_loader.js', realpath(__DIR__.'/../fixtures/assets/front/stimulus_loader.js'))));
        $this->assertFalse($compiler->supports(new MappedAsset('app.js', realpath(__DIR__.'/../fixtures/assets/app.js'))));
    }

    public function testSupportsIgnoresTheStubWithoutApplications(): void
    {
        $compiler = new StimulusLoaderJavaScriptCompiler($this->createStub(ControllersMapGenerator::class), false);

        $this->assertTrue($compiler->supports(new MappedAsset('loader.js', realpath(__DIR__.'/../../assets/dist/loader.js'))));
        $this->assertFalse($compiler->supports(new MappedAsset('shop/stimulus.js', realpath(__DIR__.'/../fixtures/assets/shop/stimulus.js'))));
    }

    public function testCompileRejectsAStubNoApplicationUses(): void
    {
        $compiler = $this->createAdminCompiler();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('no Stimulus application uses it');

        $compiler->compile(
            "export { startStimulusApp } from '@symfony/stimulus-bundle';\n",
            new MappedAsset('shop/stimulus.js', realpath(__DIR__.'/../fixtures/assets/shop/stimulus.js'), publicPathWithoutDigest: '/assets/shop/stimulus.js'),
            $this->createRuntimeAssetMapper(realpath(__DIR__.'/../fixtures/assets').'/runtime.js', 0),
        );
    }

    public function testCompileApplicationModule(): void
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
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus.js',
                    'generator' => $adminGenerator,
                ],
            ],
        );

        $moduleAsset = $this->createAdminModuleAsset();

        $compiledContents = $compiler->compile('', $moduleAsset, $this->createRuntimeAssetMapper(\dirname($adminDir).'/vendor/stimulus-bundle/runtime.js'));

        $this->assertStringStartsWith("import { loadControllers, startApplication } from \"../vendor/stimulus-bundle/runtime.js\";\n", $compiledContents);
        $this->assertStringEndsWith("\nexport const startStimulusApp = () => startApplication(eagerControllers, lazyControllers, isApplicationDebug);\nexport { loadControllers };", $compiledContents);
        $this->assertStringContainsString('import controller_0 from "./controllers/foo-controller.js";', $compiledContents);
        $this->assertStringContainsString('export const eagerControllers = {"admin-foo": controller_0};', $compiledContents);
        $this->assertStringContainsString('export const lazyControllers = {"shared": () => import("../controllers/shared-controller.js")};', $compiledContents);
        $this->assertStringContainsString('export const isApplicationDebug = true;', $compiledContents);
        $this->assertStringNotContainsString('@symfony/stimulus-bundle', $compiledContents);
        $this->assertCount(2, $moduleAsset->getDependencies());
    }

    public function testCompileApplicationModuleWithRuntimeInSameDirectory(): void
    {
        $compiler = $this->createAdminCompiler();

        $compiledContents = $compiler->compile('', $this->createAdminModuleAsset(), $this->createRuntimeAssetMapper(realpath(__DIR__.'/../fixtures/assets/admin').'/runtime.js'));

        $this->assertStringStartsWith("import { loadControllers, startApplication } from \"./runtime.js\";\n", $compiledContents);
    }

    #[DataProvider('provideAcceptedModuleContents')]
    public function testCompileApplicationModuleReplacesAnEmptyFileOrTheStub(string $content): void
    {
        $compiler = $this->createAdminCompiler();

        $compiledContents = $compiler->compile($content, $this->createAdminModuleAsset(), $this->createRuntimeAssetMapper(realpath(__DIR__.'/../fixtures/assets/admin').'/runtime.js'));

        $this->assertStringNotContainsString('@symfony/stimulus-bundle', $compiledContents);
        $this->assertStringContainsString('export const startStimulusApp = ', $compiledContents);
    }

    public static function provideAcceptedModuleContents(): iterable
    {
        yield 'empty' => [''];
        yield 'blank lines' => ["\n\n"];
        yield 'stub' => ["export { startStimulusApp } from '@symfony/stimulus-bundle';\n"];
        yield 'stub with double quotes' => ['export { startStimulusApp } from "@symfony/stimulus-bundle";'];
        yield 'stub without semicolon' => ["export {startStimulusApp} from '@symfony/stimulus-bundle'"];
    }

    #[DataProvider('provideRejectedModuleContents')]
    public function testCompileApplicationModuleRejectsOtherContent(string $content): void
    {
        $compiler = $this->createAdminCompiler();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('module of the "admin" Stimulus application is generated');

        $compiler->compile($content, $this->createAdminModuleAsset(), $this->createRuntimeAssetMapper(realpath(__DIR__.'/../fixtures/assets/admin').'/runtime.js', 0));
    }

    public static function provideRejectedModuleContents(): iterable
    {
        yield 'user code' => ["import Clipboard from 'stimulus-clipboard';\n"];
        yield 'stub followed by user code' => ["export { startStimulusApp } from '@symfony/stimulus-bundle';\nconsole.log('admin');\n"];
        yield 'export of everything' => ["export * from '@symfony/stimulus-bundle';"];
        yield 'stub from another package' => ["export { startStimulusApp } from '@acme/stimulus';"];
    }

    public function testCompileApplicationModuleThrowsWhenRuntimeAssetIsMissing(): void
    {
        $compiler = $this->createAdminCompiler();

        $assetMapper = $this->createStub(AssetMapperInterface::class);
        $assetMapper->method('getAsset')->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "@symfony/stimulus-bundle/runtime.js" asset cannot be found.');
        $compiler->compile('', $this->createAdminModuleAsset(), $assetMapper);
    }

    private function createAdminCompiler(): StimulusLoaderJavaScriptCompiler
    {
        $adminGenerator = $this->createStub(ControllersMapGenerator::class);
        $adminGenerator->method('getControllersMap')->willReturn([]);

        return new StimulusLoaderJavaScriptCompiler(
            $this->createStub(ControllersMapGenerator::class),
            false,
            [
                'admin' => [
                    'loader' => __DIR__.'/../fixtures/assets/admin/stimulus.js',
                    'generator' => $adminGenerator,
                ],
            ],
        );
    }

    private function createAdminModuleAsset(): MappedAsset
    {
        return new MappedAsset(
            'admin/stimulus.js',
            realpath(__DIR__.'/../fixtures/assets/admin/stimulus.js'),
            publicPathWithoutDigest: '/assets/admin/stimulus.js',
        );
    }

    private function createRuntimeAssetMapper(string $runtimeSourcePath, int $expectedCalls = 1): AssetMapperInterface
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->exactly($expectedCalls))
            ->method('getAsset')
            ->with('@symfony/stimulus-bundle/runtime.js')
            ->willReturn(new MappedAsset(
                '@symfony/stimulus-bundle/runtime.js',
                $runtimeSourcePath,
                publicPathWithoutDigest: '/assets/@symfony/stimulus-bundle/runtime.js',
            ));

        return $assetMapper;
    }

    private function createAsset(string $publicPath): MappedAsset
    {
        $asset = new MappedAsset(basename($publicPath), '/path/to/project/'.$publicPath, publicPathWithoutDigest: $publicPath);

        return $asset;
    }
}
