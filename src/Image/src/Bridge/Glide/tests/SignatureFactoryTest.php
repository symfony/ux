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

use League\Glide\Signatures\SignatureInterface;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Bridge\Glide\SignatureFactory;
use Symfony\UX\Image\Exception\InvalidArgumentException;

final class SignatureFactoryTest extends TestCase
{
    public function testASignKeyGivesASignature(): void
    {
        self::assertInstanceOf(SignatureInterface::class, SignatureFactory::createFromDsn('glide://default/images?source=/s&cache=/c&sign_key=s3cret', 'test'));
    }

    #[TestWith(['dev'])]
    #[TestWith(['test'])]
    public function testNoSignKeyGivesNoSignatureInDevAndTest(string $environment): void
    {
        self::assertNull(SignatureFactory::createFromDsn('glide://default/images?source=/s&cache=/c', $environment));
    }

    public function testASignKeyIsRequiredInEveryOtherEnvironment(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Glide image provider requires a "sign_key" in the "prod" environment, or anyone can make it resize and cache any image at any size. Only "dev" and "test" may run without one.');

        SignatureFactory::createFromDsn('glide://default/images?source=/s&cache=/c', 'prod');
    }

    public function testAMisspelledSignKeyFailsInsteadOfTurningSigningOff(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "sign_kye" does not exist.');

        SignatureFactory::createFromDsn('glide://default/images?source=/s&cache=/c&sign_kye=s3cret', 'test');
    }

    public function testAnEmptySignKeyIsRejectedRatherThanSigningWithAKnownSecret(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "glide" image provider DSN: The "sign_key" option must not be empty.');

        SignatureFactory::createFromDsn('glide://default/images?source=/s&cache=/c&sign_key=', 'test');
    }
}
