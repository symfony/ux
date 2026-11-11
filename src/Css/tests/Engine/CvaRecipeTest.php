<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Engine;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\CvaRecipe;
use Symfony\UX\Css\Exception\InvalidStyleException;

final class CvaRecipeTest extends TestCase
{
    private const BUTTON = [
        'base' => ['color' => 'red'],
        'variants' => [
            'size' => [
                'sm' => ['px' => '1'],
                'lg' => ['px' => '2'],
            ],
            'tone' => [
                'a' => ['bg' => 'a'],
                'b' => ['bg' => 'b'],
            ],
        ],
        'defaultVariants' => ['size' => 'sm'],
    ];

    public function testBaseAloneResolvesToBase(): void
    {
        $recipe = CvaRecipe::fromConfig(['base' => ['color' => 'red', 'px' => '1']]);

        $resolved = $recipe->resolve([], $this->generator());

        $this->assertSame(['color' => 'red', 'px' => '1'], $resolved);
    }

    public function testDefaultVariantsApplyWhenAPropIsNullOrMissing(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);
        $generator = $this->generator();
        $expected = ['color' => 'red', 'paddingInline' => '1'];

        $this->assertSame($expected, $recipe->resolve([], $generator));
        $this->assertSame($expected, $recipe->resolve(['size' => null], $generator));
    }

    public function testAnUndeclaredValueReplacesTheDefaultAndAddsNothing(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);

        $resolved = $recipe->resolve(['size' => 'xl'], $this->generator());

        $this->assertSame(['color' => 'red'], $resolved);
    }

    public function testVariantsMergeInComputedOrder(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'a' => ['y' => ['px' => '1']],
                'b' => ['x' => ['px' => '2']],
            ],
        ]);
        $generator = $this->generator();

        $bThenA = $recipe->resolve(['b' => 'x', 'a' => 'y'], $generator);
        $aThenB = $recipe->resolve(['a' => 'y', 'b' => 'x'], $generator);

        $this->assertSame(['paddingInline' => '1'], $bThenA);
        $this->assertSame(['paddingInline' => '2'], $aThenB);
    }

    public function testDefaultedVariantsKeepTheirPlaceWhenAPropReplacesThem(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'a' => ['y' => ['px' => '1']],
                'b' => ['x' => ['px' => '2']],
            ],
            'defaultVariants' => ['a' => 'y'],
        ]);

        $resolved = $recipe->resolve(['b' => 'x', 'a' => 'y'], $this->generator());

        $this->assertSame(['paddingInline' => '2'], $resolved);
    }

    public function testVariantNamesAreLookedUpAsStrings(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'disabled' => ['true' => ['color' => 'a'], 'false' => ['color' => 'b']],
                'level' => [1 => ['display' => 'flex']],
            ],
        ]);
        $generator = $this->generator();

        $this->assertSame(['color' => 'a'], $recipe->resolve(['disabled' => true], $generator));
        $this->assertSame(['color' => 'a'], $recipe->resolve(['disabled' => 'true'], $generator));
        $this->assertSame(['color' => 'b'], $recipe->resolve(['disabled' => false], $generator));
        $this->assertSame(['display' => 'flex'], $recipe->resolve(['level' => 1], $generator));
        $this->assertSame(['display' => 'flex'], $recipe->resolve(['level' => '1'], $generator));
        $this->assertSame(['display' => 'flex'], $recipe->resolve(['level' => 1.0], $generator));
    }

    public function testAPropValueThatJavaScriptCannotPrintSelectsNothing(): void
    {
        $recipe = CvaRecipe::fromConfig(['variants' => ['level' => [1 => ['display' => 'flex']]]]);
        $generator = $this->generator();

        $this->assertSame([], $recipe->resolve(['level' => \INF], $generator));
        $this->assertSame([], $recipe->resolve(['level' => ['a']], $generator));
    }

    public function testCompoundVariantsMatchLikeStrictEquality(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'disabled' => ['true' => [], 'false' => []],
                'level' => [1 => [], 2 => []],
                'size' => ['sm' => [], 'md' => [], 'lg' => []],
            ],
            'compoundVariants' => [
                ['disabled' => true, 'css' => ['display' => 'flex']],
                ['level' => 1, 'css' => ['color' => 'red']],
                ['size' => ['sm', 'lg'], 'css' => ['bg' => 'blue']],
            ],
        ]);
        $generator = $this->generator();

        $this->assertSame(['display' => 'flex'], $recipe->resolve(['disabled' => true], $generator));
        $this->assertSame([], $recipe->resolve(['disabled' => 'true'], $generator));
        $this->assertSame(['color' => 'red'], $recipe->resolve(['level' => 1], $generator));
        $this->assertSame(['color' => 'red'], $recipe->resolve(['level' => 1.0], $generator));
        $this->assertSame([], $recipe->resolve(['level' => '1'], $generator));
        $this->assertSame(['bg' => 'blue'], $recipe->resolve(['size' => 'sm'], $generator));
        $this->assertSame(['bg' => 'blue'], $recipe->resolve(['size' => 'lg'], $generator));
        $this->assertSame([], $recipe->resolve(['size' => 'md'], $generator));
    }

    public function testCompoundVariantsMergeAfterTheVariantsInDeclarationOrder(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'base' => ['color' => 'red'],
            'variants' => ['size' => ['sm' => ['color' => 'blue']]],
            'compoundVariants' => [
                ['size' => 'sm', 'css' => ['color' => 'green', 'px' => '1']],
                ['size' => 'sm', 'css' => ['color' => 'purple']],
            ],
        ]);

        $resolved = $recipe->resolve(['size' => 'sm'], $this->generator());

        $this->assertSame(['color' => 'purple', 'paddingInline' => '1'], $resolved);
    }

    public function testPropsThatResolveAlikeHaveTheSameKey(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);

        $this->assertSame($recipe->key([]), $recipe->key(['size' => null]));
        $this->assertSame($recipe->key([]), $recipe->key(['tone' => 'c']));
        $this->assertSame($recipe->key([]), $recipe->key(['size' => 'sm', 'other' => 'x']));
        $this->assertNotSame($recipe->key([]), $recipe->key(['size' => 'lg']));
    }

    public function testPropsInAnotherOrderHaveTheSameKeyWhenTheOrderDoesNotMatter(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);

        $toneFirst = $recipe->key(['tone' => 'a', 'size' => 'lg']);
        $sizeFirst = $recipe->key(['size' => 'lg', 'tone' => 'a']);

        $this->assertSame($sizeFirst, $toneFirst);
    }

    public function testPropsInAnotherOrderHaveAnotherKey(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'a' => ['y' => ['px' => '1']],
                'b' => ['x' => ['px' => '2']],
            ],
        ]);

        $bThenA = $recipe->key(['b' => 'x', 'a' => 'y']);
        $aThenB = $recipe->key(['a' => 'y', 'b' => 'x']);

        $this->assertNotSame($aThenB, $bThenA);
    }

    public function testCompoundMatchesAreInTheKey(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => ['disabled' => ['true' => []]],
            'compoundVariants' => [['disabled' => true, 'css' => ['display' => 'flex']]],
        ]);

        $this->assertNotSame($recipe->key(['disabled' => true]), $recipe->key(['disabled' => 'true']));
    }

    public function testCombinationsCoverEveryValueInCanonicalOrder(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => self::BUTTON['variants'],
            'defaultVariants' => self::BUTTON['defaultVariants'],
        ]);

        $combinations = $recipe->combinations(256);

        $expected = [
            ['size' => 'sm'],
            ['size' => 'sm', 'tone' => 'a'],
            ['size' => 'sm', 'tone' => 'b'],
            ['size' => 'lg'],
            ['size' => 'lg', 'tone' => 'a'],
            ['size' => 'lg', 'tone' => 'b'],
        ];
        $this->assertSame($expected, $combinations);
    }

    public function testCombinationsTypeValuesNaturally(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'disabled' => ['true' => [], 'false' => []],
                'level' => [1 => [], '1.5' => [], '01' => [], '' => []],
            ],
            'defaultVariants' => ['disabled' => false, 'level' => 1],
        ]);

        $combinations = $recipe->combinations(256);

        $expected = [
            ['disabled' => true, 'level' => 1],
            ['disabled' => true, 'level' => 1.5],
            ['disabled' => true, 'level' => '01'],
            ['disabled' => true, 'level' => ''],
            ['disabled' => false, 'level' => 1],
            ['disabled' => false, 'level' => 1.5],
            ['disabled' => false, 'level' => '01'],
            ['disabled' => false, 'level' => ''],
        ];
        $this->assertSame($expected, $combinations);
    }

    public function testCombinationsKeepIntegerLikeVariantNames(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                1 => ['x' => ['px' => '1']],
                'tone' => ['a' => ['bg' => 'a']],
            ],
        ]);
        $generator = $this->generator();

        $combinations = $recipe->combinations(256);

        $expected = [
            [],
            ['tone' => 'a'],
            [1 => 'x'],
            [1 => 'x', 'tone' => 'a'],
        ];
        $this->assertSame($expected, $combinations);
        $this->assertSame(['paddingInline' => '1', 'background' => 'a'], $recipe->resolve($combinations[3], $generator));
        $this->assertSame($recipe->key([1 => 'x', 'tone' => 'a']), $recipe->key($combinations[3]));
        $this->assertNotSame($recipe->key(['tone' => 'a']), $recipe->key($combinations[3]));
    }

    public function testIntegerLikeVariantNamesComeFirstInTheComputedOrder(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => [
                'b' => ['y' => ['px' => '2']],
                1 => ['x' => ['px' => '1']],
            ],
        ]);

        $resolved = $recipe->resolve(['b' => 'y', 1 => 'x'], $this->generator());

        $this->assertSame(['paddingInline' => '2'], $resolved);
        $this->assertSame($recipe->key([1 => 'x', 'b' => 'y']), $recipe->key(['b' => 'y', 1 => 'x']));
    }

    public function testCombinationsAreNullOverTheLimit(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);

        $this->assertNull($recipe->combinations(5));
        $this->assertCount(6, $recipe->combinations(6));
    }

    public function testStylesListsBaseVariantsAndCompoundCss(): void
    {
        $recipe = CvaRecipe::fromConfig([
            ...self::BUTTON,
            'compoundVariants' => [['size' => 'lg', 'tone' => 'a', 'css' => ['display' => 'flex']]],
        ]);

        $expected = [
            ['color' => 'red'],
            ['px' => '1'],
            ['px' => '2'],
            ['bg' => 'a'],
            ['bg' => 'b'],
            ['display' => 'flex'],
        ];
        $this->assertSame($expected, $recipe->styles());
    }

    public function testIdHashesTheConfig(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);

        $this->assertSame(hash('xxh128', serialize(self::BUTTON)), $recipe->id());
    }

    public function testUndeclaredReportsUnknownNamesAndValues(): void
    {
        $recipe = CvaRecipe::fromConfig([
            ...self::BUTTON,
            'variants' => [...self::BUTTON['variants'], 'disabled' => ['true' => []]],
        ]);

        $this->assertNull($recipe->undeclared([]));
        $this->assertNull($recipe->undeclared(['size' => 'lg', 'tone' => null, 'disabled' => true]));
        $this->assertStringContainsString('"color"', $recipe->undeclared(['size' => 'lg', 'color' => 'x']) ?? '');
        $this->assertStringContainsString('"xl"', $recipe->undeclared(['size' => 'xl']) ?? '');
        $this->assertStringContainsString('"false"', $recipe->undeclared(['disabled' => false]) ?? '');
        $this->assertStringContainsString('"tone"', $recipe->undeclared(['tone' => ['a']]) ?? '');
    }

    public static function provideInvalidConfigs(): iterable
    {
        $variants = ['size' => ['sm' => [], 'lg' => []], 'disabled' => ['true' => []], 'level' => [1 => []]];

        yield 'unknown top-level key' => [['slots' => []], '"slots"'];
        yield 'base not a hash' => [['base' => 'red'], '"base"'];
        yield 'base is a list' => [['base' => ['red']], '"base"'];
        yield 'variants not a hash' => [['variants' => 'size'], '"variants"'];
        yield 'variant not a hash' => [['variants' => ['size' => 'sm']], '"size"'];
        yield 'variant value not a hash' => [['variants' => ['size' => ['sm' => 'px']]], '"size.sm"'];
        yield 'variant value is a list' => [['variants' => ['size' => ['sm' => ['px']]]], '"size.sm"'];
        yield 'defaultVariants not a hash' => [['variants' => $variants, 'defaultVariants' => 'sm'], '"defaultVariants"'];
        yield 'default of an undeclared variant' => [['variants' => $variants, 'defaultVariants' => ['tone' => 'a']], '"tone"'];
        yield 'default with an undeclared value' => [['variants' => $variants, 'defaultVariants' => ['size' => 'xl']], '"xl"'];
        yield 'default that is a list' => [['variants' => $variants, 'defaultVariants' => ['size' => ['sm']]], '"size"'];
        yield 'default that is null' => [['variants' => $variants, 'defaultVariants' => ['size' => null]], '"size"'];
        yield 'default boolean of a non-boolean name' => [['variants' => $variants, 'defaultVariants' => ['size' => true]], '"true"'];
        yield 'compoundVariants not a list' => [['variants' => $variants, 'compoundVariants' => ['a' => ['css' => []]]], '"compoundVariants"'];
        yield 'compound not a hash' => [['variants' => $variants, 'compoundVariants' => ['size']], '"compoundVariants"'];
        yield 'compound without css' => [['variants' => $variants, 'compoundVariants' => [['size' => 'sm']]], '"css"'];
        yield 'compound css not a hash' => [['variants' => $variants, 'compoundVariants' => [['size' => 'sm', 'css' => 'x']]], '"css"'];
        yield 'compound of an undeclared variant' => [['variants' => $variants, 'compoundVariants' => [['tone' => 'a', 'css' => []]]], '"tone"'];
        yield 'compound with an undeclared value' => [['variants' => $variants, 'compoundVariants' => [['size' => 'xl', 'css' => []]]], '"xl"'];
        yield 'compound with an undeclared list value' => [['variants' => $variants, 'compoundVariants' => [['size' => ['sm', 'xl'], 'css' => []]]], '"xl"'];
        yield 'compound number of an undeclared name' => [['variants' => $variants, 'compoundVariants' => [['level' => 2, 'css' => []]]], '"2"'];
        yield 'compound boolean of an undeclared name' => [['variants' => $variants, 'compoundVariants' => [['disabled' => false, 'css' => []]]], '"false"'];
    }

    #[DataProvider('provideInvalidConfigs')]
    public function testInvalidConfigsAreRejected(array $config, string $message): void
    {
        $this->expectException(InvalidStyleException::class);
        $this->expectExceptionMessage($message);

        CvaRecipe::fromConfig($config);
    }

    public function testBooleansAndNumbersAreAcceptedForTheirNames(): void
    {
        $recipe = CvaRecipe::fromConfig([
            'variants' => ['disabled' => ['true' => [], 'false' => []], 'level' => [1 => []]],
            'defaultVariants' => ['disabled' => false, 'level' => 1.0],
            'compoundVariants' => [['disabled' => true, 'level' => [1, '1'], 'css' => ['color' => 'red']]],
        ]);

        $resolved = $recipe->resolve(['disabled' => true], $this->generator());

        $this->assertSame(['color' => 'red'], $resolved);
    }

    private function generator(): ClassNameGenerator
    {
        $shorthands = ['px' => 'paddingInline', 'py' => 'paddingBlock', 'p' => 'padding', 'bg' => 'background'];

        return new ClassNameGenerator([], $shorthands, ['_hover'], ['base', 'sm', 'md']);
    }
}
