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
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;

class ControllerMapGeneratorTest extends TestCase
{
    public function testGetControllersMap()
    {
        $mapper = $this->createMock(AssetMapperInterface::class);
        $mapper->expects($this->any())
            ->method('getAssetFromSourcePath')
            ->willReturnCallback(function ($path) {
                if (str_ends_with($path, 'package-controller-first.js')) {
                    $logicalPath = 'fake-vendor/ux-package1/package-controller-first.js';
                } elseif (str_ends_with($path, 'package-controller-second.js')) {
                    $logicalPath = 'fake-vendor/ux-package1/package-controller-second.js';
                } elseif (str_ends_with($path, 'package-hello-controller.js')) {
                    $logicalPath = 'fake-vendor/ux-package2/package-hello-controller.js';
                } else {
                    // replace windows slashes
                    $path = str_replace('\\', '/', $path);
                    $assetsPosition = strpos($path, '/assets/');
                    $logicalPath = substr($path, $assetsPosition + 1);
                }

                return new MappedAsset($logicalPath, $path, content: file_get_contents($path));
            });

        $packageReader = new UxPackageReader(__DIR__.'/../fixtures');

        $generator = new ControllersMapGenerator(
            $mapper,
            $packageReader,
            [
                __DIR__.'/../fixtures/assets/controllers',
                __DIR__.'/../fixtures/assets/more-controllers',
            ],
            __DIR__.'/../fixtures/assets/controllers.json',
        );

        $map = $generator->getControllersMap();
        // + 3 controller.json UX controllers
        // - 1 controllers.json UX controller is disabled
        // + 8 custom controllers (1 file is not a controller & 1 is overridden)
        $this->assertCount(10, $map);
        $packageNames = array_keys($map);
        sort($packageNames);
        $this->assertSame([
            'bye',
            'fake-vendor--ux-package1--controller-second',
            'fake-vendor--ux-package2--hello-controller',
            'hello',
            'hello-with-dashes',
            'hello-with-underscores',
            'other',
            'subdir--deeper',
            'subdir--deeper-with-dashes',
            'subdir--deeper-with-underscores',
        ], $packageNames);

        $controllerSecond = $map['fake-vendor--ux-package1--controller-second'];
        $this->assertSame('fake-vendor/ux-package1/package-controller-second.js', $controllerSecond->asset->logicalPath);
        // lazy from user's controller.json
        $this->assertTrue($controllerSecond->isLazy);

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
    }
}
