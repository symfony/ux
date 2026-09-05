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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Processor\ChainImageProcessor;
use Symfony\UX\Image\Processor\ImageDriverInterface;

#[CoversClass(ChainImageProcessor::class)]
final class ChainImageProcessorTest extends TestCase
{
    public function testDelegatesToSupportingProcessor()
    {
        $expectedAsset = new ImageAsset('default', '/default/image.jpg');

        $processor = $this->createMock(ImageDriverInterface::class);
        $processor->method('supports')->with('gd')->willReturn(true);
        $processor->expects(self::once())->method('process')->willReturn($expectedAsset);

        $chain = new ChainImageProcessor([$processor], 'gd');

        $file = $this->createStub(UploadedFile::class);
        $result = $chain->process($file);

        self::assertSame($expectedAsset, $result);
    }

    public function testThrowsWhenNoProcessorSupports()
    {
        $chain = new ChainImageProcessor([], 'gd');

        $file = $this->createStub(UploadedFile::class);

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('No image processor found for driver "gd"');

        $chain->process($file);
    }

    public function testThrowsWhenNoProcessorSupportsDriver()
    {
        $processor = $this->createStub(ImageDriverInterface::class);
        $processor->method('supports')->willReturn(false);

        $chain = new ChainImageProcessor([$processor], 'imagick');

        $file = $this->createStub(UploadedFile::class);

        $this->expectException(ExceptionInterface::class);
        $this->expectExceptionMessage('No image processor found for driver "imagick"');

        $chain->process($file);
    }

    public function testSupportsReturnsTrueWhenAnyProcessorSupports()
    {
        $proc1 = $this->createStub(ImageDriverInterface::class);
        $proc1->method('supports')->willReturnCallback(static fn (string $d) => 'gd' === $d);

        $proc2 = $this->createStub(ImageDriverInterface::class);
        $proc2->method('supports')->willReturnCallback(static fn (string $d) => 'imagick' === $d);

        $chain = new ChainImageProcessor([$proc1, $proc2]);

        self::assertTrue($chain->supports('gd'));
        self::assertTrue($chain->supports('imagick'));
    }

    public function testSupportsReturnsFalseWhenNoneSupport()
    {
        $processor = $this->createStub(ImageDriverInterface::class);
        $processor->method('supports')->willReturn(false);

        $chain = new ChainImageProcessor([$processor]);

        self::assertFalse($chain->supports('vips'));
    }

    public function testDelegatesGenerateVariants()
    {
        $asset = new ImageAsset('default', '/default/image.jpg');
        $returnedVariants = ['webp' => []];

        $processor = $this->createMock(ImageDriverInterface::class);
        $processor->method('supports')->with('gd')->willReturn(true);
        $processor->expects(self::once())->method('generateVariants')
            ->with($asset, ['thumb' => ['width' => 100]])
            ->willReturn($returnedVariants);

        $chain = new ChainImageProcessor([$processor], 'gd');

        $result = $chain->generateVariants($asset, ['thumb' => ['width' => 100]]);

        self::assertSame($returnedVariants, $result);
    }

    public function testDelegatesResize()
    {
        $processor = $this->createMock(ImageDriverInterface::class);
        $processor->method('supports')->with('gd')->willReturn(true);
        $processor->expects(self::once())->method('resize')
            ->with('/in.jpg', '/out.jpg', 100, 100, 'fit', 'center')
        ;

        $chain = new ChainImageProcessor([$processor], 'gd');

        $chain->resize('/in.jpg', '/out.jpg', 100, 100);
    }

    public function testDelegatesConvert()
    {
        $processor = $this->createMock(ImageDriverInterface::class);
        $processor->method('supports')->with('gd')->willReturn(true);
        $processor->expects(self::once())->method('convert')
            ->with('/in.jpg', '/out.webp', 'webp', 80)
        ;

        $chain = new ChainImageProcessor([$processor], 'gd');

        $chain->convert('/in.jpg', '/out.webp', 'webp');
    }

    public function testDelegatesExtractMetadata()
    {
        $processor = $this->createMock(ImageDriverInterface::class);
        $processor->method('supports')->with('gd')->willReturn(true);
        $processor->expects(self::once())->method('extractMetadata')
            ->willReturn(['width' => 800, 'height' => 600]);

        $chain = new ChainImageProcessor([$processor], 'gd');

        $file = $this->createStub(UploadedFile::class);
        $meta = $chain->extractMetadata($file);

        self::assertSame(['width' => 800, 'height' => 600], $meta);
    }

    public function testFirstSupportingProcessorIsUsed()
    {
        $proc1 = $this->createMock(ImageDriverInterface::class);
        $proc1->method('supports')->willReturn(true);
        $proc1->expects(self::once())->method('extractMetadata')->willReturn(['first' => true]);

        $proc2 = $this->createMock(ImageDriverInterface::class);
        $proc2->method('supports')->willReturn(true);
        $proc2->expects(self::never())->method('extractMetadata');

        $chain = new ChainImageProcessor([$proc1, $proc2], 'gd');

        $file = $this->createStub(UploadedFile::class);
        $result = $chain->extractMetadata($file);

        self::assertSame(['first' => true], $result);
    }
}
