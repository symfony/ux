<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Registry;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\UX\Icons\Exception\IconNotFoundException;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Tests\Util\InMemoryIconRegistry;

/**
 * @author Javier Eguiluz <javier.eguiluz@gmail.com>
 */
final class CacheIconRegistryTest extends TestCase
{
    #[DataProvider('provideInvalidNames')]
    public function testInvalidNameIsRejectedEvenIfInnerRegistryHasIt(string $name)
    {
        $inner = new InMemoryIconRegistry([$name => new Icon('<path d="M0 0h24v24H0z"/>')]);
        $registry = new CacheIconRegistry($inner, new ArrayAdapter());

        $this->expectException(IconNotFoundException::class);
        $this->expectExceptionMessage(\sprintf('The icon name "%s" is not valid.', $name));

        $registry->get($name);
    }

    public function testValidNameIsResolvedAndCached()
    {
        $icon = new Icon('<path d="M0 0h24v24H0z"/>');
        $cache = new ArrayAdapter();
        $registry = new CacheIconRegistry(new InMemoryIconRegistry(['foo-bar:baz' => $icon]), $cache);

        $this->assertSame($icon->getInnerSvg(), $registry->get('foo-bar:baz')->getInnerSvg());
        $this->assertTrue($cache->hasItem('foo-bar--baz'));
        $this->assertSame($icon->getInnerSvg(), $registry->get('foo-bar:baz')->getInnerSvg());
    }

    public static function provideInvalidNames(): iterable
    {
        yield 'underscore_in_prefix' => ['foo_bar:baz'];
        yield 'underscore_in_name' => ['foo:bar_baz'];
        yield 'underscore_in_prefix_and_name' => ['foo_bar:baz_qux'];
        yield 'uppercase_prefix' => ['FOO:bar'];
    }
}
