<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Token;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Exception\InvalidArgumentException;
use Symfony\UX\DesignTokens\Token\StrokeStyleToken;

#[CoversClass(StrokeStyleToken::class)]
final class StrokeStyleTokenTest extends TestCase
{
    private const DASHED = [
        'dashArray' => [
            ['value' => 4, 'unit' => 'px'],
            ['value' => 2, 'unit' => 'px'],
        ],
        'lineCap' => 'round',
    ];

    public function testTypeAndKeywordValue(): void
    {
        $token = new StrokeStyleToken('solid');

        self::assertSame('strokeStyle', $token->getType());
        self::assertSame('solid', (string) $token);
    }

    public function testAStructuredStrokeProjectsToTheNearestCssKeyword(): void
    {
        $token = new StrokeStyleToken(self::DASHED);

        self::assertSame('dashed', (string) $token);
    }

    public function testTheDashPatternStaysAvailableForConsumersThatCanUseIt(): void
    {
        $token = new StrokeStyleToken(self::DASHED);

        self::assertSame(['dashArray' => [['value' => 4, 'unit' => 'px'], ['value' => 2, 'unit' => 'px']], 'lineCap' => 'round'], $token->getValue());
    }

    public function testRejectsAValueThatIsNeitherAKeywordNorADashDefinition(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('keyword or a dash definition');

        new StrokeStyleToken(42);
    }
}
