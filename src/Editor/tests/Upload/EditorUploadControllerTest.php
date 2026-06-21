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
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\Editor\Upload\DefaultLocalUploadHandler;
use Symfony\UX\Editor\Upload\EditorUploadController;
use Symfony\UX\Editor\Upload\SignedUploadUrlGenerator;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

final class EditorUploadControllerTest extends TestCase
{
    private string $tmp;

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/ux-editor-ctl-'.uniqid();
        mkdir($this->tmp);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmp.'/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->tmp);
    }

    public function testHappyUpload()
    {
        $signer = new SignedUploadUrlGenerator('s3cret', 3600);
        $handler = new DefaultLocalUploadHandler($this->tmp, '/u', ['image/png'], 1_000_000);
        $registry = new UploadHandlerRegistry(['default' => $handler]);
        $ctl = new EditorUploadController($signer, $registry, 'default');

        $token = $signer->sign('body', 'default');
        $tmp = tempnam(sys_get_temp_dir(), 'ux');
        file_put_contents($tmp, 'PNGdata');
        $upload = new UploadedFile($tmp, 'img.png', 'image/png', null, true);
        $req = Request::create('/_ux_editor/upload/body?token='.urlencode($token), 'POST', [], [], ['file' => $upload]);

        $resp = $ctl('body', $req);
        self::assertSame(200, $resp->getStatusCode());
        $data = json_decode($resp->getContent(), true);
        self::assertStringStartsWith('/u/', $data['url']);
    }

    public function testBadSignatureReturns403()
    {
        $ctl = new EditorUploadController(new SignedUploadUrlGenerator('s3cret', 3600), new UploadHandlerRegistry([]), 'default');
        $req = Request::create('/_ux_editor/upload/body?token=garbage', 'POST');
        self::assertSame(403, $ctl('body', $req)->getStatusCode());
    }

    public function testBadMimeReturns422()
    {
        $signer = new SignedUploadUrlGenerator('s3cret', 3600);
        $handler = new DefaultLocalUploadHandler($this->tmp, '/u', ['image/png'], 1_000_000);
        $registry = new UploadHandlerRegistry(['default' => $handler]);
        $ctl = new EditorUploadController($signer, $registry, 'default');
        $token = $signer->sign('body', 'default');
        $tmp = tempnam(sys_get_temp_dir(), 'ux');
        file_put_contents($tmp, 'evil');
        $upload = new UploadedFile($tmp, 'e.exe', 'application/x-msdownload', null, true);
        $req = Request::create('/_ux_editor/upload/body?token='.urlencode($token), 'POST', [], [], ['file' => $upload]);
        self::assertSame(422, $ctl('body', $req)->getStatusCode());
    }
}
