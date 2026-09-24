<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\LiveComponent\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;

#[CoversClass(UploadTarget::class)]
final class UploadTargetTest extends TestCase
{
    public function testRejectsAnEmptyUploaderName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be empty');

        new UploadTarget('');
    }
}
