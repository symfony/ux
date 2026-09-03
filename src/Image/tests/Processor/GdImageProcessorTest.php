<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Async\ImageProcessingDispatcherInterface;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Processor\ExifOrientation;
use Symfony\UX\Image\Processor\GdImageProcessor;
use Symfony\UX\Image\Processor\ImageInspector;
use Symfony\UX\Image\Processor\ProcessingWorkspace;
use Symfony\UX\Image\Processor\VariantProcessingPlanner;
use Symfony\UX\Image\Storage\ImageWriteSession;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Storage\StreamStorageInterface;

#[CoversClass(GdImageProcessor::class)]
#[CoversClass(ExifOrientation::class)]
#[CoversClass(InspectedImage::class)]
#[CoversClass(ProcessingWorkspace::class)]
#[CoversClass(VariantProcessingPlanner::class)]
#[CoversClass(ImageWriteSession::class)]
#[RequiresPhpExtension('gd')]
final class GdImageProcessorTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    private StorageInterface $storage;
    private ImageInspector $imageInspector;

    protected function setUp(): void
    {
        $this->storage = $this->createStub(StorageInterface::class);
        $this->imageInspector = new ImageInspector();
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    public function testSupportsGd()
    {
        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);

        self::assertTrue($processor->supports('gd'));
    }

    public function testDoesNotSupportImagick()
    {
        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);

        self::assertFalse($processor->supports('imagick'));
    }

    public function testDoesNotSupportOtherDriver()
    {
        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);

        self::assertFalse($processor->supports('vips'));
    }

    public function testProcessRejectsSvgByDefault()
    {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect fill="red" width="100" height="100"/></svg>';
        $svgPath = $this->createTempFile($svgContent, 'svg');

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/svg+xml');
        $file->method('getClientOriginalName')->willReturn('test.svg');
        $file->method('getRealPath')->willReturn($svgPath);

        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('store');

        $processor = new GdImageProcessor($storage, [], $this->imageInspector);
        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('SVG is rejected by default');
        $processor->process($file);
    }

    public function testProcessWithoutProfile()
    {
        $jpegPath = $this->createTempJpeg(200, 100);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);

        $this->storage->method('store')->willReturn('uploads/photo.jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $result = $processor->process($file);

        self::assertSame('image/jpeg', $result->mimeType);
        self::assertSame(200, $result->width);
        self::assertSame(100, $result->height);
        self::assertSame([], $result->variants);
    }

    public function testProcessWithProfile()
    {
        $jpegPath = $this->createTempJpeg(400, 300);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);

        $this->storage->method('store')->willReturn('uploads/photo.jpg');
        $this->storage->method('getFilePath')->willReturn($jpegPath);

        $profiles = [
            'thumbnail' => [
                'variants' => [
                    'small' => ['width' => 100, 'height' => 75],
                ],
                'formats' => ['webp'],
            ],
        ];

        $processor = new GdImageProcessor($this->storage, $profiles, $this->imageInspector);
        $result = $processor->process($file, 'thumbnail');

        self::assertNotEmpty($result->variants);
        self::assertArrayHasKey('webp', $result->variants);
        self::assertSame([
            'name' => 'small',
            'format' => 'webp',
            'mimeType' => 'image/webp',
            'mode' => 'fit',
            'quality' => 80,
            'position' => 'center',
        ], array_intersect_key($result->variants['webp'][0], array_flip(['name', 'format', 'mimeType', 'mode', 'quality', 'position'])));

        // Clean up generated variant files
        $dir = \dirname($jpegPath);
        foreach (glob($dir.'/photo_*_small.*') as $variantFile) {
            $this->tempFiles[] = $variantFile;
        }
    }

    public function testProcessWithUnknownProfile()
    {
        $jpegPath = $this->createTempJpeg(200, 100);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);

        $this->storage->method('store')->willReturn('uploads/photo.jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $this->expectException(\Symfony\UX\Image\Exception\UnknownImageProfileException::class);
        $this->expectExceptionMessage('Unknown image profile "nonexistent"');
        $processor->process($file, 'nonexistent');
    }

    public function testDeferredProcessingSkipsVariants()
    {
        $jpegPath = $this->createTempJpeg(400, 300);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);

        $this->storage->method('store')->willReturn('uploads/photo.jpg');
        $this->storage->method('getFilePath')->willReturn($jpegPath);

        $profiles = [
            'deferred' => [
                'processing' => 'deferred',
                'variants' => [
                    'small' => ['width' => 100, 'height' => 75],
                ],
                'formats' => ['webp'],
            ],
        ];

        $processor = new GdImageProcessor($this->storage, $profiles, $this->imageInspector);
        $result = $processor->process($file, 'deferred');

        self::assertSame('uploads/photo.jpg', $result->path);
        self::assertSame([], $result->variants);
        self::assertNull($result->profileRevision);
        self::assertSame([], glob(\dirname($jpegPath).'/photo_*_small.webp'));
    }

    public function testProcessRejectsNonImageBeforeStorageWhenProcessingIsDeferred()
    {
        $path = $this->createTempFile('<?php echo "not an image";', 'php');
        $file = new UploadedFile($path, 'payload.php', 'image/jpeg', null, true);
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('store');
        $processor = new GdImageProcessor(
            $storage,
            ['deferred' => ['processing' => 'deferred']],
            $this->imageInspector,
        );

        $this->expectException(ImageProcessingException::class);

        $processor->process($file, 'deferred');
    }

    public function testAsyncProfileDispatchesWithoutGeneratingVariants()
    {
        $jpegPath = $this->createTempJpeg(200, 100);
        $file = $this->createStub(UploadedFile::class);
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);
        $this->storage->method('store')->willReturn('uploads/photo.jpg');
        $dispatcher = $this->createMock(ImageProcessingDispatcherInterface::class);
        $dispatcher->expects(self::once())->method('dispatch')
            ->with(self::callback(static fn (ImageAsset $asset): bool => 'background' === $asset->profile
                && 200 === $asset->width
                && 100 === $asset->height
                && null === $asset->profileRevision), 'background');
        $processor = new GdImageProcessor(
            $this->storage,
            ['background' => ['processing' => 'async', 'variants' => ['large' => ['width' => 2000]], 'formats' => ['jpeg']]],
            $this->imageInspector,
            asyncDispatcher: $dispatcher,
        );

        $asset = $processor->process($file, 'background');

        self::assertSame([], $asset->variants);
        self::assertSame('background', $asset->profile);
        self::assertNull($asset->profileRevision);
    }

    public function testImmediateProcessingGeneratesVariants()
    {
        $jpegPath = $this->createTempJpeg(400, 300);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);

        $this->storage->method('store')->willReturn('uploads/photo.jpg');
        $this->storage->method('getFilePath')->willReturn($jpegPath);

        $profiles = [
            'eager' => [
                'processing' => 'immediate',
                'variants' => [
                    'small' => ['width' => 100, 'height' => 75],
                ],
                'formats' => ['webp'],
            ],
        ];

        $processor = new GdImageProcessor($this->storage, $profiles, $this->imageInspector);
        $result = $processor->process($file, 'eager');

        self::assertNotEmpty($result->variants);
        self::assertArrayHasKey('webp', $result->variants);
        self::assertNotNull($result->profileRevision);

        $dir = \dirname($jpegPath);
        foreach (glob($dir.'/photo_*_small.*') as $variantFile) {
            $this->tempFiles[] = $variantFile;
        }
    }

    public function testSynchronousStreamProcessingDoesNotDownloadStoredOriginal()
    {
        $pngPath = $this->createTempPng(200, 100);
        $file = new UploadedFile($pngPath, 'photo.png', 'image/png', null, true);
        $storage = $this->createMock(StreamStorageInterface::class);
        $storage->expects(self::once())
            ->method('store')
            ->with($file, 'media', null)
            ->willReturn('/photo.png');
        $storage->expects(self::never())->method('readStream');
        $storage->expects(self::exactly(2))->method('writeStream');

        $processor = new GdImageProcessor(
            $storage,
            [
                'responsive' => [
                    'formats' => ['jpeg', 'png'],
                    'variants' => ['small' => ['width' => 100]],
                ],
            ],
            $this->imageInspector,
        );

        $asset = $processor->process($file, 'responsive', 'media');

        self::assertCount(1, $asset->variants['jpeg']);
        self::assertCount(1, $asset->variants['png']);
    }

    public function testGenerateVariantsReturnsEmptyWithoutVariantsKey()
    {
        $asset = new ImageAsset('default_public', 'uploads/photo.jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $result = $processor->generateVariants($asset, [
            'formats' => ['webp'],
        ]);

        self::assertSame([], $result);
    }

    public function testGenerateVariantsReturnsEmptyWithoutFormatsKey()
    {
        $asset = new ImageAsset('default_public', 'uploads/photo.jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $result = $processor->generateVariants($asset, [
            'variants' => ['small' => ['width' => 100, 'height' => 75]],
        ]);

        self::assertSame([], $result);
    }

    public function testGenerateVariantsEnforcesActualOutputPixelLimit()
    {
        $jpegPath = $this->createTempJpeg(200, 100);
        $this->storage->method('getFilePath')->willReturn($jpegPath);
        $processor = new GdImageProcessor(
            $this->storage,
            [],
            $this->imageInspector,
            limits: new ProcessingLimits(maxOutputPixels: 4_999),
        );

        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('output limit');

        $processor->generateVariants(
            new ImageAsset('default_public', 'uploads/photo.jpg'),
            [
                'variants' => ['small' => ['width' => 100, 'height' => 75]],
                'formats' => ['jpeg'],
            ],
        );
    }

    public function testResizeFitMode()
    {
        $inputPath = $this->createTempJpeg(200, 100);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 100, 0);
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(100, $size[0]);
        self::assertSame(50, $size[1]);
    }

    public function testResizeWithHeightOnly()
    {
        $inputPath = $this->createTempJpeg(200, 100);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 0, 50);
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(100, $size[0]);
        self::assertSame(50, $size[1]);
    }

    public function testResizeWithZeroDimensions()
    {
        $inputPath = $this->createTempJpeg(200, 100);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 0, 0);
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(200, $size[0]);
        self::assertSame(100, $size[1]);
    }

    public function testResizeCropMode()
    {
        $inputPath = $this->createTempJpeg(200, 100);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 50, 50, 'crop');
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(50, $size[0]);
        self::assertSame(50, $size[1]);
    }

    public function testResizeCropPositionTop()
    {
        $inputPath = $this->createTempJpeg(200, 200);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 100, 50, 'crop', 'top');
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(100, $size[0]);
        self::assertSame(50, $size[1]);
    }

    public function testResizeCropPositionBottom()
    {
        $inputPath = $this->createTempJpeg(200, 200);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 100, 50, 'crop', 'bottom');
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(100, $size[0]);
        self::assertSame(50, $size[1]);
    }

    public function testResizeCropPositionLeft()
    {
        $inputPath = $this->createTempJpeg(200, 200);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 50, 100, 'crop', 'left');
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(50, $size[0]);
        self::assertSame(100, $size[1]);
    }

    public function testResizeCropPositionRight()
    {
        $inputPath = $this->createTempJpeg(200, 200);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 50, 100, 'crop', 'right');
        self::assertFileExists($outputPath);

        $size = getimagesize($outputPath);
        self::assertSame(50, $size[0]);
        self::assertSame(100, $size[1]);
    }

    public function testResizeInvalidImageThrowsException()
    {
        $invalidPath = $this->createTempFile('not an image', 'txt');
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);

        $this->expectException(ImageProcessingException::class);
        $processor->resize($invalidPath, $outputPath, 100, 100);
    }

    public function testResizeWithNegativeDimensionsThrowsException()
    {
        $inputPath = $this->createTempJpeg(200, 100);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);

        $this->expectException(ImageProcessingException::class);
        $processor->resize($inputPath, $outputPath, -1, 100);
    }

    public function testConvertToWebp()
    {
        $inputPath = $this->createTempJpeg(100, 100);
        $outputPath = $this->createTempOutputPath('webp');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->convert($inputPath, $outputPath, 'webp');
        self::assertFileExists($outputPath);

        $info = getimagesize($outputPath);
        self::assertSame(\IMAGETYPE_WEBP, $info[2]);
    }

    public function testConvertToPng()
    {
        $inputPath = $this->createTempJpeg(100, 100);
        $outputPath = $this->createTempOutputPath('png');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->convert($inputPath, $outputPath, 'png');
        self::assertFileExists($outputPath);

        $info = getimagesize($outputPath);
        self::assertSame(\IMAGETYPE_PNG, $info[2]);
    }

    public function testConvertToJpeg()
    {
        $inputPath = $this->createTempPng(100, 100);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->convert($inputPath, $outputPath, 'jpeg');
        self::assertFileExists($outputPath);

        $info = getimagesize($outputPath);
        self::assertSame(\IMAGETYPE_JPEG, $info[2]);
    }

    #[RequiresPhpExtension('exif')]
    public function testResizeAppliesExifOrientationBeforeCalculatingGeometry()
    {
        $inputPath = $this->withExifOrientation($this->createTempJpeg(40, 20), 6);
        $outputPath = $this->createTempOutputPath('jpg');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->resize($inputPath, $outputPath, 10, 0);

        $size = getimagesize($outputPath);
        self::assertSame(10, $size[0]);
        self::assertSame(20, $size[1]);
    }

    #[RequiresPhpExtension('exif')]
    public function testConvertBakesExifOrientationIntoOutputPixels()
    {
        $inputPath = $this->withExifOrientation($this->createTempJpeg(40, 20), 6);
        $outputPath = $this->createTempOutputPath('png');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $processor->convert($inputPath, $outputPath, 'png');

        $size = getimagesize($outputPath);
        self::assertSame(20, $size[0]);
        self::assertSame(40, $size[1]);
    }

    public function testAppliesEveryExifOrientationTransformation()
    {
        foreach ([2, 3, 4, 5, 7, 8] as $orientation) {
            $path = $this->withExifOrientation($this->createTempJpeg(4, 2), $orientation);
            $image = imagecreatefromjpeg($path);
            self::assertInstanceOf(\GdImage::class, $image);

            $oriented = ExifOrientation::fromJpeg($path)->applyTo($image);
            self::assertInstanceOf(\GdImage::class, $oriented);
        }
    }

    public function testConvertInvalidFileThrowsException()
    {
        $invalidPath = $this->createTempFile('not an image', 'txt');
        $outputPath = $this->createTempOutputPath('webp');

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);

        $this->expectException(ImageProcessingException::class);
        $processor->convert($invalidPath, $outputPath, 'webp');
    }

    public function testConvertRejectsUnsupportedOutputFormat()
    {
        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('Unsupported image format');

        new GdImageProcessor($this->storage, [], $this->imageInspector)
            ->convert($this->createTempJpeg(10, 10), $this->createTempOutputPath('gif'), 'gif');
    }

    public function testExtractMetadataDelegatesToInspector()
    {
        $jpegPath = $this->createTempJpeg(320, 240);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getRealPath')->willReturn($jpegPath);

        $processor = new GdImageProcessor($this->storage, [], $this->imageInspector);
        $metadata = $processor->extractMetadata($file);

        self::assertSame(320, $metadata['width']);
        self::assertSame(240, $metadata['height']);
        self::assertSame('image/jpeg', $metadata['mime']);
        self::assertSame('jpeg', $metadata['format']);
    }

    public function testProcessPassesDirectoryFromProfile()
    {
        $jpegPath = $this->createTempJpeg(200, 100);

        $file = $this->createStub(UploadedFile::class);
        $file->method('getMimeType')->willReturn('image/jpeg');
        $file->method('getClientOriginalName')->willReturn('photo.jpg');
        $file->method('getRealPath')->willReturn($jpegPath);

        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::once())
            ->method('store')
            ->with($file, 'default_public', 'products')
            ->willReturn('products/photo.jpg');

        $profiles = [
            'product' => [
                'directory' => 'products',
            ],
        ];

        $processor = new GdImageProcessor($storage, $profiles, $this->imageInspector);
        $processor->process($file, 'product');
    }

    private function createTempJpeg(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gd_test_').'.jpg';
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 100, 150, 200);
        imagefill($image, 0, 0, $color);
        imagejpeg($image, $path, 90);
        unset($image);

        // Remove the intermediate tempnam file (without .jpg)
        $base = substr($path, 0, -4);
        if (file_exists($base)) {
            @unlink($base);
        }

        $this->tempFiles[] = $path;

        return $path;
    }

    private function createTempPng(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gd_test_').'.png';
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 100, 150, 200);
        imagefill($image, 0, 0, $color);
        imagepng($image, $path);
        unset($image);

        $base = substr($path, 0, -4);
        if (file_exists($base)) {
            @unlink($base);
        }

        $this->tempFiles[] = $path;

        return $path;
    }

    private function createTempFile(string $content, string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gd_test_').'.'.$extension;
        file_put_contents($path, $content);

        $base = substr($path, 0, -(\strlen($extension) + 1));
        if (file_exists($base)) {
            @unlink($base);
        }

        $this->tempFiles[] = $path;

        return $path;
    }

    private function createTempOutputPath(string $extension): string
    {
        $path = tempnam(sys_get_temp_dir(), 'gd_out_').'.'.$extension;

        $base = substr($path, 0, -(\strlen($extension) + 1));
        if (file_exists($base)) {
            @unlink($base);
        }

        $this->tempFiles[] = $path;

        return $path;
    }

    private function withExifOrientation(string $path, int $orientation): string
    {
        $jpeg = file_get_contents($path);
        self::assertIsString($jpeg);
        self::assertStringStartsWith("\xFF\xD8", $jpeg);

        $tiff = "II\x2A\x00\x08\x00\x00\x00"
            ."\x01\x00"
            ."\x12\x01\x03\x00\x01\x00\x00\x00"
            .pack('v', $orientation)."\x00\x00"
            ."\x00\x00\x00\x00";
        $payload = "Exif\x00\x00".$tiff;
        $jpeg = substr($jpeg, 0, 2)."\xFF\xE1".pack('n', \strlen($payload) + 2).$payload.substr($jpeg, 2);
        file_put_contents($path, $jpeg);

        return $path;
    }
}
