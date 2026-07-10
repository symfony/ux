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
use Symfony\UX\StimulusBundle\AssetMapper\AutoImportLocator;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\AssetMapper\MappedControllerAutoImport;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;

class ControllersMapGeneratorTest extends TestCase
{
    public function testGetControllersMap()
    {
        $mapper = $this->createStub(AssetMapperInterface::class);
        $mapper->method('getAssetFromSourcePath')
            ->willReturnCallback(static function ($path) {
                if (str_ends_with($path, 'package-controller-first.js')) {
                    $logicalPath = 'fake-vendor/ux-package1/package-controller-first.js';
                } elseif (str_ends_with($path, 'package-controller-second.js')) {
                    $logicalPath = 'fake-vendor/ux-package1/package-controller-second.js';
                } elseif (str_ends_with($path, 'package-hello-controller.js')) {
                    $logicalPath = 'fake-vendor/ux-package2/package-hello-controller.js';
                } elseif (str_ends_with($path, 'other-controller.ts')) {
                    return null;
                } else {
                    // replace windows slashes
                    $path = str_replace('\\', '/', $path);
                    $assetsPosition = strpos($path, '/assets/');
                    $logicalPath = substr($path, $assetsPosition + 1);
                }

                $content = null;
                if (str_ends_with($path, 'minified-controller.js')) {
                    $content = 'import{Controller}from"@hotwired/stimulus";export default class extends Controller{}';
                }

                return new MappedAsset($logicalPath, $path, content: $content);
            });

        $packageReader = new UxPackageReader(__DIR__.'/../fixtures');

        $autoImportLocator = $this->createStub(AutoImportLocator::class);
        $autoImportLocator->method('locateAutoImport')
            ->willReturnCallback(static function ($path) {
                return new MappedControllerAutoImport('/path/to'.$path, false);
            });

        $generator = new ControllersMapGenerator(
            $mapper,
            $packageReader,
            [
                __DIR__.'/../fixtures/assets/controllers',
                __DIR__.'/../fixtures/assets/more-controllers',
            ],
            __DIR__.'/../fixtures/assets/controllers.json',
            $autoImportLocator,
        );

        $map = $generator->getControllersMap();
        // + 2 UX controllers from controllers.json (1 disabled)
        // + 12 custom controllers (1 file is not a controller, 1 is overridden)
        $this->assertCount(14, $map);
        $packageNames = array_keys($map);
        sort($packageNames);
        $this->assertSame([
            'bye',
            'excluded',
            'fake-vendor--ux-package1--controller-second',
            'fake-vendor--ux-package2--hello-controller',
            'hello',
            'hello-with-dashes',
            'hello-with-underscores',
            'minified',
            'other',
            'preserved-comment',
            'subdir--deeper',
            'subdir--deeper-with-dashes',
            'subdir--deeper-with-underscores',
            'typescript',
        ], $packageNames);

        $controllerSecond = $map['fake-vendor--ux-package1--controller-second'];
        $this->assertSame('fake-vendor/ux-package1/package-controller-second.js', $controllerSecond->asset->logicalPath);
        // lazy from user's controller.json
        $this->assertTrue($controllerSecond->isLazy);
        $this->assertCount(4, $controllerSecond->autoImports);

        $helloControllerFromPackage = $map['fake-vendor--ux-package2--hello-controller'];
        $this->assertSame('fake-vendor/ux-package2/package-hello-controller.js', $helloControllerFromPackage->asset->logicalPath);
        $this->assertFalse($helloControllerFromPackage->isLazy);

        $helloController = $map['hello'];
        $this->assertStringContainsString('hello-controller.js override', file_get_contents($helloController->asset->sourcePath));
        $this->assertFalse($helloController->isLazy);

        // lazy from stimulusFetch comment
        $byeController = $map['bye'];
        $this->assertTrue($byeController->isLazy);

        $otherController = $map['other'];
        $this->assertTrue($otherController->isLazy);

        $minifiedController = $map['minified'];
        $this->assertTrue($minifiedController->isLazy);

        $preservedComment = $map['preserved-comment'];
        $this->assertTrue($preservedComment->isLazy);
    }

    public function testGetControllersMapThrowsOnUnmappedController()
    {
        $mapper = $this->createStub(AssetMapperInterface::class);
        $mapper->method('getAssetFromSourcePath')
            ->willReturnCallback(static function ($path) {
                if (str_ends_with($path, 'excluded-controller.js')) {
                    return null;
                }

                $path = str_replace('\\', '/', $path);
                $assetsPosition = strpos($path, '/assets/');
                $logicalPath = substr($path, $assetsPosition + 1);

                return new MappedAsset($logicalPath, $path);
            });

        $packageReader = new UxPackageReader(__DIR__.'/../fixtures');

        $autoImportLocator = $this->createStub(AutoImportLocator::class);
        $autoImportLocator->method('locateAutoImport')
            ->willReturnCallback(static function ($path) {
                return new MappedControllerAutoImport('/path/to'.$path, false);
            });

        $generator = new ControllersMapGenerator(
            $mapper,
            $packageReader,
            [
                __DIR__.'/../fixtures/assets/more-controllers',
            ],
            __DIR__.'/../fixtures/assets/nonexistent.json',
            $autoImportLocator,
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Could not find an asset mapper path that points to the "excluded" controller.');
        $generator->getControllersMap();
    }

    public function testCustomControllersAreSortedByName()
    {
        $mapper = $this->createMock(AssetMapperInterface::class);
        $mapper->expects($this->any())
            ->method('getAssetFromSourcePath')
            ->willReturnCallback(static function ($path) {
                // replace windows slashes
                $path = str_replace('\\', '/', $path);
                $assetsPosition = strpos($path, '/assets/');

                return new MappedAsset(substr($path, $assetsPosition + 1), $path);
            });

        $autoImportLocator = $this->createMock(AutoImportLocator::class);
        $autoImportLocator->expects($this->any())
            ->method('locateAutoImport')
            ->willReturnCallback(static function ($path) {
                return new MappedControllerAutoImport('/path/to'.$path, false);
            });

        $generator = new ControllersMapGenerator(
            $mapper,
            new UxPackageReader(__DIR__.'/../fixtures'),
            [__DIR__.'/../fixtures/assets/controllers'],
            __DIR__.'/../fixtures/assets/controllers.json',
            $autoImportLocator,
        );

        $customControllers = array_values(array_filter(
            array_keys($generator->getControllersMap()),
            static fn (string $name) => !str_starts_with($name, 'fake-vendor--'),
        ));

        $this->assertSame([
            'bye',
            'hello',
            'hello-with-dashes',
            'hello-with-underscores',
            'preserved-comment',
            'subdir--deeper',
            'subdir--deeper-with-dashes',
            'subdir--deeper-with-underscores',
            'typescript',
        ], $customControllers);
    }
}
