<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class UploadControllerTest extends KernelTestCase
{
    use HasBrowser;

    private const FIXTURE_FILE_DIR = __DIR__.'/../../Fixtures/files';
    private const UPLOAD_FILE_DIR = __DIR__.'/../../../var/live-tmp';
    private const TEMP_DIR = __DIR__.'/../../../var/tmp';

    /**
     * @before
     */
    public static function prepareTempDirs(): void
    {
        (new Filesystem())->remove(self::TEMP_DIR);
        (new Filesystem())->remove(self::UPLOAD_FILE_DIR);
        (new Filesystem())->mirror(self::FIXTURE_FILE_DIR, self::TEMP_DIR);
    }

    public function testCanUploadASingleFile(): void
    {
        $json = $this->browser()
            ->post('/live/upload', ['files' => [new UploadedFile(self::TEMP_DIR.'/image1.png', 'image1.png', test: true)]])
            ->assertSuccessful()
            ->response()
            ->assertJson()
            ->json()
        ;

        $this->assertIsArray($json);
        $this->assertCount(1, $json);
        $this->assertArrayHasKey('image1.png', $json);
        $this->assertFileExists(self::UPLOAD_FILE_DIR.'/'.$json['image1.png']);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/'.$json['image1.png']);
    }

    public function testCanUploadMultipleFiles(): void
    {
        $json = $this->browser()
            ->post('/live/upload', ['files' => [
                new UploadedFile(self::TEMP_DIR.'/image1.png', 'image1.png', test: true),
                new UploadedFile(self::TEMP_DIR.'/image2.png', 'image2.png', test: true),
            ]])
            ->assertSuccessful()
            ->response()
            ->assertJson()
            ->json()
        ;

        $this->assertIsArray($json);
        $this->assertCount(2, $json);
        $this->assertArrayHasKey('image1.png', $json);
        $this->assertArrayHasKey('image2.png', $json);
        $this->assertFileExists(self::UPLOAD_FILE_DIR.'/'.$json['image1.png']);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/'.$json['image1.png']);
        $this->assertFileExists(self::UPLOAD_FILE_DIR.'/'.$json['image2.png']);
        $this->assertFileDoesNotExist(self::TEMP_DIR.'/'.$json['image2.png']);
    }

    public function testUploadEndpointMustBePost(): void
    {
        $this->markTestIncomplete();
    }

    public function testUploadEndpointMustBeSigned(): void
    {
        $this->markTestIncomplete();
    }

    public function testUploadEndpointIsTemporary(): void
    {
        $this->markTestIncomplete();
    }

    public function testCanPreviewImages(): void
    {
        (new Filesystem())->copy(self::FIXTURE_FILE_DIR.'/image1.png', self::UPLOAD_FILE_DIR.'/image1.png');

        $this->browser()
            ->visit('/live/preview/image1.png')
            ->assertSuccessful()
            ->assertHeaderContains('Content-Type', 'image/png')
            ->assertContains(file_get_contents(self::FIXTURE_FILE_DIR.'/image1.png'))
        ;
    }

    public function testCannotPreviewNonImages(): void
    {
        (new Filesystem())->copy(self::FIXTURE_FILE_DIR.'/text.txt', self::UPLOAD_FILE_DIR.'/text.txt');

        $this->browser()
            ->visit('/live/preview/text.txt')
            ->assertStatus(404)
        ;
    }

    public function testMissingPreviewFileThrows404(): void
    {
        $this->browser()
            ->visit('/live/preview/missing.png')
            ->assertStatus(404)
        ;
    }

    public function testInvalidPreviewFilenameThrows404(): void
    {
        (new Filesystem())->mkdir(self::UPLOAD_FILE_DIR);

        $this->browser()
            ->visit('/live/preview/../../tests/Fixtures/files/image1.png')
            ->assertStatus(404)
        ;
    }
}
