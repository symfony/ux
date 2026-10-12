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
use Symfony\UX\StimulusBundle\AssetMapper\AutoImportLocator;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\AssetMapper\MappedControllerAutoImport;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;

class ControllersMapGeneratorTest extends TestCase
{
    public function testGetControllersMap(): void
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

    public function testGetControllersMapThrowsOnUnmappedController(): void
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

    public function testCustomControllersAreSortedByName(): void
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

    public function testApplicationControllersOverrideGlobalControllers(): void
    {
        $globalMap = $this->createGenerator([__DIR__.'/../fixtures/assets/controllers'])->getControllersMap();
        $this->assertStringNotContainsString('override', file_get_contents($globalMap['hello']->asset->sourcePath));

        $generator = $this->createGenerator(
            [__DIR__.'/../fixtures/assets/controllers'],
            [__DIR__.'/../fixtures/assets/more-controllers'],
        );

        $map = $generator->getControllersMap();
        $this->assertStringContainsString('hello-controller.js override', file_get_contents($map['hello']->asset->sourcePath));
        $this->assertArrayHasKey('bye', $map);
        $this->assertArrayHasKey('minified', $map);
    }

    public function testEmptyControllerPathsDoNotThrow(): void
    {
        $generator = $this->createGenerator([], [], __DIR__.'/../fixtures/assets/nonexistent.json');

        $this->assertSame([], $generator->getControllersMap());
    }

    public function testGetControllerPathsIncludesApplicationPathsAfterGlobalPaths(): void
    {
        $generator = $this->createGenerator(['/global/a', '/global/b'], ['/app/a']);

        $this->assertSame(['/global/a', '/global/b', '/app/a'], $generator->getControllerPaths());
    }

    public function testApplicationControllersJsonReplacesTheGlobalOne(): void
    {
        $controllersJsonPath = self::writeControllersJson([
            '@fake-vendor/ux-package1' => [
                'controller_first' => ['enabled' => true, 'fetch' => 'lazy'],
            ],
        ]);

        try {
            $map = $this->createGenerator([], [], $controllersJsonPath)->getControllersMap();
        } finally {
            unlink($controllersJsonPath);
        }

        $this->assertSame(['fake-vendor--ux-package1--controller-first'], array_keys($map));
        $this->assertTrue($map['fake-vendor--ux-package1--controller-first']->isLazy);
        $this->assertSame([], $map['fake-vendor--ux-package1--controller-first']->autoImports);
    }

    public function testApplicationControllersJsonIsMergedOverTheGlobalOne(): void
    {
        $controllersJsonPath = self::writeControllersJson([
            '@fake-vendor/ux-package1' => [
                'controller_first' => ['enabled' => true],
                'controller_second' => [
                    'fetch' => 'eager',
                    'autoimport' => [
                        'in/asset/mapper/controller_second1.css' => false,
                        'in/asset/mapper/controller_second2.css' => true,
                    ],
                ],
            ],
            '@fake-vendor/ux-package2' => [
                'hello_controller' => ['enabled' => false],
            ],
        ]);

        try {
            $map = $this->createGenerator([], [], $controllersJsonPath, __DIR__.'/../fixtures/assets/controllers.json')->getControllersMap();
        } finally {
            unlink($controllersJsonPath);
        }

        $this->assertSame(['fake-vendor--ux-package1--controller-first', 'fake-vendor--ux-package1--controller-second'], array_keys($map));

        $controllerFirst = $map['fake-vendor--ux-package1--controller-first'];
        $this->assertFalse($controllerFirst->isLazy);
        $this->assertSame(['/path/toin/asset/mapper/controller_first.css'], array_map(static fn ($autoImport) => $autoImport->path, $controllerFirst->autoImports));

        $controllerSecond = $map['fake-vendor--ux-package1--controller-second'];
        $this->assertFalse($controllerSecond->isLazy);
        $this->assertSame([
            '/path/toin/asset/mapper/controller_second2.css',
            '/path/to@fake-vendor/ux-package1/dist/styles.css',
            '/path/toneeded-vendor/file.css',
            '/path/to@scoped/needed-vendor/the/file2.css',
        ], array_map(static fn ($autoImport) => $autoImport->path, $controllerSecond->autoImports));
    }

