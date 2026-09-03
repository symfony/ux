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

use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ImageLimitExceededException;
use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\InspectedImage;
use Symfony\UX\Image\ProcessingLimits;
use Symfony\UX\Image\Processor\ImageInspector;
use Symfony\UX\Image\Processor\InterventionImageProcessor;
use Symfony\UX\Image\Processor\ProcessingWorkspace;
use Symfony\UX\Image\Processor\VariantProcessingPlanner;
use Symfony\UX\Image\Storage\ImageWriteSession;
use Symfony\UX\Image\Storage\LocalStorage;
use Symfony\UX\Image\Storage\StorageInterface;

#[CoversClass(InterventionImageProcessor::class)]
#[CoversClass(InspectedImage::class)]
#[CoversClass(ProcessingWorkspace::class)]
#[CoversClass(VariantProcessingPlanner::class)]
#[CoversClass(ImageWriteSession::class)]
final class InterventionImageProcessorTest extends TestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        foreach ($this->tempDirs as $dir) {
            if (is_dir($dir)) {
                $this->removeDir($dir);
            }
        }
    }

    public function testSupportsIntervention()
    {
        $processor = $this->createProcessor();

        self::assertTrue($processor->supports('intervention'));
    }

    public function testSupportsImagick()
    {
        $processor = $this->createProcessor();

        self::assertTrue($processor->supports('imagick'));
    }

    public function testDoesNotSupportGd()
    {
        $processor = $this->createProcessor();

        self::assertFalse($processor->supports('gd'));
    }

    public function testSupportsVips()
    {
        $processor = $this->createProcessor();

        self::assertTrue($processor->supports('vips'));
    }

    public function testDoesNotSupportOtherDriver()
    {
        $processor = $this->createProcessor();

        self::assertFalse($processor->supports('webp'));
    }

    public function testProcessRejectsSvgByDefault()
    {
        $svgFile = $this->createTempSvgFile();
        $uploadedFile = $this->createUploadedFileMock($svgFile, 'logo.svg', 'image/svg+xml');

        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock->expects(self::never())->method('store');

        $processor = $this->createProcessor(storageMock: $storageMock);
        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('SVG is rejected by default');
        $processor->process($uploadedFile);
    }

    public function testProcessWithoutProfile()
    {
        $jpegFile = $this->createTempJpegFile();
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock->expects(self::once())
            ->method('store')
            ->with($uploadedFile, 'default_public', null)
            ->willReturn('uploads/photo.jpg');

        $processor = $this->createProcessor(storageMock: $storageMock);
        $result = $processor->process($uploadedFile);

        self::assertSame('default_public', $result->storageName);
        self::assertSame('uploads/photo.jpg', $result->path);
        self::assertSame('photo.jpg', $result->originalFilename);
        self::assertSame('image/jpeg', $result->mimeType);
        self::assertNotNull($result->width);
        self::assertNotNull($result->height);
        self::assertEmpty($result->variants);
    }

    public function testProcessWithProfile()
    {
        $jpegFile = $this->createTempJpegFile();
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock->expects(self::once())
            ->method('store')
            ->with($uploadedFile, 'default_public', 'thumbs')
            ->willReturn('thumbs/photo.jpg');

        $storageMock->method('getFilePath')
            ->willReturn($jpegFile);

        $profiles = [
            'thumbnail' => [
                'directory' => 'thumbs',
                'variants' => [
                    'small' => ['width' => 150, 'height' => 100],
                ],
                'formats' => ['webp'],
            ],
        ];

        $processor = $this->createProcessor(storageMock: $storageMock, profiles: $profiles, imageManager: $this->realImageManager());
        $result = $processor->process($uploadedFile, 'thumbnail');

        self::assertSame('default_public', $result->storageName);
        self::assertSame('thumbs/photo.jpg', $result->path);
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
    }

    public function testProcessWithUnknownProfile()
    {
        $jpegFile = $this->createTempJpegFile();
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $storageMock = $this->createStub(StorageInterface::class);
        $storageMock->method('store')->willReturn('uploads/photo.jpg');

        $processor = $this->createProcessor(storageMock: $storageMock, profiles: []);
        $this->expectException(\Symfony\UX\Image\Exception\UnknownImageProfileException::class);
        $this->expectExceptionMessage('Unknown image profile "nonexistent"');
        $processor->process($uploadedFile, 'nonexistent');
    }

    public function testDeferredProcessingSkipsVariants()
    {
        $jpegFile = $this->createTempJpegFile();
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock->expects(self::once())
            ->method('store')
            ->with($uploadedFile, 'default_public', 'thumbs')
            ->willReturn('thumbs/photo.jpg');
        $storageMock->expects(self::never())->method('getFilePath');

        $profiles = [
            'deferred' => [
                'directory' => 'thumbs',
                'processing' => 'deferred',
                'variants' => [
                    'small' => ['width' => 150, 'height' => 100],
                ],
                'formats' => ['webp'],
            ],
        ];

        $processor = $this->createProcessor(storageMock: $storageMock, profiles: $profiles, imageManager: $this->realImageManager());
        $result = $processor->process($uploadedFile, 'deferred');

        self::assertSame('thumbs/photo.jpg', $result->path);
        self::assertEmpty($result->variants);
        self::assertNull($result->profileRevision);
    }

    public function testProcessRejectsNonImageBeforeStorageWhenProcessingIsDeferred()
    {
        $path = tempnam(sys_get_temp_dir(), 'ux_img_test_').'.php';
        $this->tempFiles[] = $path;
        file_put_contents($path, '<?php echo "not an image";');
        $uploadedFile = $this->createUploadedFileMock($path, 'payload.php', 'image/jpeg');
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects(self::never())->method('store');
        $processor = $this->createProcessor(
            storageMock: $storage,
            profiles: ['deferred' => ['processing' => 'deferred']],
        );

        $this->expectException(ImageProcessingException::class);

        $processor->process($uploadedFile, 'deferred');
    }

    public function testImmediateProcessingGeneratesVariants()
    {
        $jpegFile = $this->createTempJpegFile();
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $storageMock = $this->createStub(StorageInterface::class);
        $storageMock->method('store')->willReturn('thumbs/photo.jpg');
        $storageMock->method('getFilePath')->willReturn($jpegFile);

        $profiles = [
            'eager' => [
                'directory' => 'thumbs',
                'processing' => 'immediate',
                'variants' => [
                    'small' => ['width' => 150, 'height' => 100],
                ],
                'formats' => ['webp'],
            ],
        ];

        $processor = $this->createProcessor(storageMock: $storageMock, profiles: $profiles, imageManager: $this->realImageManager());
        $result = $processor->process($uploadedFile, 'eager');

        self::assertNotEmpty($result->variants);
        self::assertArrayHasKey('webp', $result->variants);
        self::assertNotNull($result->profileRevision);

        $variantFiles = glob(\dirname($jpegFile).'/*_small.*');
        foreach ($variantFiles ?: [] as $variantFile) {
            $this->tempFiles[] = $variantFile;
        }
    }

    public function testProcessPassesDirectoryFromProfile()
    {
        $jpegFile = $this->createTempJpegFile();
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock->expects(self::once())
            ->method('store')
            ->with($uploadedFile, 'default_public', 'gallery')
            ->willReturn('gallery/photo.jpg');
        $storageMock->method('getFilePath')->willReturn($jpegFile);

        $profiles = [
            'gallery' => [
                'directory' => 'gallery',
                'variants' => [
                    'thumb' => ['width' => 200, 'height' => 200],
                ],
                'formats' => ['jpeg'],
            ],
        ];

        $processor = $this->createProcessor(storageMock: $storageMock, profiles: $profiles, imageManager: $this->realImageManager());
        $processor->process($uploadedFile, 'gallery');
    }

    public function testGenerateVariantsReturnsEmptyWithoutVariantsKey()
    {
        $processor = $this->createProcessor();
        $asset = new ImageAsset(storageName: 'default_public', path: 'uploads/photo.jpg');

        $result = $processor->generateVariants($asset, ['formats' => ['webp']]);

        self::assertSame([], $result);
    }

    public function testGenerateVariantsReturnsEmptyWithoutFormatsKey()
    {
        $processor = $this->createProcessor();
        $asset = new ImageAsset(storageName: 'default_public', path: 'uploads/photo.jpg');

        $result = $processor->generateVariants($asset, ['variants' => ['small' => ['width' => 100]]]);

        self::assertSame([], $result);
    }

    public function testGenerateVariantsBuildsCorrectPaths()
    {
        $jpegFile = $this->createTempJpegFile(1200, 900);

        $storageMock = $this->createStub(StorageInterface::class);
        $storageMock->method('getFilePath')->willReturn($jpegFile);

        $processor = $this->createProcessor(storageMock: $storageMock, imageManager: $this->realImageManager());

        $asset = new ImageAsset(storageName: 'default_public', path: 'uploads/photo.jpg');
        $config = [
            'variants' => [
                'small' => ['width' => 320, 'height' => 240],
                'large' => ['width' => 1024, 'height' => 768],
            ],
            'formats' => ['webp', 'jpeg'],
        ];

        $result = $processor->generateVariants($asset, $config);

        self::assertArrayHasKey('webp', $result);
        self::assertArrayHasKey('jpeg', $result);
        self::assertCount(2, $result['webp']);
        self::assertCount(2, $result['jpeg']);

        $webpPaths = array_column($result['webp'], 'path');
        self::assertNotEmpty(array_filter($webpPaths, static fn (string $path): bool => 1 === preg_match('#^/uploads/photo_[a-f0-9]{24}_small\\.webp$#', $path)));
        self::assertNotEmpty(array_filter($webpPaths, static fn (string $path): bool => 1 === preg_match('#^/uploads/photo_[a-f0-9]{24}_large\\.webp$#', $path)));

        $jpegPaths = array_column($result['jpeg'], 'path');
        self::assertNotEmpty(array_filter($jpegPaths, static fn (string $path): bool => 1 === preg_match('#^/uploads/photo_[a-f0-9]{24}_small\\.jpeg$#', $path)));
        self::assertNotEmpty(array_filter($jpegPaths, static fn (string $path): bool => 1 === preg_match('#^/uploads/photo_[a-f0-9]{24}_large\\.jpeg$#', $path)));

        $smallWebp = $result['webp'][0];
        self::assertSame(320, $smallWebp['width']);
        self::assertSame(240, $smallWebp['height']);
    }

    public function testOneResizeAndOneEncodingDecodeAreSharedAcrossFormats()
    {
        $jpegFile = $this->createTempJpegFile(100, 50);
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('getFilePath')->willReturn($jpegFile);

        $resizeImage = $this->createMock(ImageInterface::class);
        $resizeImage->expects(self::once())
            ->method('scaleDown')
            ->with(width: 50, height: 25)
            ->willReturnSelf();
        $resizeImage->expects(self::once())
            ->method('save')
            ->willReturnCallback(static function (string $path) use ($resizeImage): ImageInterface {
                $image = imagecreatetruecolor(50, 25);
                imagejpeg($image, $path);

                return $resizeImage;
            });

        $encodingImage = $this->createMock(ImageInterface::class);
        $encodingImage->expects(self::exactly(2))
            ->method('save')
            ->willReturnCallback(static function (string $path, mixed $quality, string $format) use ($encodingImage): ImageInterface {
                $image = imagecreatetruecolor(50, 25);
                match ($format) {
                    'png' => imagepng($image, $path),
                    'jpeg' => imagejpeg($image, $path, (int) $quality),
                };

                return $encodingImage;
            });

        $reads = 0;
        $manager = $this->createMock(ImageManagerInterface::class);
        $manager->expects(self::exactly(2))
            ->method('read')
            ->willReturnCallback(static function () use (&$reads, $resizeImage, $encodingImage): ImageInterface {
                return 1 === ++$reads ? $resizeImage : $encodingImage;
            });

        $processor = $this->createProcessor(storageMock: $storage, imageManager: $manager);
        $result = $processor->generateVariants(
            new ImageAsset('default_public', '/photo.jpeg'),
            [
                'variants' => ['small' => ['width' => 50]],
                'formats' => ['png', 'jpeg'],
            ],
        );

        self::assertCount(1, $result['png']);
        self::assertCount(1, $result['jpeg']);
        foreach (glob(\dirname($jpegFile).'/photo_*_small.*') as $variantFile) {
            $this->tempFiles[] = $variantFile;
        }
    }

    public function testGenerateVariantsEnforcesActualOutputPixelLimit()
    {
        $jpegFile = $this->createTempJpegFile();
        $storageMock = $this->createStub(StorageInterface::class);
        $storageMock->method('getFilePath')->willReturn($jpegFile);
        $processor = $this->createProcessor(
            storageMock: $storageMock,
            imageManager: $this->realImageManager(),
            limits: new ProcessingLimits(maxOutputPixels: 99),
        );

        $this->expectException(ImageLimitExceededException::class);
        $this->expectExceptionMessage('output limit');

        $processor->generateVariants(
            new ImageAsset('default_public', 'uploads/photo.jpg'),
            [
                'variants' => ['small' => ['width' => 10, 'height' => 10]],
                'formats' => ['jpeg'],
            ],
        );
    }

    public function testResizeWithoutImageManagerCopiesFile()
    {
        $inputFile = $this->createTempJpegFile();
        $outputDir = sys_get_temp_dir().'/ux_image_test_resize_'.uniqid();
        $this->tempDirs[] = $outputDir;
        $outputFile = $outputDir.'/output.jpg';

        $processor = $this->createProcessor();
        $processor->resize($inputFile, $outputFile, 200, 150);
        self::assertFileExists($outputFile);
        self::assertFileEquals($inputFile, $outputFile);
    }

    public function testResizeWithoutImageManagerCreatesMissingDir()
    {
        $inputFile = $this->createTempJpegFile();
        $outputDir = sys_get_temp_dir().'/ux_image_test_mkdir_'.uniqid().'/nested';
        $this->tempDirs[] = \dirname($outputDir);
        $outputFile = $outputDir.'/output.jpg';

        $processor = $this->createProcessor();
        $processor->resize($inputFile, $outputFile, 200, 150);

        self::assertDirectoryExists($outputDir);
        self::assertFileExists($outputFile);
    }

    public function testResizeWithImageManager()
    {
        if (!interface_exists(ImageManagerInterface::class)) {
            self::markTestSkipped('Intervention Image is not installed.');
        }

        $inputFile = $this->createTempJpegFile(400, 300);
        $outputDir = sys_get_temp_dir().'/ux_image_test_im_resize_'.uniqid();
        $this->tempDirs[] = $outputDir;
        $outputFile = $outputDir.'/output.jpg';

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->method('width')->willReturn(400);
        $imageMock->method('height')->willReturn(300);
        $imageMock->expects(self::once())
            ->method('scaleDown')
            ->with(width: 200, height: 150)
            ->willReturnSelf();
        $imageMock->expects(self::once())
            ->method('save')
            ->with($outputFile);

        $imageManagerMock = $this->createMock(ImageManagerInterface::class);
        $imageManagerMock->expects(self::once())
            ->method('read')
            ->with($inputFile)
            ->willReturn($imageMock);

        $processor = $this->createProcessor(imageManager: $imageManagerMock);
        $processor->resize($inputFile, $outputFile, 200, 150);
    }

    public function testConvertWithoutImageManagerFailsExplicitly()
    {
        $processor = $this->createProcessor();

        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('ImageManager is required');
        $processor->convert('/tmp/input.jpg', '/tmp/output.webp', 'webp', 80);
    }

    public function testConvertWithImageManager()
    {
        if (!interface_exists(ImageManagerInterface::class)) {
            self::markTestSkipped('Intervention Image is not installed.');
        }

        $inputFile = $this->createTempJpegFile();

        $imageMock = $this->createMock(ImageInterface::class);
        $imageMock->expects(self::once())
            ->method('save')
            ->with('/tmp/output.webp', 90, 'webp');

        $imageManagerMock = $this->createMock(ImageManagerInterface::class);
        $imageManagerMock->expects(self::once())
            ->method('read')
            ->with($inputFile)
            ->willReturn($imageMock);

        $processor = $this->createProcessor(imageManager: $imageManagerMock);
        $processor->convert($inputFile, '/tmp/output.webp', 'webp', 90);
    }

    public function testExtractMetadataDelegatesToInspector()
    {
        $jpegFile = $this->createTempJpegFile(50, 30);
        $uploadedFile = new UploadedFile($jpegFile, 'test.jpg', 'image/jpeg', null, true);

        $processor = $this->createProcessor();
        $metadata = $processor->extractMetadata($uploadedFile);

        self::assertArrayHasKey('width', $metadata);
        self::assertArrayHasKey('height', $metadata);
        self::assertSame(50, $metadata['width']);
        self::assertSame(30, $metadata['height']);
    }

    public function testProcessReadsMetadataBeforeRealLocalStorageMovesFile()
    {
        // End-to-end with a real LocalStorage (no mock): store() moves the uploaded
        // file to its final location. Metadata must be inspected on the original
        // upload beforehand, otherwise getRealPath() no longer resolves and width/
        // height come back null. This guards the store-before-metadata ordering bug.
        $storageRoot = sys_get_temp_dir().'/ux_image_intervention_e2e_'.uniqid();
        $this->tempDirs[] = $storageRoot;

        $jpegFile = $this->createTempJpegFile(50, 30);
        $uploadedFile = $this->createUploadedFileMock($jpegFile, 'photo.jpg', 'image/jpeg');

        $processor = $this->createProcessor(storageMock: new LocalStorage([], $storageRoot));
        $result = $processor->process($uploadedFile);

        self::assertSame(50, $result->width);
        self::assertSame(30, $result->height);
        self::assertFileDoesNotExist($jpegFile, 'store() should have moved the original upload.');
        self::assertFileExists($storageRoot.'/'.$result->storageName.'/'.ltrim($result->path, '/'));
    }

    public function testProcessSvgIsRejectedBeforeStorage()
    {
        $svgFile = $this->createTempSvgFile();
        $uploadedFile = $this->createUploadedFileMock($svgFile, 'icon.svg', 'image/svg+xml');

        $storageMock = $this->createMock(StorageInterface::class);
        $storageMock->expects(self::never())->method('store');

        $processor = $this->createProcessor(storageMock: $storageMock);
        $this->expectException(ImageProcessingException::class);
        $processor->process($uploadedFile);
    }

    /**
     * Creates an UploadedFile stub that returns the given mime type from getMimeType()
     * without requiring the symfony/mime component.
     */
    private function createUploadedFileMock(string $path, string $originalName, string $mimeType): UploadedFile
    {
        return new class($path, $originalName, $mimeType, null, true) extends UploadedFile {
            public function getMimeType(): string
            {
                return $this->getClientMimeType();
            }
        };
    }

    private function createProcessor(
        ?StorageInterface $storageMock = null,
        array $profiles = [],
        ?ImageInspector $imageInspector = null,
        mixed $imageManager = null,
        ?ProcessingLimits $limits = null,
    ): InterventionImageProcessor {
        $storageMock ??= $this->createStub(StorageInterface::class);
        $imageInspector ??= new ImageInspector();

        return new InterventionImageProcessor(
            storageManager: $storageMock,
            profiles: $profiles,
            imageInspector: $imageInspector,
            imageManager: $imageManager,
            limits: $limits,
        );
    }

    private function realImageManager(): ImageManagerInterface
    {
        return \Intervention\Image\ImageManager::withDriver(\Intervention\Image\Drivers\Gd\Driver::class);
    }

    private function createTempJpegFile(int $width = 10, int $height = 10): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ux_img_test_').'.jpg';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        imagejpeg($image, $path);

        return $path;
    }

    private function createTempSvgFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'ux_img_test_').'.svg';
        $this->tempFiles[] = $path;

        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="red"/></svg>');

        return $path;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir);
        foreach ($items as $item) {
            if ('.' === $item || '..' === $item) {
                continue;
            }

            $fullPath = $dir.'/'.$item;
            if (is_dir($fullPath)) {
                $this->removeDir($fullPath);
            } else {
                @unlink($fullPath);
            }
        }

        @rmdir($dir);
    }
}
