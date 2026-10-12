<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\CacheWarmer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\DesignTokens\CacheWarmer\DesignTokensCacheWarmer;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;

#[CoversClass(DesignTokensCacheWarmer::class)]
final class DesignTokensCacheWarmerTest extends TestCase
{
    private string $buildDir;

    protected function setUp(): void
    {
        $this->buildDir = sys_get_temp_dir().'/ux-design-tokens-warmer-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->buildDir);
    }

    public function testWarmupIsOptional(): void
    {
        self::assertTrue($this->warmer()->isOptional());
    }

    public function testWritesTheStylesheetAndReturnsNoFileToPreload(): void
    {
        self::assertSame([], $this->warmer()->warmUp($this->buildDir, $this->buildDir));

        self::assertStringContainsString('--dt-color-action-primary:', (string) file_get_contents($this->directory().'/tokens.css'));
    }

    private function directory(): string
    {
        return StylesheetCache::directory($this->buildDir);
    }

    private function warmer(): DesignTokensCacheWarmer
    {
        return new DesignTokensCacheWarmer(new StylesheetCache(
            new ConfiguredTokenResolver(resolverPath: \dirname(__DIR__, 2).'/Fixtures/color-scheme/theme.resolver.json'),
            new CssGenerator('dt'),
            $this->buildDir,
        ));
    }
}
