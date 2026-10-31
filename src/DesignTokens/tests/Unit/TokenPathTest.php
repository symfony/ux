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
use Symfony\UX\DesignTokens\TokenPath;

#[CoversClass(TokenPath::class)]
final class TokenPathTest extends TestCase
{
    public function testConvertsAPathToACssVariable(): void
    {
        self::assertSame('--color-action-primary', TokenPath::toCssVariable('color.action.primary'));
        self::assertSame('--my-color-action-primary', TokenPath::toCssVariable('color.action.primary', 'my'));
        self::assertSame('--type-heading-large', TokenPath::toCssVariable('type.heading large'));
        self::assertSame('--token', TokenPath::toCssVariable('...'));
    }

    public function testBuildsCssReferencesWithAnOptionalFallback(): void
    {
        self::assertSame('var(--color-action-primary)', TokenPath::toCssReference('color.action.primary'));
        self::assertSame('var(--color-action-primary, currentColor)', TokenPath::toCssReference('color.action.primary', 'currentColor'));
        self::assertSame('var(--my-color-action-primary)', TokenPath::toCssReference('color.action.primary', prefix: 'my'));
    }

    public function testRejectsAnInvalidCssPrefix(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('CSS prefix');

        TokenPath::toCssVariable('color.action.primary', '--my');
    }
}
