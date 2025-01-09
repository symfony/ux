<?php

namespace Symfony\UX\LiveComponent\Tests\Unit;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\UX\LiveComponent\LiveResponse;
use PHPUnit\Framework\TestCase;

class LiveResponseTest extends TestCase
{
    public function testSendFileWithStringPath(): void
    {
        $filePath = __DIR__.'/../fixtures/files/test.txt';
        $response = LiveResponse::file($filePath);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('attachment; filename=test.txt', $response->headers->get('Content-Disposition'));
        $this->assertEquals('application/octet-stream', $response->headers->get('Content-Type'));
    }

    public function testSendFileWithSplFileInfo(): void
    {
        $file = new File(__DIR__.'/../fixtures/files/test.txt');
        $response = LiveResponse::file($file, 'custom-name.txt', 'text/plain');

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('attachment; filename=custom-name.txt', $response->headers->get('Content-Disposition'));
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
    }

    public function testSendFileWithSplTempFileObject(): void
    {
        $tempFile = new \SplTempFileObject();
        $tempFile->fwrite('Temporary content');
        $response = LiveResponse::file($tempFile, size: 17);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertEquals('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertEquals(17, $response->headers->get('Content-Length'));
    }

     public function testStreamFileWithResource(): void
    {
        $file = fopen(__DIR__.'/../fixtures/files/test.txt', 'rb');
        $response = LiveResponse::streamFile($file, 'streamed-file.txt');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('attachment; filename=streamed-file.txt', $response->headers->get('Content-Disposition'));
        $this->assertEquals('application/octet-stream', $response->headers->get('Content-Type'));
        fclose($file);
    }

    public function testStreamFileWithClosure(): void
    {
        $closure = function () {
            echo 'Streaming content';
        };

        $response = LiveResponse::streamFile($closure, 'streamed-closure.txt', 'text/plain');

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertEquals('attachment; filename=streamed-closure.txt', $response->headers->get('Content-Disposition'));
        $this->assertEquals('text/plain', $response->headers->get('Content-Type'));
    }

    public function testStreamFileWithInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file must be a resource or a closure, "string" given.');

        LiveResponse::streamFile('invalid-type', 'invalid.txt');
    }
}
