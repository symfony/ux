<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Exception\InvalidStyleException;
use Symfony\UX\Css\Validation\StyleValidator;

final class StyleValidatorTest extends TestCase
{
    private const PROJECT = [
        'theme' => [
            'tokens' => [
                'colors' => ['red' => ['value' => '#f00']],
                'spacing' => ['md' => ['value' => '1rem'], 'lg' => ['value' => '2rem']],
                'letterSpacings' => ['tight' => ['value' => '-0.025em']],
                'blurs' => ['md' => ['value' => '12px']],
            ],
            'semanticTokens' => [
                'colors' => ['primary' => ['value' => '{colors.red}'], 'fg' => ['value' => '{colors.red}']],
            ],
        ],
    ];

    public static function provideInvalidStyles(): iterable
    {
        yield 'unknown property' => [['colr' => 'primary'], 'Unknown property "colr". Did you mean "color"?'];
        yield 'unknown condition' => [['_hovr' => ['color' => 'primary']], 'Unknown condition "_hovr". Did you mean "_hover"?'];
        yield 'unknown condition in a value' => [['color' => ['base' => 'fg', '_hovr' => 'primary']], 'Unknown condition "_hovr" in the value of "color". Did you mean "_hover"?'];
        yield 'unknown token' => [['color' => 'primry'], 'Unknown colors token "primry". Did you mean "primary"?'];
        yield 'raw value without brackets' => [['color' => '#fff'], 'Unknown colors token "#fff".'];
        yield 'raw value without brackets, no token close to it' => [['w' => '37ch'], 'Unknown sizes token "37ch". Write a raw value between brackets, like "[37ch]".'];
        yield 'unknown breakpoint' => [['mdd' => ['p' => 'md']], 'Unknown property "mdd". Did you mean "md"?'];
        yield 'invalid keyword' => [['display' => 'flexx'], 'Invalid value "flexx" for "display". Did you mean "flex"?'];
        yield 'base outside a conditional value' => [['base' => ['color' => 'primary']], '"base" can only be used inside a conditional value, like { color: { base: \'red\', _hover: \'blue\' } }.'];
        yield 'quote' => [['content' => '"a"'], 'The value of "content" cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment, ""a"" given.'];
        yield 'css injection' => [['w' => '[1px;} body{display:none]'], 'The value of "w" cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment, "[1px;} body{display:none]" given.'];
        yield 'comment' => [['color' => '[red /*]'], 'The value of "color" cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment, "[red /*]" given.'];
        yield 'backslash' => [['color' => '[red\\]'], 'The value of "color" cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment, "[red\\]" given.'];
        yield 'brace in a selector key' => [['& {' => ['color' => 'fg']], 'The key "& {" cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment.'];
        yield 'declaration in a variable key' => [['--x:1;color:red' => '1'], 'The key "--x:1;color:red" cannot contain ";", "{", "}", "\\", quotes, "<" or a CSS comment.'];
        yield 'token of a category with no token' => [['fontSize' => 'lg'], 'Unknown fontSizes token "lg": no fontSizes token is declared. Add them under ux_css.tokens.fontSizes, or write a raw value between brackets, like "[lg]".'];
        yield 'token of a category with no token, next to keywords' => [['fontWeight' => 'heavy'], 'Unknown fontWeights token "heavy": no fontWeights token is declared. Add them under ux_css.tokens.fontWeights, or write a raw value between brackets, like "[heavy]".'];
        yield 'unknown letterSpacings token' => [['letterSpacing' => 'tigt'], 'Unknown letterSpacings token "tigt". Did you mean "tight"?'];
        yield 'raw letterSpacings value without brackets' => [['letterSpacing' => '3px'], 'Unknown letterSpacings token "3px". Write a raw value between brackets, like "[3px]".'];
        yield 'unknown blurs token' => [['backdropBlur' => 'mdd'], 'Unknown blurs token "mdd". Did you mean "md"?'];
        yield 'token of a category with no token, aspectRatios' => [['aspectRatio' => 'wide'], 'Unknown aspectRatios token "wide": no aspectRatios token is declared. Add them under ux_css.tokens.aspectRatios, or write a raw value between brackets, like "[wide]".'];
        yield 'token of a category with no token, borders' => [['border' => 'subtle'], 'Unknown borders token "subtle": no borders token is declared. Add them under ux_css.tokens.borders, or write a raw value between brackets, like "[subtle]".'];
        yield 'token of a category with no token, borderWidths' => [['borderWidth' => '2px'], 'Unknown borderWidths token "2px": no borderWidths token is declared. Add them under ux_css.tokens.borderWidths, or write a raw value between brackets, like "[2px]".'];
        yield 'token of a category with no token, animations' => [['animation' => 'spin'], 'Unknown animations token "spin": no animations token is declared. Add them under ux_css.tokens.animations, or write a raw value between brackets, like "[spin]".'];
        yield 'token of a category with no token, opacity' => [['opacity' => 'half'], 'Unknown opacity token "half": no opacity token is declared. Add them under ux_css.tokens.opacity, or write a raw value between brackets, like "[half]".'];
        yield 'token of a category with no token, cursor' => [['cursor' => 'click'], 'Unknown cursor token "click": no cursor token is declared. Add them under ux_css.tokens.cursor, or write a raw value between brackets, like "[click]".'];
        yield 'token of a category with no token, gradients' => [['bgGradient' => 'sunset'], 'Unknown gradients token "sunset": no gradients token is declared. Add them under ux_css.tokens.gradients, or write a raw value between brackets, like "[sunset]".'];
        yield 'token of a category with no token, assets' => [['bgImage' => 'logo'], 'Unknown assets token "logo": no assets token is declared. Add them under ux_css.tokens.assets, or write a raw value between brackets, like "[logo]".'];
        yield 'token of a category with no token, containerNames' => [['containerName' => 'sidebar'], 'Unknown containerNames token "sidebar": no containerNames token is declared. Add them under ux_css.tokens.containerNames, or write a raw value between brackets, like "[sidebar]".'];
        yield 'object' => [['color' => new \stdClass()], 'The value of "color" must be a string, a number or a boolean, "stdClass" given.'];
    }

