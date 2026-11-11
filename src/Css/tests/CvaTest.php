<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\CssClassList;
use Symfony\UX\Css\Cva;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Engine\CvaRecipe;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Twig\CssRuntime;
use Symfony\UX\Css\Validation\StyleValidator;
use Twig\Error\RuntimeError;

final class CvaTest extends TestCase
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
        'compoundVariants' => [
            ['size' => 'lg', 'tone' => 'b', 'css' => ['p' => '3']],
        ],
    ];

    public function testApplyReturnsTheClassesOfTheResolvedStyles(): void
    {
        $generator = $this->generator();
        $cva = $this->runtime($generator)->cva(...self::BUTTON);

        $classes = $cva->apply(['size' => 'lg', 'tone' => 'a']);

        $expected = $generator->classes(['color' => 'red', 'paddingInline' => '2', 'background' => 'a']);
        $this->assertSame(array_keys($expected), $this->classNames($classes));
    }

    public function testAPrecomputedEntryIsReturnedAsIs(): void
    {
        $recipe = CvaRecipe::fromConfig(self::BUTTON);
        $table = [$recipe->key(['size' => 'lg']) => ['sentinel' => 'sentinel']];
        $cva = new Cva($recipe, $table, $this->runtime($this->generator()), false);

        $classes = $cva->apply(['size' => 'lg']);

        $this->assertSame(['sentinel'], $this->classNames($classes));
    }

    public function testTheTableAndTheRuntimeGiveTheSameClasses(): void
    {
        $generator = $this->generator();
        $runtime = $this->runtime($generator);
        $recipe = CvaRecipe::fromConfig(self::BUTTON);
        $table = $this->table($recipe, $generator);
        $withTable = new Cva($recipe, $table, $runtime, false);
        $withoutTable = $runtime->cva(...self::BUTTON);

        foreach ($recipe->combinations(256) as $combination) {
            foreach ([$combination, array_reverse($combination, true)] as $props) {
                $this->assertSame(
                    $this->classNames($withTable->apply($props)),
                    $this->classNames($withoutTable->apply($props)),
                );
            }
        }
        $this->assertGreaterThan(1, \count($table));
    }

    public function testInDebugAnUndeclaredPropIsARuntimeError(): void
    {
        $cva = $this->runtime($this->generator(), true)->cva(...self::BUTTON);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The cva() variant "shape" is not declared');

        $cva->apply(['shape' => 'round']);
    }

    public function testInDebugAnUndeclaredValueIsARuntimeErrorEvenWhenItsKeyIsInTheTable(): void
    {
        $generator = $this->generator();
        $recipe = CvaRecipe::fromConfig(self::BUTTON);
        $table = $this->table($recipe, $generator);
        $cva = $this->runtime($generator, true)->compiledCva(self::BUTTON, $recipe->id(), $table);

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('The value "zz" of the cva() variant "tone" is not declared');

        $cva->apply(['tone' => 'zz']);
    }

    public function testOutsideDebugAnUndeclaredValueSelectsNoStyles(): void
    {
        $generator = $this->generator();
        $cva = $this->runtime($generator)->cva(...self::BUTTON);

        $classes = $cva->apply(['size' => 'xl']);

        $this->assertSame(array_keys($generator->classes(['color' => 'red'])), $this->classNames($classes));
    }

    public function testCompiledCvaReusesTheInstanceOfAnId(): void
    {
        $runtime = $this->runtime($this->generator());

        $first = $runtime->compiledCva(self::BUTTON, 'id', []);
        $second = $runtime->compiledCva(self::BUTTON, 'id', []);
        $other = $runtime->compiledCva(self::BUTTON, 'other', []);

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $other);
    }

    private function runtime(ClassNameGenerator $generator, bool $debug = false): CssRuntime
    {
        if (!$debug) {
            return new CssRuntime($generator);
        }

        $validator = new StyleValidator(Engine::fromProjectConfig([]));

        return new CssRuntime($generator, static fn (): StyleValidator => $validator);
    }

    private function generator(): ClassNameGenerator
    {
        $shorthands = ['px' => 'paddingInline', 'py' => 'paddingBlock', 'p' => 'padding', 'bg' => 'background'];

        return new ClassNameGenerator([], $shorthands, ['_hover'], ['base', 'sm', 'md']);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function table(CvaRecipe $recipe, ClassNameGenerator $generator): array
    {
        $table = [];
        foreach ($recipe->combinations(256) ?? [] as $combination) {
            $table[$recipe->key($combination)] = $generator->classes($recipe->resolve($combination, $generator));
        }

        return $table;
    }

    /**
     * @return list<string>
     */
    private function classNames(CssClassList $classes): array
    {
        return explode(' ', trim((string) $classes));
    }
}
