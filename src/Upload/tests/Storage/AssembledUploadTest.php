<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Storage\AssembledUpload;

final class AssembledUploadTest extends TestCase
{
    public function testExposesVerifiedAssemblyMeasurements(): void
    {
        $assembled = new AssembledUpload('.tmp/completed/file.txt', 42, 'checksum');

        self::assertSame('.tmp/completed/file.txt', $assembled->getPath());
        self::assertSame(42, $assembled->getSize());
        self::assertSame('checksum', $assembled->getHash());
    }
}
