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
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;

#[CoversClass(StylesheetCache::class)]
final class StylesheetCacheTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/dt-stylesheets-'.bin2hex(random_bytes(4));
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->directory.'/a.tokens.json', '{"c":{"$type":"number","$value":1},"d":{"$ref":"b.json#/d"}}');
        $filesystem->dumpFile($this->directory.'/b.json', '{"d":{"$type":"number","$value":10}}');
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
    }

    public function testWritesAMissingStylesheet(): void
    {
        $path = $this->cache(debug: false)->path();

        self::assertFileExists($path);
        self::assertStringContainsString('--dt-c: 1;', (string) file_get_contents($path));
    }

    public function testRebuildsInDebugWhenADocumentChanges(): void
    {
        $cache = $this->cache(debug: true);
        $cache->path();
        $this->rewrite('a.tokens.json', '{"c":{"$type":"number","$value":2}}');

        self::assertStringContainsString('--dt-c: 2;', (string) file_get_contents($cache->path()));
    }

    public function testRebuildsInDebugWhenAFileReachedByAReferenceChanges(): void
    {
        $cache = $this->cache(debug: true);
        $cache->path();
        $this->rewrite('b.json', '{"d":{"$type":"number","$value":20}}');

        self::assertStringContainsString('--dt-d: 20;', (string) file_get_contents($cache->path()));
    }

    public function testKeepsTheStylesheetOutsideDebug(): void
    {
        $cache = $this->cache(debug: false);
        $cache->path();
        $this->rewrite('a.tokens.json', '{"c":{"$type":"number","$value":2}}');

        self::assertStringContainsString('--dt-c: 1;', (string) file_get_contents($cache->path()));
    }

    public function testRebuildsInDebugWhenTheConfigurationChanges(): void
    {
        $this->cache(debug: true, fingerprint: 'a')->path();

        $path = $this->cache(debug: true, prefix: 'app', fingerprint: 'b')->path();

        self::assertStringContainsString('--app-c: 1;', (string) file_get_contents($path));
    }

    public function testReadsNothingWithoutWritingAMissingStylesheet(): void
    {
        self::assertNull($this->cache(debug: true)->read());
        self::assertDirectoryDoesNotExist($this->directory.'/build');
    }

    public function testReadsAStylesheetThatIsFresh(): void
    {
        $cache = $this->cache(debug: true);
        $cache->path();

        self::assertStringContainsString('--dt-c: 1;', (string) $cache->read());
    }

    public function testReadsNothingOnceAStylesheetIsStale(): void
    {
        $cache = $this->cache(debug: true);
        $cache->path();
        $this->rewrite('a.tokens.json', '{"c":{"$type":"number","$value":2}}');

        self::assertNull($cache->read());
    }

    public function testWritesTheDarkSchemeOfTheDefaultSelection(): void
    {
        $css = (string) file_get_contents(new StylesheetCache(
            new ConfiguredTokenResolver(resolverPath: \dirname(__DIR__, 2).'/Fixtures/color-scheme/theme.resolver.json'),
            new CssGenerator('dt'),
            $this->directory.'/build',
        )->path());

        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.2 0.4 0.8);', $css);
        self::assertStringContainsString(':root[data-theme="dark"]', $css);
        self::assertStringContainsString('--dt-color-action-primary: color(srgb 0.5 0.6 0.9);', $css);
    }

    private function rewrite(string $file, string $json): void
    {
        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->directory.'/'.$file, $json);
        $filesystem->touch($this->directory.'/'.$file, time() + 10);
    }

    private function cache(bool $debug, string $prefix = 'dt', string $fingerprint = ''): StylesheetCache
    {
        return new StylesheetCache(
            new ConfiguredTokenResolver(paths: [$this->directory.'/a.tokens.json']),
            new CssGenerator($prefix),
            $this->directory.'/build',
            debug: $debug,
            fingerprint: $fingerprint,
        );
    }
}
