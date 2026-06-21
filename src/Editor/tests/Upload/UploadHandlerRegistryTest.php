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
use Symfony\UX\Editor\Exception\Upload\UploadHandlerException;
use Symfony\UX\Editor\Upload\EditorUploadHandlerInterface;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

final class UploadHandlerRegistryTest extends TestCase
{
    public function testGetByProfile()
    {
        $h = $this->fakeHandler();
        $r = new UploadHandlerRegistry(['default' => $h]);
        self::assertSame($h, $r->get('default'));
    }

    public function testUnknownProfileThrows()
    {
        $this->expectException(UploadHandlerException::class);
        new UploadHandlerRegistry([])->get('missing');
    }

    private function fakeHandler(): EditorUploadHandlerInterface
    {
        return new class implements EditorUploadHandlerInterface {
            public function handle(UploadedFile $f, array $c = []): array
            {
                return ['url' => '/u', 'size' => 1];
            }
        };
    }
}