    #[DataProvider('provideInvalidStyles')]
    public function testInvalidStylesAreRejected(array $styles, string $message): void
    {
        $validator = new StyleValidator(Engine::fromProjectConfig(self::PROJECT));

        $this->expectException(InvalidStyleException::class);
        $this->expectExceptionMessage($message);

        $validator->validate($styles);
    }

    public function testValidStylesAreAccepted(): void
    {
        $validator = new StyleValidator(Engine::fromProjectConfig(self::PROJECT));

        $this->expectNotToPerformAssertions();

        $validator->validate([
            'p' => 'md',
            'w' => '[37ch]',
            'display' => 'flex',
            'zIndex' => 10,
            'color' => ['base' => 'fg', '_hover' => 'primary', 'md' => 'red'],
            'bg' => 'primary/50',
            'md' => ['p' => 'lg'],
            '_dark' => ['_hover' => ['color' => 'var(--brand)']],
            '--size' => '12px',
            'srOnly' => true,
        ]);
    }

    public function testCssWideKeywordsAreAcceptedWithStrictTokens(): void
    {
        $validator = new StyleValidator(Engine::fromProjectConfig(self::PROJECT));

        $this->expectNotToPerformAssertions();

        $validator->validate([
            'color' => 'inherit',
            'p' => 'initial',
            'm' => 'unset',
            'bg' => 'revert',
            'gap' => 'revert-layer',
        ]);
    }

    public function testACategoryWithNoTokenStillAcceptsRawValuesAndItsKeywords(): void
    {
        $validator = new StyleValidator(Engine::fromProjectConfig(self::PROJECT));

        $this->expectNotToPerformAssertions();

        $validator->validate(['fontSize' => '[18px]', 'fontWeight' => 'bold', 'zIndex' => 10, 'opacity' => 0.5]);
    }

    public function testTheTokensOfTheNewCategoriesAreAcceptedAsWellAsRawValuesBetweenBrackets(): void
    {
        $project = [
            'theme' => [
                'tokens' => [
                    'letterSpacings' => ['tight' => ['value' => '-0.025em']],
                    'blurs' => ['md' => ['value' => '12px']],
                    'borders' => ['subtle' => ['value' => '1px solid #ccc']],
                ],
            ],
        ];
        $validator = new StyleValidator(Engine::fromProjectConfig($project));

        $this->expectNotToPerformAssertions();

        $validator->validate([
            'letterSpacing' => 'tight',
            'backdropBlur' => 'md',
            'border' => 'subtle',
            'aspectRatio' => '[16 / 9]',
            'cursor' => 'pointer',
        ]);
    }

    public function testRawValuesAreAcceptedWithoutStrictTokens(): void
    {
        $validator = new StyleValidator(Engine::fromProjectConfig(self::PROJECT), strictTokens: false);

        $this->expectNotToPerformAssertions();

        $validator->validate(['color' => '#fff', 'p' => '3px']);
    }

    public function testAnyKeywordIsAcceptedWithoutStrictPropertyValues(): void
    {
        $validator = new StyleValidator(
            Engine::fromProjectConfig(self::PROJECT),
            strictTokens: false,
            strictPropertyValues: false,
        );

        $this->expectNotToPerformAssertions();

        $validator->validate(['display' => 'flexx']);
    }
}
