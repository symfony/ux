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

use PHPUnit\Framework\TestCase;
use Symfony\UX\Css\Engine\Engine;

final class ResetCssTest extends TestCase
{
    public function testTheResetIsWrittenInTheResetLayer(): void
    {
        $css = self::css(true);

        $this->assertStringStartsWith("@layer reset {\n  html,:host {\n", $css);
        $this->assertStringContainsString("\n  img {\n    border-style: none;\n", $css);
        $this->assertStringContainsString("\n  [hidden]:where(:not([hidden='until-found'])) {\n    display: none !important;\n", $css);
    }

    public function testValuesGoThroughTheEngine(): void
    {
        $css = self::css(true);

        $this->assertStringContainsString('-moz-tab-size: 4;', $css);
        $this->assertStringContainsString('background: transparent;', $css);
    }

    public function testTokensReplaceTheValuesThatNameOne(): void
    {
        $css = self::css(true, ['tokens' => ['colors' => ['transparent' => ['value' => 'transparent']]]]);

        $this->assertStringContainsString('background: var(--colors-transparent);', $css);
    }

    public function testAParentScopeLimitsTheResetToWhatIsInsideIt(): void
    {
        $css = self::css(['scope' => '.reset', 'level' => 'parent']);

        $this->assertStringNotContainsString('html,:host', $css);
        $this->assertStringContainsString("\n  .reset {\n    --font-fallback: ", $css);
        $this->assertStringContainsString("\n  .reset img {\n    border-style: none;\n", $css);
        $this->assertStringContainsString("\n  .reset ::placeholder {\n", $css);
        $this->assertStringContainsString("\n  .reset button,.reset input:where([type='button'], [type='reset'], [type='submit']),.reset ::file-selector-button {\n", $css);
    }

    public function testAnElementScopeLimitsTheResetToTheElementsThatHaveIt(): void
    {
        $css = self::css(['scope' => '.reset', 'level' => 'element']);

        $this->assertStringNotContainsString('html,:host', $css);
        $this->assertStringContainsString("\n  .reset {\n    --font-fallback: ", $css);
        $this->assertStringContainsString("\n  img.reset {\n    border-style: none;\n", $css);
        $this->assertStringContainsString("\n  *.reset,::before.reset,::after.reset,::backdrop.reset,::file-selector-button.reset {\n", $css);
        $this->assertStringContainsString("\n  .reset ::selection {\n", $css);
        $this->assertStringNotContainsString('::selection.reset', $css);
    }

    public function testTheParentLevelIsTheDefaultOne(): void
    {
        $this->assertSame(self::css(['scope' => '.reset', 'level' => 'parent']), self::css(['scope' => '.reset']));
    }

    /**
     * @param bool|array{scope?: string, level?: string} $preflight
     * @param array<string, mixed>                       $theme
     */
    private static function css(bool|array $preflight, array $theme = []): string
    {
        $engine = Engine::fromProjectConfig(['preflight' => $preflight, 'theme' => $theme]);

        return $engine->stylesheet()->render([], [], $engine->resetCss()->nodes());
    }
}
