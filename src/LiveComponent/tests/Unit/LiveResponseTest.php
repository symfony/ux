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

    public function testDownloadFileWithContent(): void
    {
        $response = LiveResponse::downloadFile('a,b,c', 'report.csv', 'text/csv');

        $this->assertSame('a,b,c', $response->content);
        $this->assertSame('report.csv', $response->filename);
        $this->assertSame('text/csv', $response->contentType);
        $this->assertSame(5, $response->size);
        $this->assertFalse($response->isDownloadUrl());
    }

    public function testDownloadFileDefaultsToOctetStream(): void
    {
        $this->assertSame('application/octet-stream', LiveResponse::downloadFile('x', 'f.bin')->contentType);
    }

    public function testDownloadFileWithSplFileInfoDeducesNameAndSize(): void
    {
        $response = LiveResponse::downloadFile(new \SplFileInfo(self::FILE));

        $this->assertSame('test.txt', $response->filename);
        $this->assertSame(filesize(self::FILE), $response->size);
    }

    public function testDownloadFileWithSplFileObjectUsesThePathBasename(): void
    {
        // SplFileObject::__toString() returns the current line, so the name must come from the path
        $response = LiveResponse::downloadFile(new \SplFileObject(self::FILE));

        $this->assertSame('test.txt', $response->filename);
    }

    public function testDownloadFileWithAStreamBackedSplFileObjectHasNoSize(): void
    {
        // getSize() throws on php://temp, so nothing can be deduced
        $temp = new \SplTempFileObject();
        $temp->fwrite('content');

        $this->assertNull(LiveResponse::downloadFile($temp, 'temp.txt')->size);
    }

    public function testDownloadFileWithAResource(): void
    {
        $resource = fopen('php://memory', 'r+');

        $response = LiveResponse::downloadFile($resource, 'stream.bin', null, 42);

        $this->assertSame($resource, $response->content);
        $this->assertSame(42, $response->size);
        fclose($resource);
    }

    public function testDownloadFileWithAClosureHasNoSizeUnlessGiven(): void
    {
        $this->assertNull(LiveResponse::downloadFile(static fn () => null, 'f.txt')->size);
    }

    public function testDownloadFileKeepsBytesThatAreNotValidUtf8(): void
    {
        $response = LiveResponse::downloadFile("\x00\xFF\xFE", 'blob.bin');

        $this->assertSame("\x00\xFF\xFE", $response->content);
        $this->assertSame(3, $response->size);
    }

    public function testDownloadFileRejectsAnUnsupportedContent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The content must be a string, an \SplFileInfo, a resource or a closure, "int" given.');

        LiveResponse::downloadFile(42, 'f.txt');
    }

    public function testDownloadFileRequiresAFilenameForAString(): void
    {
        // only an SplFileInfo carries a name of its own
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A filename is required');

        LiveResponse::downloadFile('content');
    }

    public function testDownloadFileRejectsABlankFilename(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        LiveResponse::downloadFile('content', '   ');
    }

    public function testDownloadFileRejectsASizeThatContradictsTheContent(): void
    {
        // an inexact Content-Length truncates the response or leaves the client waiting
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The given size (99) does not match the actual content size (5).');

        LiveResponse::downloadFile('a,b,c', 'report.csv', null, 99);
    }

    public function testDownloadFileAcceptsASizeThatMatches(): void
    {
        $this->assertSame(5, LiveResponse::downloadFile('a,b,c', 'report.csv', null, 5)->size);
    }

    public function testDownloadFileRejectsAContentTypeWithALineBreak(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot contain a line break');

        LiveResponse::downloadFile('x', 'f.txt', "text/csv\r\nX-Injected: 1");
    }

    public function testDownloadUrl(): void
    {
        $response = LiveResponse::downloadUrl('/exports/report.csv');

        $this->assertTrue($response->isDownloadUrl());
        $this->assertSame('/exports/report.csv', $response->url);
        $this->assertNull($response->content);
    }

    public function testDownloadUrlRejectsAnEmptyUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        LiveResponse::downloadUrl('  ');
    }

    public function testDataEncodesAnArrayAsJson(): void
    {
        $response = LiveResponse::data(['results' => ['foo', 'bar'], 'total' => 2]);

        $this->assertTrue($response->isData());
        $this->assertSame('{"results":["foo","bar"],"total":2}', $response->content);
        $this->assertSame('application/json', $response->contentType);
    }

    public function testDataEncodesAJsonSerializable(): void
    {
        $data = new class implements \JsonSerializable {
            public function jsonSerialize(): array
            {
                return ['id' => 42];
            }
        };

        $this->assertSame('{"id":42}', LiveResponse::data($data)->content);
    }

    public function testDataEncodesScalarsAndNullAsJson(): void
    {
        $this->assertSame('42', LiveResponse::data(42)->content);
        $this->assertSame('true', LiveResponse::data(true)->content);
        $this->assertSame('null', LiveResponse::data(null)->content);
    }

    public function testDataEncodesJsonLikeAJsonResponse(): void
    {
        $this->assertSame('{"html":"\u003Cb\u003E\u0026\u0027\u0022"}', LiveResponse::data(['html' => '<b>&\'"'])->content);
    }

    public function testDataKeepsTheGivenContentTypeForEncodedData(): void
    {
        $this->assertSame('application/problem+json', LiveResponse::data(['title' => 'Oops'], 'application/problem+json')->contentType);
    }

    public function testDataRejectsWhatCannotBeEncoded(): void
    {
        $this->expectException(\JsonException::class);

        LiveResponse::data(['value' => \NAN]);
    }

    public function testDataSendsAStringAsIs(): void
    {
        $response = LiveResponse::data('<result><item>foo</item></result>', 'application/xml');

        $this->assertTrue($response->isData());
        $this->assertSame('<result><item>foo</item></result>', $response->content);
        $this->assertSame('application/xml', $response->contentType);
    }

    public function testDataRequiresAContentTypeForAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A content type is required when the data is a string.');

        LiveResponse::data('foo');
    }

    public function testDataRejectsABlankContentTypeForAString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A content type is required when the data is a string.');

        LiveResponse::data('foo', ' ');
    }

    public function testDataRejectsABlankContentTypeForEncodedData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The content type cannot be blank.');

        LiveResponse::data(['foo' => 'bar'], ' ');
    }

    public function testDataRejectsAContentTypeWithALineBreak(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The content type cannot contain a line break.');

        LiveResponse::data('foo', "text/plain\r\nX-Injected: 1");
    }

    public function testDataIsNeitherADownloadNorARemoval(): void
    {
        $response = LiveResponse::data(['foo' => 'bar']);

        $this->assertFalse($response->isDownloadUrl());
        $this->assertFalse($response->isRemove());
        $this->assertNull($response->filename);
    }

    public function testDownloadsAndRemovalsAreNotData(): void
    {
        $this->assertFalse(LiveResponse::downloadFile('x', 'f.bin')->isData());
        $this->assertFalse(LiveResponse::downloadUrl('/f.bin')->isData());
        $this->assertFalse(LiveResponse::remove()->isData());
    }

    public function testRemove(): void
    {
        $response = LiveResponse::remove();

        $this->assertTrue($response->isRemove());
        $this->assertNull($response->content);
        $this->assertNull($response->url);
    }

    public function testDownloadsAreNotRemovals(): void
    {
        $this->assertFalse(LiveResponse::downloadFile('x', 'f.bin')->isRemove());
        $this->assertFalse(LiveResponse::downloadUrl('/f.bin')->isRemove());
    }

    public function testARemovalIsNotADownloadUrl(): void
    {
        $this->assertFalse(LiveResponse::remove()->isDownloadUrl());
    }
}
