<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\LiveComponent\LiveResponse;

final class LiveResponseTest extends TestCase
{
    private const FILE = __DIR__.'/../Fixtures/files/test.txt';

    public function testDownloadFileWithContent()
    {
        $response = LiveResponse::downloadFile('a,b,c', 'report.csv', 'text/csv');

        $this->assertSame('a,b,c', $response->content);
        $this->assertSame('report.csv', $response->filename);
        $this->assertSame('text/csv', $response->contentType);
        $this->assertSame(5, $response->size);
        $this->assertFalse($response->isDownloadUrl());
    }

    public function testDownloadFileDefaultsToOctetStream()
    {
        $this->assertSame('application/octet-stream', LiveResponse::downloadFile('x', 'f.bin')->contentType);
    }

    public function testDownloadFileWithSplFileInfoDeducesNameAndSize()
    {
        $response = LiveResponse::downloadFile(new \SplFileInfo(self::FILE));

        $this->assertSame('test.txt', $response->filename);
        $this->assertSame(filesize(self::FILE), $response->size);
    }

    public function testDownloadFileWithSplFileObjectUsesThePathBasename()
    {
        // SplFileObject::__toString() returns the current line, so the name must come from the path
        $response = LiveResponse::downloadFile(new \SplFileObject(self::FILE));

        $this->assertSame('test.txt', $response->filename);
    }

    public function testDownloadFileWithAStreamBackedSplFileObjectHasNoSize()
    {
        // getSize() throws on php://temp, so nothing can be deduced
        $temp = new \SplTempFileObject();
        $temp->fwrite('content');

        $this->assertNull(LiveResponse::downloadFile($temp, 'temp.txt')->size);
    }

    public function testDownloadFileWithAResource()
    {
        $resource = fopen('php://memory', 'r+');

        $response = LiveResponse::downloadFile($resource, 'stream.bin', null, 42);

        $this->assertSame($resource, $response->content);
        $this->assertSame(42, $response->size);
        fclose($resource);
    }

    public function testDownloadFileWithAClosureHasNoSizeUnlessGiven()
    {
        $this->assertNull(LiveResponse::downloadFile(static fn () => null, 'f.txt')->size);
    }

    public function testDownloadFileKeepsBytesThatAreNotValidUtf8()
    {
        $response = LiveResponse::downloadFile("\x00\xFF\xFE", 'blob.bin');

        $this->assertSame("\x00\xFF\xFE", $response->content);
        $this->assertSame(3, $response->size);
    }

    public function testDownloadFileRejectsAnUnsupportedContent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The content must be a string, an \SplFileInfo, a resource or a closure, "int" given.');

        LiveResponse::downloadFile(42, 'f.txt');
    }

    public function testDownloadFileRequiresAFilenameForAString()
    {
        // only an SplFileInfo carries a name of its own
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A filename is required');

        LiveResponse::downloadFile('content');
    }

    public function testDownloadFileRejectsABlankFilename()
    {
        $this->expectException(\InvalidArgumentException::class);

        LiveResponse::downloadFile('content', '   ');
    }

    public function testDownloadFileRejectsASizeThatContradictsTheContent()
    {
        // an inexact Content-Length truncates the response or leaves the client waiting
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given size (99) does not match the actual content size (5).');

        LiveResponse::downloadFile('a,b,c', 'report.csv', null, 99);
    }

    public function testDownloadFileAcceptsASizeThatMatches()
    {
        $this->assertSame(5, LiveResponse::downloadFile('a,b,c', 'report.csv', null, 5)->size);
    }

    public function testDownloadFileRejectsAContentTypeWithALineBreak()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot contain a line break');

        LiveResponse::downloadFile('x', 'f.txt', "text/csv\r\nX-Injected: 1");
    }

    public function testDownloadUrl()
    {
        $response = LiveResponse::downloadUrl('/exports/report.csv');

        $this->assertTrue($response->isDownloadUrl());
        $this->assertSame('/exports/report.csv', $response->url);
        $this->assertNull($response->content);
    }

    public function testDownloadUrlRejectsAnEmptyUrl()
    {
        $this->expectException(\InvalidArgumentException::class);

        LiveResponse::downloadUrl('  ');
    }

    public function testRemove()
    {
        $response = LiveResponse::remove();

        $this->assertTrue($response->isRemove());
        $this->assertNull($response->content);
        $this->assertNull($response->url);
    }

    public function testDownloadsAreNotRemovals()
    {
        $this->assertFalse(LiveResponse::downloadFile('x', 'f.bin')->isRemove());
        $this->assertFalse(LiveResponse::downloadUrl('/f.bin')->isRemove());
    }

    public function testARemovalIsNotADownloadUrl()
    {
        $this->assertFalse(LiveResponse::remove()->isDownloadUrl());
    }
}
