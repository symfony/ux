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
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\StimulusBundle\AssetMapper\AutoImportLocator;
use Symfony\UX\StimulusBundle\Ux\UxPackageMetadata;

class AutoImportLocatorTest extends TestCase
{
    protected function setUp(): void
    {
        if (!class_exists(ImportMapConfigReader::class)) {
            $this->markTestSkipped('Test requires AssetMapper >= 6.4.');
        }
    }

    public function testLocateAutoImportCanHandleAssetMapperPath()
    {
        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->once())
            ->method('getAsset')
            ->with('foo.css')
            ->willReturn(new MappedAsset('foo.css', '/path/to/foo.css'));

        $locator = new AutoImportLocator(
            $this->createMock(ImportMapConfigReader::class),
            $assetMapper,
        );
        $packageMetadata = new UxPackageMetadata('foo', [], 'bar');
        $autoImport = $locator->locateAutoImport('foo.css', $packageMetadata);
        $this->assertSame('/path/to/foo.css', $autoImport->path);
        $this->assertFalse($autoImport->isBareImport);
    }

    public function testLocateAutoImportHandlesFileInPackage()
    {
        $packageMetadata = new UxPackageMetadata(
            __DIR__.'/../fixtures/vendor/fake-vendor/ux-package1/assets',
            [],
            'fake-vendor/ux-package1'
        );

        $assetMapper = $this->createMock(AssetMapperInterface::class);
        $assetMapper->expects($this->once())
            ->method('getAssetFromSourcePath')
            ->with(__DIR__.'/../fixtures/vendor/fake-vendor/ux-package1/assets/dist/styles.css')
            ->willReturn(new MappedAsset('styles.css', '/path/to/styles.css'));

        $locator = new AutoImportLocator(
            $this->createMock(ImportMapConfigReader::class),
            $assetMapper,
        );

        $autoImport = $locator->locateAutoImport('@fake-vendor/ux-package1/dist/styles.css', $packageMetadata);
        $this->assertSame('/path/to/styles.css', $autoImport->path);
        $this->assertFalse($autoImport->isBareImport);
    }

    public function testLocateAutoImportFromImportMap()
    {
        $importMapConfigReader = $this->createMock(ImportMapConfigReader::class);
        $importMapConfigReader->expects($this->once())
            ->method('findRootImportMapEntry')
            ->with('tom-select/dist/css/tom-select.default.css')
            ->willReturn(ImportMapEntry::createRemote('tom-select/dist/css/tom-select.default.css', ImportMapType::CSS, '/path/to/vendor/tom-select.default.css', '1.0.0', 'tom-select/dist/css/tom-select.default.css', false));

        $locator = new AutoImportLocator(
            $importMapConfigReader,
            $this->createMock(AssetMapperInterface::class),
        );

        $packageMetadata = new UxPackageMetadata('foo', [], 'bar');
        $autoImport = $locator->locateAutoImport('tom-select/dist/css/tom-select.default.css', $packageMetadata);
        $this->assertSame('tom-select/dist/css/tom-select.default.css', $autoImport->path);
        $this->assertTrue($autoImport->isBareImport);
    }
}
