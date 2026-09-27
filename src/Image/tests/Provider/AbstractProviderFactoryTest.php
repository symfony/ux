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
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Symfony\UX\Image\Exception\InvalidArgumentException;
use Symfony\UX\Image\Provider\Dsn;
use Symfony\UX\Image\Provider\NullProviderFactory;
use Symfony\UX\Image\Tests\Fixtures\FakeProviderFactory;

final class AbstractProviderFactoryTest extends TestCase
{
    public function testAnOptionTheFactoryDoesNotSupportIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "fake" image provider DSN: The option "driver" does not exist. Defined options are: "auto_format".');

        new FakeProviderFactory()->create(new Dsn('fake://default?driver=imagick'));
    }

    public function testAFactoryWithoutOptionsRejectsAnyOption(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "null" image provider DSN: the provider takes no option, "foo", "bar" given.');

        new NullProviderFactory()->create(new Dsn('null://null?foo=1&bar=2'));
    }

    public function testAnOptionThatIsNotAStringIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid "fake" image provider DSN: the "auto_format" option must be a string, "array" given.');

        new FakeProviderFactory()->create(new Dsn('fake://default?auto_format[]=1'));
    }

    public function testTheOptionsResolverErrorIsKeptAsThePreviousException(): void
    {
        try {
            new FakeProviderFactory()->create(new Dsn('fake://default?driver=imagick'));
            self::fail('Expected an InvalidArgumentException.');
        } catch (InvalidArgumentException $e) {
            self::assertInstanceOf(UndefinedOptionsException::class, $e->getPrevious());
        }
    }

    public function testASupportedStringOptionIsAccepted(): void
    {
        $provider = new FakeProviderFactory()->create(new Dsn('fake://default?auto_format=0'));

        self::assertFalse($provider->supportsAutoFormat());
    }
}
