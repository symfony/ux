<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Tests\Fixtures\Registries;
use Symfony\UX\DesignTokens\Token\DimensionToken;
use Symfony\UX\DesignTokens\Token\NumberToken;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\TokenTree;

#[CoversClass(TokenTree::class)]
#[CoversClass(TokenFactory::class)]
final class TokenTreeTest extends TestCase
{
    public function testFlattensNestedResolvedTokensInInsertionOrder(): void
    {
        $first = new NumberToken(1);
        $second = new NumberToken(2);

        self::assertSame([
            'content.label' => $first,
            'space.2' => $second,
        ], TokenTree::flatten([
            'content' => ['label' => $first],
            'space' => [2 => $second],
            'ignored' => 'not a token',
        ]));
    }

    public function testFlattensAnEmptyTree(): void
    {
        self::assertSame([], TokenTree::flatten([]));
    }

    public function testExportWritesOnlyTheDtcgKeysThatCarryInformation(): void
    {
        $tree = [
            'spacing' => [
                'md' => new DimensionToken(['value' => 1, 'unit' => 'rem'], 'Medium gap', ['com.acme' => ['id' => 4]], 'use lg'),
                'sm' => new DimensionToken(['value' => 0.5, 'unit' => 'rem']),
            ],
            'ignored' => 'not a token',
        ];

        self::assertSame([
            'spacing' => [
                'md' => [
                    '$type' => 'dimension',
                    '$value' => ['value' => 1, 'unit' => 'rem'],
                    '$description' => 'Medium gap',
                    '$extensions' => ['com.acme' => ['id' => 4]],
                    '$deprecated' => 'use lg',
                ],
                'sm' => [
                    '$type' => 'dimension',
                    '$value' => ['value' => 0.5, 'unit' => 'rem'],
                ],
            ],
        ], TokenTree::export($tree));
    }

    public function testHydrateIsTheInverseOfExport(): void
    {
        $registry = Registries::fromJson((string) file_get_contents(\dirname(__DIR__).'/Fixtures/color-scheme/foundation.tokens.json'));

        $tree = $registry->all();

        self::assertNotSame([], $tree);
        self::assertEquals($tree, TokenTree::hydrate(TokenTree::export($tree)));
    }

    public function testHydrateRebuildsEveryTokenTypeAndSkipsNonNodes(): void
    {
        $data = [
            'motion' => ['fast' => ['$type' => 'duration', '$value' => ['value' => 100, 'unit' => 'ms']]],
            'noise' => 'ignored',
        ];

        $tree = TokenTree::hydrate($data);

        self::assertSame(['motion'], array_keys($tree));
        self::assertSame('100ms', (string) TokenTree::flatten($tree)['motion.fast']);
    }
}