    public function testMergedErrorsMentionTheFileOfTheController(): void
    {
        $baseControllersJsonPath = self::writeControllersJson([
            '@fake-vendor/ux-package2' => ['unknown' => ['enabled' => true]],
        ]);
        $controllersJsonPath = self::writeControllersJson([
            '@fake-vendor/ux-package2' => ['hello_controller' => ['enabled' => true]],
        ]);
        $generator = $this->createGenerator([], [], $controllersJsonPath, $baseControllersJsonPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf('(read from "%s").', $baseControllersJsonPath));

        try {
            $generator->getControllersMap();
        } finally {
            unlink($baseControllersJsonPath);
            unlink($controllersJsonPath);
        }
    }

    public function testGetControllersJsonPathsIncludesTheBaseFileFirst(): void
    {
        $this->assertSame(['/app.json'], $this->createGenerator([], [], '/app.json')->getControllersJsonPaths());
        $this->assertSame(['/global.json', '/app.json'], $this->createGenerator([], [], '/app.json', '/global.json')->getControllersJsonPaths());
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $controllers
     */
    #[DataProvider('provideInvalidControllersJson')]
    public function testErrorsMentionTheControllersJsonFile(array $controllers, string $expectedMessage): void
    {
        $controllersJsonPath = self::writeControllersJson($controllers);
        $generator = $this->createGenerator([], [], $controllersJsonPath);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage(\sprintf($expectedMessage, $controllersJsonPath));

        try {
            $generator->getControllersMap();
        } finally {
            unlink($controllersJsonPath);
        }
    }

    /**
     * @return iterable<string, array{array<string, array<string, array<string, mixed>>>, string}>
     */
    public static function provideInvalidControllersJson(): iterable
    {
        yield 'unknown package' => [
            ['@fake-vendor/unknown' => ['hello' => ['enabled' => true]]],
            'Could not find package "fake-vendor/unknown" referred to from controllers.json (read from "%s").',
        ];
        yield 'unknown controller' => [
            ['@fake-vendor/ux-package2' => ['unknown' => ['enabled' => true]]],
            'Controller "@fake-vendor/ux-package2/unknown" does not exist in the "fake-vendor/ux-package2" package (read from "%s").',
        ];
    }

    /**
     * @param array<string, array<string, array<string, mixed>>> $controllers
     */
    private static function writeControllersJson(array $controllers): string
    {
        $path = tempnam(sys_get_temp_dir(), 'stimulus_controllers_json');
        file_put_contents($path, json_encode(['controllers' => $controllers], \JSON_THROW_ON_ERROR));

        return $path;
    }

    private function createGenerator(array $controllerPaths, array $applicationControllerPaths = [], ?string $controllersJsonPath = null, ?string $baseControllersJsonPath = null): ControllersMapGenerator
    {
        $mapper = $this->createStub(AssetMapperInterface::class);
        $mapper->method('getAssetFromSourcePath')
            ->willReturnCallback(static function ($path) {
                $path = str_replace('\\', '/', $path);

                return new MappedAsset(basename($path), $path);
            });

        $autoImportLocator = $this->createStub(AutoImportLocator::class);
        $autoImportLocator->method('locateAutoImport')
            ->willReturnCallback(static function ($path) {
                return new MappedControllerAutoImport('/path/to'.$path, false);
            });

        return new ControllersMapGenerator(
            $mapper,
            new UxPackageReader(__DIR__.'/../fixtures'),
            $controllerPaths,
            $controllersJsonPath ?? __DIR__.'/../fixtures/assets/controllers.json',
            $autoImportLocator,
            $applicationControllerPaths,
            $baseControllersJsonPath,
        );
    }
}
