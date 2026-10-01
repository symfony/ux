<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Conformance;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\Token\GradientToken;
use Symfony\UX\DesignTokens\Validation\ColorRangeInspector;
use Symfony\UX\DesignTokens\Validation\TokenValueValidator;

#[CoversClass(TokenValueValidator::class)]
#[CoversClass(GradientToken::class)]
final class FormatColorConformanceTest extends TestCase
{
    #[DataProvider('validValueProvider')]
    public function testAcceptsEveryPinnedPositiveFormatAndColorCase(string $type, mixed $value): void
    {
        new TokenValueValidator()->validate($type, $value, '/fixture');
        $this->addToAssertionCount(1);
    }

    private const array OUT_OF_RANGE = [
        'color/srgb-below',
        'color/srgb-above',
        'color/hsl-percent-above',
        'color/lab-lightness-above',
        'color/oklab-lightness-above',
        'color/lch-negative-chroma',
    ];

    #[DataProvider('invalidValueProvider')]
    public function testRejectsEveryPinnedNegativeFormatAndColorCase(string $type, mixed $value, string $case): void
    {
        if (\in_array($case, self::OUT_OF_RANGE, true)) {
            new TokenValueValidator()->validate($type, $value, '/fixture');
            self::assertNotSame([], new ColorRangeInspector()->inspect([
                'fixture' => new ColorToken($value),
            ]), \sprintf('"%s" should be reported as out of range.', $case));

            return;
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('/fixture');

        new TokenValueValidator()->validate($type, $value, '/fixture');
    }

    public function testAppliesNormativeGradientClamping(): void
    {
        $token = new GradientToken([
            ['color' => ['colorSpace' => 'srgb', 'components' => [0, 0, 0]], 'position' => -99],
            ['color' => ['colorSpace' => 'srgb', 'components' => [1, 1, 1]], 'position' => 42],
        ]);

        self::assertSame(0, $token->getValue()[0]['position']);
        self::assertSame(1, $token->getValue()[1]['position']);
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function validValueProvider(): iterable
    {
        yield from self::fixture('format-color-valid-values.json');
    }

    /** @return iterable<string, array{string, mixed}> */
    public static function invalidValueProvider(): iterable
    {
        foreach (self::fixture('format-color-invalid-values.json') as $case => [$type, $value]) {
            yield $case => [$type, $value, $case];
        }
    }

    /** @return array<string, array{string, mixed}> */
    private static function fixture(string $filename): array
    {
        $contents = file_get_contents(\dirname(__DIR__, 2).'/Fixtures/dtcg/'.$filename);
        self::assertNotFalse($contents);

        /** @var array<string, array{string, mixed}> $cases */
        $cases = json_decode($contents, true, 512, \JSON_THROW_ON_ERROR);

        return $cases;
    }
}
