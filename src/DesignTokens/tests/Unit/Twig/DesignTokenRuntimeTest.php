<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\Twig;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Generator\ColorScheme;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Resolver\ArrayDocumentLoader;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Tests\Fixtures\Registries;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;
use Symfony\UX\DesignTokens\Tests\Fixtures\TokenValues;
use Symfony\UX\DesignTokens\Token\ColorToken;
use Symfony\UX\DesignTokens\TokenRegistry;
use Symfony\UX\DesignTokens\Twig\DesignTokenRuntime;

#[CoversClass(DesignTokenRuntime::class)]
final class DesignTokenRuntimeTest extends TestCase
{
    private TokenRegistry $registry;
    private DesignTokenRuntime $runtime;

    protected function setUp(): void
    {
        $this->registry = new TokenRegistry();
        $this->runtime = new DesignTokenRuntime($this->registry);
    }

    /** @param array<array-key, mixed> $data */
    private function loadTokens(array $data): void
    {
        $this->registry = Registries::fromArray($data);
        $this->runtime = new DesignTokenRuntime($this->registry);
    }

    public function testGetTokenDelegatesToTokenRegistry(): void
    {
        $this->loadTokens(['color' => ['brand' => ['$type' => 'color', '$value' => TokenValues::color()]]]);

        $token = $this->runtime->getToken('color.brand');

        self::assertInstanceOf(ColorToken::class, $token);
        self::assertSame('color(srgb 0.2 0.4 0.8)', (string) $token);
    }

    public function testGetTokenThrowsForUnknownPath(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->runtime->getToken('does.not.exist');
    }

    public function testGetTokenAcceptsResolverInputs(): void
    {
        $extension = self::colorSchemeRuntime();

        self::assertSame('1rem', (string) $extension->getToken('font.size.body'));
        self::assertSame('1.125rem', (string) $extension->getToken('font.size.body', ['surface' => 'docs']));
    }

    public function testRenderCssWrapsOutputInStyleTag(): void
    {
        $this->loadTokens(['c' => ['$type' => 'color', '$value' => TokenValues::color()]]);

        $html = $this->runtime->renderCss();

        self::assertStringStartsWith('<style>', $html);
        self::assertStringEndsWith('</style>', $html);
    }

    public function testRenderCssContainsCustomProperties(): void
    {
        $this->loadTokens(['color' => ['accent' => ['$type' => 'color', '$value' => TokenValues::color()]]]);

        $html = $this->runtime->renderCss();

        self::assertStringContainsString('--dt-color-accent', $html);
    }

