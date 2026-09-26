<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Generator\GeneratorInterface;
use Symfony\UX\DesignTokens\Generator\JavaScriptGenerator;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\TokenRegistry;

final class LayeredThemeTest extends TestCase
{
    private const RESOLVER = __DIR__.'/../Fixtures/theme/theme.resolver.json';

    public function testTheStylesheetWritesOnlyTheDarkDifferences(): void
    {
        $registry = new TokenRegistry(new ConfiguredTokenResolver(resolverPath: self::RESOLVER));
        $light = $registry->flatten(['scheme' => 'light']);
        $dark = $registry->flatten(['scheme' => 'dark']);

        self::assertCount(78, $light);
        self::assertCount(20, array_filter($light, static fn ($token, string $path): bool => (string) $token !== (string) $dark[$path], \ARRAY_FILTER_USE_BOTH));

        $css = new CssGenerator('dt')->generate($registry->all(['scheme' => 'light']), [GeneratorInterface::DARK_TOKENS => $registry->all(['scheme' => 'dark'])]);
        [$root, $darkBlock] = explode(':root[data-theme="dark"] {', $css, 2);

        self::assertStringContainsString('--dt-dimension-spacing-md: 1rem;', $root);

        // 18 colors, plus the shorthand and the color of the 2 borders.
        self::assertSame(22, substr_count($darkBlock, '--dt-'));
        self::assertStringContainsString('--dt-color-palette-surface: color(srgb 0.1294 0.1451 0.1608);', $darkBlock);
        self::assertStringContainsString('--dt-border-default-color:', $darkBlock);
        self::assertStringNotContainsString('--dt-dimension-spacing-md', $darkBlock);
    }

    public function testTheRegistryListsTheModifiersOfTheTheme(): void
    {
        $registry = new TokenRegistry(new ConfiguredTokenResolver(resolverPath: self::RESOLVER));

        self::assertSame([
            'brand' => ['contexts' => ['symfony', 'sky'], 'default' => 'symfony'],
            'scheme' => ['contexts' => ['light', 'dark'], 'default' => 'light'],
        ], $registry->getModifiers());
    }

    public function testATraceNamesTheBrandFileBehindAToken(): void
    {
        $resolution = new ConfiguredTokenResolver(resolverPath: self::RESOLVER)->trace(['brand' => 'sky', 'scheme' => 'dark']);

        self::assertSame('color(srgb 0.3255 0.5176 0.9294)', (string) $resolution->getTokens()['color']['action']['primary']);
        self::assertSame('system-ui, sans-serif', (string) $resolution->getTokens()['font']['family']['ui']);
        self::assertSame('brand-sky.tokens.json', basename($resolution->getSource('color.palette.brand')?->uri ?? ''));
    }

    public function testAnotherSelectionExportsAsJavaScript(): void
    {
        $registry = new TokenRegistry(new ConfiguredTokenResolver(resolverPath: self::RESOLVER));
        $module = new JavaScriptGenerator()->generate($registry->all(['brand' => 'sky', 'scheme' => 'dark']));

        self::assertStringContainsString('export const tokens = Object.freeze(JSON.parse(', $module);
        self::assertStringContainsString('\\"color.action.primary\\":\\"color(srgb 0.3255 0.5176 0.9294)\\"', $module);
        self::assertStringContainsString('\\"color.surface.canvas\\":\\"color(srgb 0.1294 0.1451 0.1608)\\"', $module);
        self::assertStringContainsString('\\"font.size.h1\\":\\"3.25rem\\"', $module);
        self::assertStringContainsString('\\"dimension.spacing.lg\\":\\"1.5rem\\"', $module);
        self::assertStringContainsString('\\"motion.control\\":\\"150ms cubic-bezier(0.2, 0, 0, 1) 0ms\\"', $module);
    }
}
