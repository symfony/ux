<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Upload;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Editor\Exception\Upload\UnsupportedFileException;
use Symfony\UX\Editor\Upload\DefaultLocalUploadHandler;

final class DefaultLocalUploadHandlerTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/ux-editor-test-'.uniqid();
        mkdir($this->tmp);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmp);
    }

    public function testHappyUpload(): void
    {
        $h = new DefaultLocalUploadHandler($this->tmp, '/uploads', ['image/png'], 2_000_000);
        $file = $this->makeUploadedFile('img.png', 'image/png', 100);
        $r = $h->handle($file, ['profile' => 'default', 'field' => 'body']);
        self::assertStringStartsWith('/uploads/', $r['url']);
        self::assertSame(100, $r['size']);
        self::assertFileExists($this->tmp.'/'.basename($r['url']));
    }

    public function testRejectsUnsupportedMime(): void
    {
        $h = new DefaultLocalUploadHandler($this->tmp, '/uploads', ['image/png'], 2_000_000);
        $file = $this->makeUploadedFile('evil.exe', 'application/x-msdownload', 10);
        $this->expectException(UnsupportedFileException::class);
        $h->handle($file, []);
    }

    public function testRejectsOversize(): void
    {
        $h = new DefaultLocalUploadHandler($this->tmp, '/uploads', ['image/png'], 50);
        $file = $this->makeUploadedFile('big.png', 'image/png', 1000);
        $this->expectException(UnsupportedFileException::class);
        $h->handle($file, []);
    }

    private function makeUploadedFile(string $name, string $mime, int $size): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'ux');
        file_put_contents($tmp, str_repeat('x', $size));

        return new UploadedFile($tmp, $name, $mime, null, true);
    }
}
