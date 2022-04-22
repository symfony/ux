<?php

namespace Symfony\UX\TwigComponent\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\TwigComponent\ComputedPropertiesProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComputedPropertiesProxyTest extends TestCase
{
    public function testProxyCachesGetMethodReturns(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {
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

    public function testProxyCachesIsMethodReturns(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {
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

    public function testProxyCachesHasMethodReturns(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {
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

    public function testCanProxyPublicProperties(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {
            public $foo = 'bar';
        });

        $this->assertSame('bar', $proxy->foo());
    }

    public function testCanProxyArrayAccess(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() implements \ArrayAccess {
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

    public function testCannotProxyMethodsThatDoNotExist(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {});

        $this->expectException(\InvalidArgumentException::class);

        $proxy->getSomething();
    }

    public function testCannotPassArgumentsToProxiedMethods(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {});

        $this->expectException(\InvalidArgumentException::class);

        $proxy->getSomething('foo');
    }

    public function testCannotProxyMethodsWithRequiredArguments(): void
    {
        $proxy = new ComputedPropertiesProxy(new class() {
            public function getValue(int $value): int
            {
                return $value;
            }
        });

        $this->expectException(\LogicException::class);

        $proxy->getValue();
    }
}
