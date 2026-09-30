<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\UX\Icons\Icon;
use Symfony\UX\Icons\IconCacheWarmer;
use Symfony\UX\Icons\IconFinderInterface;
use Symfony\UX\Icons\IconRegistryInterface;
use Symfony\UX\Icons\Registry\CacheIconRegistry;
use Symfony\UX\Icons\Tests\Util\InMemoryIconFinder;
use Symfony\UX\Icons\Tests\Util\InMemoryIconRegistry;

final class IconCacheWarmerTest extends TestCase
{
    public function testIsOptional(): void
    {
        $warmer = new IconCacheWarmer(new CacheIconRegistry(new InMemoryIconRegistry(), new ArrayAdapter()), new InMemoryIconFinder());

        $this->assertTrue($warmer->isOptional());
    }

    public function testWarmUpCachesFoundIcons(): void
    {
        $cache = new ArrayAdapter();
        $registry = new InMemoryIconRegistry([
            'foo' => new Icon('<path d="foo"/>'),
            'bar:baz' => new Icon('<path d="baz"/>'),
        ]);
        $warmer = new IconCacheWarmer(new CacheIconRegistry($registry, $cache), new InMemoryIconFinder(['foo', 'bar:baz', 'not:found']));

        $this->assertSame([], $warmer->warmUp(sys_get_temp_dir()));

        $this->assertSame('<path d="foo"/>', $cache->getItem('foo')->get()->getInnerSvg());
        $this->assertSame('<path d="baz"/>', $cache->getItem('bar--baz')->get()->getInnerSvg());
        $this->assertFalse($cache->hasItem('not--found'));
    }

    public function testWarmUpKeepsCachedIcons(): void
    {
        $cache = new ArrayAdapter();
        $cache->get('foo', static fn () => new Icon('<path d="cached"/>'));
        $registry = new InMemoryIconRegistry(['foo' => new Icon('<path d="fresh"/>')]);
        $warmer = new IconCacheWarmer(new CacheIconRegistry($registry, $cache), new InMemoryIconFinder(['foo']));

        $warmer->warmUp(sys_get_temp_dir());

        $this->assertSame('<path d="cached"/>', $cache->getItem('foo')->get()->getInnerSvg());
    }

    public function testWarmUpIgnoresFailures(): void
    {
        $cache = new ArrayAdapter();
        $registry = new class implements IconRegistryInterface {
            public function get(string $name): Icon
            {
                return match ($name) {
                    'foo' => new Icon('<path d="foo"/>'),
                    default => throw new \RuntimeException(\sprintf('The icon file "%s.svg" does not contain a valid SVG.', $name)),
                };
            }
        };
        $finder = new class implements IconFinderInterface {
            public function icons(): iterable
            {
                yield 'broken';
                yield 'foo';

                throw new \RuntimeException('The database is not reachable.');
            }
        };
        $warmer = new IconCacheWarmer(new CacheIconRegistry($registry, $cache), $finder);

        $this->assertSame([], $warmer->warmUp(sys_get_temp_dir()));

        $this->assertTrue($cache->hasItem('foo'));
        $this->assertFalse($cache->hasItem('broken'));
    }
}
