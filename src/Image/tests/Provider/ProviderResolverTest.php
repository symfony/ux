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
use Symfony\UX\Image\Exception\UnsupportedSchemeException;
use Symfony\UX\Image\Provider\ProviderResolver;
use Symfony\UX\Image\Tests\Fixtures\FakeProvider;
use Symfony\UX\Image\Tests\Fixtures\FakeProviderFactory;

final class ProviderResolverTest extends TestCase
{
    public function testItResolvesADsnToTheMatchingProvider()
    {
        $resolver = new ProviderResolver([new FakeProviderFactory()]);

        self::assertInstanceOf(FakeProvider::class, $resolver->fromString('fake://default'));
    }

    public function testItThrowsWhenNoFactorySupportsTheScheme()
    {
        $resolver = new ProviderResolver([new FakeProviderFactory()]);

        $this->expectException(UnsupportedSchemeException::class);

        $resolver->fromString('unknown://default');
    }
}
