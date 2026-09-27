<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Bridge\Glide\Tests;

use Intervention\Image\Interfaces\DriverInterface;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Glide\EncodableFormats;

final class EncodableFormatsTest extends TestCase
{
    public function testAFormatTheDriverCannotEncodeIsDropped(): void
    {
        $driver = $this->driverEncoding(['webp', 'jpeg']);

        self::assertSame(['webp', 'jpeg'], EncodableFormats::filter($driver, ['avif', 'webp', 'jpeg']));
    }

    public function testProgressiveJpegFollowsJpeg(): void
    {
        self::assertSame(['pjpg'], EncodableFormats::filter($this->driverEncoding(['jpeg']), ['pjpg']));
        self::assertSame([], EncodableFormats::filter($this->driverEncoding(['webp']), ['pjpg']));
    }

    /**
     * @param list<string> $formats
     */
    private function driverEncoding(array $formats): DriverInterface
    {
        $driver = $this->createStub(DriverInterface::class);
        $driver->method('supports')->willReturnCallback(static fn (string $format): bool => \in_array($format, $formats, true));

        return $driver;
    }
}
