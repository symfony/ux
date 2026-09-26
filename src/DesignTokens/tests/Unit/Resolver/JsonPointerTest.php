<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Resolver;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Resolver\JsonPointer;

#[CoversClass(JsonPointer::class)]
final class JsonPointerTest extends TestCase
{
    /** @param list<string> $segments */
    #[DataProvider('fragments')]
    public function testSplitsAFragmentIntoDecodedSegments(string $fragment, array $segments): void
    {
        self::assertSame($segments, JsonPointer::segments($fragment));
    }

    /** @return iterable<string, array{string, list<string>}> */
    public static function fragments(): iterable
    {
        yield 'whole document' => ['', []];
        yield 'path' => ['/color/brand', ['color', 'brand']];
        yield 'empty segment' => ['/', ['']];
        yield 'escapes' => ['/a~1b/c~0d', ['a/b', 'c~d']];
        yield 'escape order' => ['/~01', ['~1']];
        yield 'percent-encoded space' => ['/space%20token', ['space token']];
        yield 'percent-encoded escape' => ['/tilde%7E0', ['tilde~']];
        yield 'percent-encoded slash separates' => ['/a%2Fb', ['a', 'b']];
        yield 'numeric segment' => ['/items/0', ['items', '0']];
    }

    #[DataProvider('invalidFragments')]
    public function testRejectsAMalformedFragment(string $fragment, string $message): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);

        JsonPointer::segments($fragment);
    }

    /** @return iterable<string, array{string, string}> */
    public static function invalidFragments(): iterable
    {
        yield 'no leading slash' => ['color', 'Invalid JSON Pointer fragment'];
        yield 'bad escape' => ['/a~2b', 'Invalid JSON Pointer escape'];
        yield 'lone tilde' => ['/a~', 'Invalid JSON Pointer escape'];
        yield 'bad percent-encoding' => ['/value%2', 'Invalid percent-encoding'];
    }

    public function testEscapesASegment(): void
    {
        self::assertSame('a~1b~0c', JsonPointer::escape('a/b~c'));
        self::assertSame(['a/b~c'], JsonPointer::segments('/'.JsonPointer::escape('a/b~c')));
    }
}
