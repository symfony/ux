<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Tests\Adapter;

use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Pagination\Adapter\CursorValuesTrait;

#[CoversTrait(CursorValuesTrait::class)]
final class CursorValuesTraitTest extends TestCase
{
    private object $encoder;

    protected function setUp(): void
    {
        // Create anonymous class using the trait for testing
        $this->encoder = new class {
            use CursorValuesTrait {
                extractCursorValues as public;
                compareTuples as public;
            }
        };
    }

    public function testExtractFromObjectGetter(): void
    {
        $item = new class {
            public function getId(): int
            {
                return 42;
            }
        };

        self::assertSame([42], $this->encoder->extractCursorValues($item, ['id']));
    }

    public function testExtractFromObjectIsGetter(): void
    {
        $item = new class {
            public function isActive(): bool
            {
                return true;
            }
        };

        self::assertSame([1], $this->encoder->extractCursorValues($item, ['active']));
    }

    public function testExtractFromPublicProperty(): void
    {
        $item = new class {
            public string $name = 'Alice';
        };

        self::assertSame(['Alice'], $this->encoder->extractCursorValues($item, ['name']));
    }

    public function testPrivatePropertyWithoutGetterIsReportedAsInaccessible(): void
    {
        $item = new class {
            private string $name = 'Alice';
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot extract cursor field "name"');

        $this->encoder->extractCursorValues($item, ['name']);
    }

    public function testExtractFromArrayKey(): void
    {
        self::assertSame([7, 'b'], $this->encoder->extractCursorValues(['id' => 7, 'code' => 'b'], ['id', 'code']));
    }

    public function testExtractFromScalarWithIdField(): void
    {
        self::assertSame([5], $this->encoder->extractCursorValues(5, ['id']));
    }

    public function testExtractNormalizesDateTime(): void
    {
        $item = ['createdAt' => new \DateTimeImmutable('2024-06-15 10:30:00')];

        self::assertSame(['2024-06-15T10:30:00.000000Z'], $this->encoder->extractCursorValues($item, ['createdAt']));
    }

    public function testExtractNormalizesDateTimeFromGetter(): void
    {
        $item = new class {
            public function getCreatedAt(): \DateTimeImmutable
            {
                return new \DateTimeImmutable('2023-01-02 03:04:05');
            }
        };

        self::assertSame(['2023-01-02T03:04:05.000000Z'], $this->encoder->extractCursorValues($item, ['createdAt']));
    }

    public function testExtractRejectsNull(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must be non-null');

        $this->encoder->extractCursorValues(['field' => null], ['field']);
    }

    public function testExtractNormalizesBool(): void
    {
        self::assertSame([1, 0], $this->encoder->extractCursorValues(['a' => true, 'b' => false], ['a', 'b']));
    }

    public function testExtractThrowsForMissingField(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot extract cursor field "missing"');

        $this->encoder->extractCursorValues(['id' => 1], ['missing']);
    }

    public function testExtractThrowsForUnsupportedValueType(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('must be scalar or \DateTimeInterface');

        $this->encoder->extractCursorValues(['field' => new \stdClass()], ['field']);
    }

    public function testCompareTuplesEqual(): void
    {
        self::assertSame(0, $this->encoder->compareTuples([1, 'a'], [1, 'a']));
    }

    public function testCompareTuplesFirstFieldWins(): void
    {
        self::assertGreaterThan(0, $this->encoder->compareTuples([2, 'a'], [1, 'z']));
        self::assertLessThan(0, $this->encoder->compareTuples([1, 'z'], [2, 'a']));
    }

    public function testCompareTuplesFallsBackToNextField(): void
    {
        self::assertGreaterThan(0, $this->encoder->compareTuples([1, 'b'], [1, 'a']));
        self::assertLessThan(0, $this->encoder->compareTuples([1, 'a'], [1, 'b']));
    }

    public function testCompareTuplesWithDateStrings(): void
    {
        self::assertLessThan(0, $this->encoder->compareTuples(['2024-01-01 00:00:00'], ['2024-06-15 10:30:00']));
    }
}
