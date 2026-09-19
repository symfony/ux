<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Processor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\ImageProcessingException;
use Symfony\UX\Image\Processor\ImageDriverCapabilities;

#[CoversClass(ImageDriverCapabilities::class)]
final class ImageDriverCapabilitiesTest extends TestCase
{
    public function testNormalizesJpgAndAcceptsAvailableFormats()
    {
        new ImageDriverCapabilities(['jpeg', 'webp'])->assertEncodable(['jpg', 'webp'], 'avatar');

        self::addToAssertionCount(1);
    }

    public function testUnavailableCodecFailsWithProfileAndAvailableCodecs()
    {
        $this->expectException(ImageProcessingException::class);
        $this->expectExceptionMessage('Profile "avatar" requests unavailable codec "avif"');

        new ImageDriverCapabilities(['jpeg'])->assertEncodable(['avif'], 'avatar');
    }

    public function testGdReportsAtLeastItsRequiredCodecs()
    {
        $capabilities = ImageDriverCapabilities::gd();

        self::assertContains('jpeg', $capabilities->encodableFormats);
        self::assertContains('png', $capabilities->encodableFormats);
    }

    public function testRejectsEmptyCodec()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new ImageDriverCapabilities(['  ']);
    }

    public function testRejectsEmptyCapabilityList()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least one');

        new ImageDriverCapabilities([]);
    }
}
