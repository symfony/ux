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
use Symfony\UX\TwigComponent\ComputedPropertiesProxy;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ComputedPropertiesProxyTest extends TestCase
{
    public function testProxyCachesGetMethodReturns(): void
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

    public function testProxyCachesIsMethodReturns(): void
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

    public function testProxyCachesHasMethodReturns(): void
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

    public function testProxyCachesNullReturns(): void
    {
        $component = new class {
            public int $calls = 0;

            public function getValue(): ?string
            {
                ++$this->calls;

                return null;
            }
        };
        $proxy = new ComputedPropertiesProxy($component);

        $this->assertNull($proxy->getValue());
        $this->assertNull($proxy->getValue());
        $this->assertNull($proxy->value());
        $this->assertSame(1, $component->calls);
    }

    public function testResolvesMethodsForEachComponentClass(): void
    {
        $getter = new ComputedPropertiesProxy(new class {
            public function getLabel(): string
            {
                return 'getter';
            }
        });
        $isser = new ComputedPropertiesProxy(new class {
            public function isLabel(): string
            {
                return 'isser';
            }
        });

        $this->assertSame('getter', $getter->label());
        $this->assertSame('isser', $isser->label());
    }

    public function testCachesReturnsForEachProxy(): void
    {
        $create = static fn (string $label): object => new class($label) {
            public function __construct(private string $label)
            {
            }

            public function getLabel(): string
            {
                return $this->label;
            }
        };

        $first = new ComputedPropertiesProxy($create('first'));
        $second = new ComputedPropertiesProxy($create('second'));

        $this->assertSame('first', $first->label());
        $this->assertSame('second', $second->label());
    }

    public function testCanProxyPublicProperties(): void
    {
        $proxy = new ComputedPropertiesProxy(new class {
            public $foo = 'bar';
        });

        $this->assertSame('bar', $proxy->foo());
    }

    public function testCanProxyArrayAccess(): void
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

    public function testCannotProxyMethodsThatDoNotExist(): void
    {
        $proxy = new ComputedPropertiesProxy(new class {});

        $this->expectException(\InvalidArgumentException::class);

        $proxy->getSomething();
    }

    public function testCannotPassArgumentsToProxiedMethods(): void
    {
        $proxy = new ComputedPropertiesProxy(new class {});

        $this->expectException(\InvalidArgumentException::class);

        $proxy->getSomething('foo');
    }

    public function testCannotProxyMethodsWithRequiredArguments(): void
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

    public function testThrowsForMethodsWithRequiredArgumentsOnEveryCall(): void
    {
        $proxy = new ComputedPropertiesProxy(new class {
            public function getValue(int $value): int
            {
                return $value;
            }
        });

        for ($i = 0; $i < 2; ++$i) {
            try {
                $proxy->getValue();
                $this->fail('A LogicException should have been thrown.');
            } catch (\LogicException $e) {
                $this->assertSame('Cannot use computed methods for methods with required parameters.', $e->getMessage());
            }
        }
    }
}
