<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\NullProviderFactory;
use Symfony\UX\Image\Tests\Fixtures\FakeProviderFactory;

final class AbstractProviderFactoryTest extends TestCase
{
    public function testAnOptionTheFactoryDoesNotSupportIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option(s) "driver" passed to the "fake" image provider (supported: "auto_format").');

        new FakeProviderFactory()->create(new Dsn('fake://default?driver=imagick'));
    }

    public function testAFactoryWithoutOptionsRejectsAnyOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid option(s) "foo", "bar" passed to the "null" image provider (supported: none).');

        new NullProviderFactory()->create(new Dsn('null://null?foo=1&bar=2'));
    }

    public function testAnOptionThatIsNotAStringIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The "auto_format" option of the "fake" image provider must be a string, "array" given.');

        new FakeProviderFactory()->create(new Dsn('fake://default?auto_format[]=1'));
    }

    public function testASupportedStringOptionIsAccepted(): void
    {
        $provider = new FakeProviderFactory()->create(new Dsn('fake://default?auto_format=0'));

        self::assertFalse($provider->supportsAutoFormat());
    }
}
