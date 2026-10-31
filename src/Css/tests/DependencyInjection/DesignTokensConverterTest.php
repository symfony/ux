<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\UX\Css\DependencyInjection\DesignTokensConverter;
use Symfony\UX\DesignTokens\Token\TokenFactory;
use Symfony\UX\DesignTokens\Token\TokenInterface;

final class DesignTokensConverterTest extends TestCase
{
    public static function provideNames(): iterable
    {
        yield 'color' => ['color.action.primary', self::color(), 'colors', ['action', 'primary']];
        yield 'plural prefix' => ['colors.blue-500', self::color(), 'colors', ['blue-500']];
        yield 'spacing under dimension' => ['dimension.spacing.md', self::rem(1), 'spacing', ['md']];
        yield 'decimal name' => ['spacing.1.5', self::rem(0.375), 'spacing', ['1', '5']];
        yield 'radius' => ['dimension.radius.control', self::rem(0.375), 'radii', ['control']];
        yield 'font size' => ['font.size.body', self::rem(1), 'fontSizes', ['body']];
        yield 'group root' => ['color.brand.$root', self::color(), 'colors', ['brand']];
    }

    #[DataProvider('provideNames')]
    public function testAPrefixGivesTheCategoryAndTheName(
        string $path,
        TokenInterface $token,
        string $category,
        array $segments,
    ): void {
        $converter = new DesignTokensConverter('dt');

        $tokens = $converter->convert([[$path => $token]])['tokens'];

        $definition = $tokens[$category];
        foreach ($segments as $segment) {
            $definition = $definition[$segment];
        }
        $this->assertSame(['externalVar' => '--dt-'.self::variable($path)], $definition['extensions']);
        $this->assertSame('var(--dt-'.self::variable($path).')', $definition['value']);
    }

    public function testATokenAndAGroupWithTheSameNameShareIt(): void
    {
        $converter = new DesignTokensConverter('dt');
        $resolution = ['color.brand' => self::color(), 'color.brand.light' => self::color()];

        $colors = $converter->convert([$resolution])['tokens']['colors'];

        $this->assertSame(['DEFAULT', 'light'], array_keys($colors['brand']));
    }

    public function testATokenOfAnotherTypeUnderAPrefixIsSkipped(): void
    {
        $converter = new DesignTokensConverter('dt');
        $resolution = ['color.opacity' => TokenFactory::create('number', 0.5), 'color.red' => self::color()];

        $colors = $converter->convert([$resolution])['tokens']['colors'];

        $this->assertSame(['red'], array_keys($colors));
    }

    public function testATokenAtThePrefixItselfIsSkipped(): void
    {
        $converter = new DesignTokensConverter('dt');

        $tokens = $converter->convert([['color' => self::color()]])['tokens'];

        $this->assertSame([], $tokens);
    }

    public function testCompositeTokensAreSkipped(): void
    {
        $converter = new DesignTokensConverter('dt');
        $typography = TokenFactory::create('typography', [
            'fontFamily' => ['Inter'],
            'fontSize' => ['value' => 1, 'unit' => 'rem'],
            'fontWeight' => 400,
            'letterSpacing' => ['value' => 0, 'unit' => 'px'],
            'lineHeight' => 1.5,
        ]);

        $tokens = $converter->convert([['font.body' => $typography]])['tokens'];

        $this->assertSame([], $tokens);
    }

    public function testNamesAreTheUnionOfEveryResolution(): void
    {
        $converter = new DesignTokensConverter('dt');
        $light = ['color.fg' => self::color()];
        $brand = ['color.fg' => self::color(), 'color.accent' => self::color()];

        $colors = $converter->convert([$light, $brand])['tokens']['colors'];

        $this->assertSame(['fg', 'accent'], array_keys($colors));
    }

    public function testBreakpointsGetTheirValues(): void
    {
        $converter = new DesignTokensConverter('dt');
        $resolution = ['breakpoint.md' => self::rem(48), 'breakpoint.lg' => self::rem(64)];

        $breakpoints = $converter->convert([$resolution])['breakpoints'];

        $this->assertSame(['md' => '48rem', 'lg' => '64rem'], $breakpoints);
    }

    public function testWithoutPrefixTheVariableHasNone(): void
    {
        $converter = new DesignTokensConverter(null);

        $tokens = $converter->convert([['color.red' => self::color()]])['tokens'];

        $this->assertSame('var(--color-red)', $tokens['colors']['red']['value']);
    }

    public function testTwoPathsCannotGiveTheSameName(): void
    {
        $converter = new DesignTokensConverter('dt');
        $resolution = ['color.red' => self::color(), 'colors.red' => self::color()];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "color.red" and "colors.red" design tokens both give the "red" colors token.');

        $converter->convert([$resolution]);
    }

    public function testABreakpointMustNotChangeBetweenResolutions(): void
    {
        $converter = new DesignTokensConverter('dt');
        $resolutions = [['breakpoint.md' => self::rem(48)], ['breakpoint.md' => self::rem(40)]];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The "breakpoint.md" design token must have the same value in every Resolver context, because a media query cannot change per request.');

        $converter->convert($resolutions);
    }

    private static function color(): TokenInterface
    {
        return TokenFactory::create('color', ['colorSpace' => 'srgb', 'components' => [1, 0, 0]]);
    }

    private static function rem(int|float $value): TokenInterface
    {
        return TokenFactory::create('dimension', ['value' => $value, 'unit' => 'rem']);
    }

    private static function variable(string $path): string
    {
        return preg_replace('/[^\p{L}\p{N}_-]+/u', '-', str_replace('.', '-', $path));
    }
}
