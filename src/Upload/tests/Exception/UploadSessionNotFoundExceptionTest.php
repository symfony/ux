<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;

#[CoversClass(UploadSessionNotFoundException::class)]
final class UploadSessionNotFoundExceptionTest extends TestCase
{
    public function testMessageNamesTheUploadSession(): void
    {
        self::assertSame(
            'Upload session "0123456789abcdef" not found or expired.',
            new UploadSessionNotFoundException('0123456789abcdef')->getMessage(),
        );
    }
}
