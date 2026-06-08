<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\Attribute\ComponentCache;
use Symfony\UX\TwigComponent\ComputedPropertiesProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComputedPropertiesProxyTest extends TestCase
{
    public function testProxyCachesGetMethodReturns()
    {
        $proxy = new ComputedPropertiesProxy(new class {
            private int $count = 0;

            public function getCount(): int
            {
                return ++$this->count;
            }
        });

        $this->assertSame(1, $proxy->getCount());
        $this->assertSame(1, $proxy->getCount());
        $this->assertSame(1, $proxy->count());
    }

    public function testProxyCachesIsMethodReturns()
    {
        $proxy = new ComputedPropertiesProxy(new class {
            private int $count = 0;

            public function isCount(): int
            {
                return ++$this->count;
            }
        });

        $this->assertSame(1, $proxy->isCount());
        $this->assertSame(1, $proxy->isCount());
        $this->assertSame(1, $proxy->count());
    }

    public function testProxyCachesHasMethodReturns()
    {
        $proxy = new ComputedPropertiesProxy(new class {
            private int $count = 0;

            public function hasCount(): int
            {
                return ++$this->count;
            }
        });

        $this->assertSame(1, $proxy->hasCount());
        $this->assertSame(1, $proxy->hasCount());
        $this->assertSame(1, $proxy->count());
    }

    public function testCanProxyPublicProperties()
    {
        $proxy = new ComputedPropertiesProxy(new class {
            public $foo = 'bar';
        });

        $this->assertSame('bar', $proxy->foo());
    }

    public function testCanProxyArrayAccess()
    {
        $proxy = new ComputedPropertiesProxy(new class implements \ArrayAccess {
            private $array = ['foo' => 'bar'];

            public function offsetExists(mixed $offset): bool
            {
                return isset($this->array[$offset]);
            }

            public function offsetGet(mixed $offset): mixed
            {
                return $this->array[$offset];
            }

            public function offsetSet(mixed $offset, mixed $value): void
            {
            }

            public function offsetUnset(mixed $offset): void
            {
            }
        });

        $this->assertSame('bar', $proxy->foo());
    }

    public function testCannotProxyMethodsThatDoNotExist()
    {
        $proxy = new ComputedPropertiesProxy(new class {});

        $this->expectException(\InvalidArgumentException::class);

        $proxy->getSomething();
    }

    public function testCannotPassArgumentsToProxiedMethods()
    {
        $proxy = new ComputedPropertiesProxy(new class {});

        $this->expectException(\InvalidArgumentException::class);

        $proxy->getSomething('foo');
    }

    public function testCannotProxyMethodsWithRequiredArguments()
    {
        $proxy = new ComputedPropertiesProxy(new class {
            public function getValue(int $value): int
            {
                return $value;
            }
        });

        $this->expectException(\LogicException::class);

        $proxy->getValue();
    }

    public function testComponentCacheAttributeUsesSymfonyCache()
    {
        $container = new \Symfony\Component\DependencyInjection\Container();
        $cache = new \Symfony\Component\Cache\Adapter\ArrayAdapter();
        $container->set('cache.app', $cache);

        $component = new class {
            public int $count = 0;

            #[ComponentCache(key: 'my_test_key')]
            public function getHeavyData(): int
            {
                return ++$this->count;
            }
        };

        $proxy = new ComputedPropertiesProxy($component, $container);

        // First call hits the real method, returns 1, caches it
        $this->assertSame(1, $proxy->heavyData());

        // Second call hits the proxy in-memory cache, returns 1
        $this->assertSame(1, $proxy->heavyData());

        // New proxy, representing a new request
        $proxy2 = new ComputedPropertiesProxy($component, $container);

        // Hits the symfony cache pool, returns 1, DOES NOT increment count
        $this->assertSame(1, $proxy2->heavyData());

        // Check cache item directly
        $item = $cache->getItem('my_test_key');
        $this->assertTrue($item->isHit());
        $this->assertSame(1, $item->get());
    }
}
