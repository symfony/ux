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
use Symfony\UX\Editor\Upload\EditorUploadHandlerInterface;
use Symfony\UX\Editor\Upload\UploadHandlerRegistry;

final class UploadHandlerRegistryAllTest extends TestCase
{
    public function testAllListsRegistered(): void
    {
        $h = new class implements EditorUploadHandlerInterface {
            public function handle(UploadedFile $f, array $c = []): array { return ['url' => '/x', 'size' => 1]; }
        };
        self::assertSame(['default'], array_keys((new UploadHandlerRegistry(['default' => $h]))->all()));
    }
}