    public function testRenderCssWritesTheDarkColorScheme(): void
    {
        [$root, $dark] = explode('@media (prefers-color-scheme: dark)', self::colorSchemeRuntime()->renderCss(), 2);

        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.2 0.4 0.8);', $root);
        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.5 0.6 0.9);', $dark);
        self::assertStringContainsString(':root[data-theme="dark"]', $dark);
    }

    public function testRenderCssAcceptsResolverInputs(): void
    {
        $html = self::colorSchemeRuntime()->renderCss(['surface' => 'docs']);

        self::assertStringContainsString('--dt-font-size-body: 1.125rem;', $html);
    }

    public function testTheDarkSchemeKeepsTheOtherInputs(): void
    {
        [$root, $dark] = explode('@media (prefers-color-scheme: dark)', self::colorSchemeRuntime()->renderCss(['package' => 'map']), 2);

        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.1 0.7 0.5);', $root);
        self::assertStringContainsString('--dt-color-surface-canvas: color(srgb 0.01 0.02 0.05);', $dark);
        self::assertStringNotContainsString('--dt-color-action-primary:', $dark);
    }

    public function testASelectedSchemeRendersOneResolution(): void
    {
        $html = self::colorSchemeRuntime()->renderCss(['scheme' => 'dark']);

        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.5 0.6 0.9);', $html);
        self::assertStringNotContainsString('prefers-color-scheme', $html);
    }

    public function testUsesTheConfiguredColorScheme(): void
    {
        self::assertStringContainsString('prefers-color-scheme', self::colorSchemeRuntime('named-theme.resolver.json', new ColorScheme('mode', 'day', 'night'))->renderCss());
        self::assertStringNotContainsString('prefers-color-scheme', self::colorSchemeRuntime('named-theme.resolver.json')->renderCss());
    }

    public function testRenderCssCannotCloseItsStyleElement(): void
    {
        $this->loadTokens(['unsafe' => ['$type' => 'fontFamily', '$value' => '</style><script>alert(1)</script>']]);

        $html = $this->runtime->renderCss();

        self::assertStringNotContainsString('</style><script>', $html);
        self::assertStringContainsString('\\3C /style>\\3C script>', $html);
    }

    public function testAnInvalidDarkTokenFailsTheRendering(): void
    {
        $registry = new TokenRegistry(new ConfiguredTokenResolver(new ArrayDocumentLoader(['theme.resolver.json' => [
            'version' => '2025.10',
            'modifiers' => ['scheme' => ['contexts' => [
                'light' => [['c' => ['$type' => 'color', '$value' => TokenValues::color()]]],
                'dark' => [['c' => ['$type' => 'color', '$value' => 'red']]],
            ], 'default' => 'light']],
            'resolutionOrder' => [['$ref' => '#/modifiers/scheme']],
        ]]), resolverPath: 'theme.resolver.json'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"c"');

        new DesignTokenRuntime($registry)->renderCss();
    }

    public function testInlineCssDoesNotWriteIntoTheBuildDirectory(): void
    {
        $buildDir = sys_get_temp_dir().'/ux-design-tokens-inline-'.bin2hex(random_bytes(6));

        self::assertStringContainsString('--dt-', self::colorSchemeRuntime(stylesheets: self::stylesheets($buildDir))->renderCss());
        self::assertDirectoryDoesNotExist($buildDir);
    }

    public function testTheWrittenStylesheetIsServedWithoutResolving(): void
    {
        $directory = self::warmedDirectory();

        try {
            self::assertStringContainsString('--dt-accent: rebeccapurple;', self::warmedRuntime($directory)->renderCss());
        } finally {
            $directory->remove();
        }
    }

    public function testTheWrittenStylesheetIsBypassedWhenInputsAreGiven(): void
    {
        $directory = self::warmedDirectory();

        try {
            $this->expectException(\LogicException::class);
            $this->expectExceptionMessage('ux_design_tokens.resolver.path');

            self::warmedRuntime($directory)->renderCss(['package' => 'map']);
        } finally {
            $directory->remove();
        }
    }

    private static function warmedDirectory(): TemporaryDirectory
    {
        $directory = new TemporaryDirectory();
        $directory->write(StylesheetCache::DIRECTORY.'/'.StylesheetCache::STYLESHEET, ':root { --dt-accent: rebeccapurple; }');

        return $directory;
    }

    private static function warmedRuntime(TemporaryDirectory $buildDir): DesignTokenRuntime
    {
        return new DesignTokenRuntime(new TokenRegistry(), stylesheets: self::stylesheets($buildDir->path()));
    }

    private static function stylesheets(string $buildDir): StylesheetCache
    {
        return new StylesheetCache(new ConfiguredTokenResolver(), new CssGenerator('dt'), $buildDir);
    }

    private static function colorSchemeRuntime(string $resolver = 'theme.resolver.json', ColorScheme $colorScheme = new ColorScheme(), ?StylesheetCache $stylesheets = null): DesignTokenRuntime
    {
        return new DesignTokenRuntime(Registries::colorScheme($resolver), $colorScheme, $stylesheets);
    }

    public function testRenderStylesheetLinksTheVersionedAsset(): void
    {
        $runtime = new DesignTokenRuntime(new TokenRegistry(), assetMapper: new AssetMapperStub([
            'design-tokens/tokens.css' => '/assets/design-tokens/tokens-CuTbN4D.css',
        ]));

        self::assertSame('<link rel="stylesheet" href="/assets/design-tokens/tokens-CuTbN4D.css">', $runtime->renderStylesheet());
    }

    public function testRenderStylesheetEscapesThePublicPath(): void
    {
        $runtime = new DesignTokenRuntime(new TokenRegistry(), assetMapper: new AssetMapperStub([
            'design-tokens/tokens.css' => '/assets/"onload="alert(1)',
        ]));

        self::assertStringNotContainsString('"onload=', $runtime->renderStylesheet());
        self::assertStringContainsString('&quot;onload=', $runtime->renderStylesheet());
    }

    /**
     * @param array<string, string>|null $publicPaths
     * @param class-string<\Throwable>   $exception
     */
    #[DataProvider('stylesheetFailures')]
    public function testRenderStylesheetSaysWhyItCannotLinkTheAsset(?array $publicPaths, string $exception, string $message): void
    {
        $runtime = new DesignTokenRuntime(new TokenRegistry(), assetMapper: null === $publicPaths ? null : new AssetMapperStub($publicPaths));

        $this->expectException($exception);
        $this->expectExceptionMessage($message);

        $runtime->renderStylesheet();
    }

    /** @return iterable<string, array{array<string, string>|null, class-string<\Throwable>, string}> */
    public static function stylesheetFailures(): iterable
    {
        yield 'no AssetMapper points at the alternative' => [null, \LogicException::class, 'composer require symfony/asset-mapper'];
        yield 'missing asset says what to run' => [[], \RuntimeException::class, 'No design token stylesheet was found at "design-tokens/tokens.css"'];
    }
}

final class AssetMapperStub implements AssetMapperInterface
{
    /** @param array<string, string> $publicPaths */
    public function __construct(private readonly array $publicPaths)
    {
    }

    public function getAsset(string $logicalPath): ?MappedAsset
    {
        return null;
    }

    public function allAssets(): iterable
    {
        return [];
    }

    public function getAssetFromSourcePath(string $sourcePath): ?MappedAsset
    {
        return null;
    }

    public function getPublicPath(string $logicalPath): ?string
    {
        return $this->publicPaths[$logicalPath] ?? null;
    }
}
