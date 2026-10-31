<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DesignTokens\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\AssetMapper\Event\PreAssetsCompileEvent;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\UX\DesignTokens\CacheWarmer\DesignTokensCacheWarmer;
use Symfony\UX\DesignTokens\CacheWarmer\StylesheetCache;
use Symfony\UX\DesignTokens\EventListener\WarmDesignTokensOnAssetCompileListener;
use Symfony\UX\DesignTokens\Generator\CssGenerator;
use Symfony\UX\DesignTokens\Resolver\ConfiguredTokenResolver;
use Symfony\UX\DesignTokens\Tests\Fixtures\TemporaryDirectory;

#[CoversClass(WarmDesignTokensOnAssetCompileListener::class)]
final class WarmDesignTokensOnAssetCompileListenerTest extends TestCase
{
    private TemporaryDirectory $directory;
    private string $buildDir;

    protected function setUp(): void
    {
        $this->directory = new TemporaryDirectory();
        $this->buildDir = $this->directory->path();
    }

    protected function tearDown(): void
    {
        $this->directory->remove();
    }

    public function testRendersTheStylesheetBeforeTheAssetsAreCompiled(): void
    {
        $warmer = new DesignTokensCacheWarmer(new StylesheetCache(
            new ConfiguredTokenResolver(resolverPath: \dirname(__DIR__, 2).'/Fixtures/color-scheme/theme.resolver.json'),
            new CssGenerator('dt'),
            $this->buildDir,
        ));
        $output = new BufferedOutput();

        new WarmDesignTokensOnAssetCompileListener($warmer, $this->buildDir)(new PreAssetsCompileEvent($output));

        self::assertFileExists(StylesheetCache::directory($this->buildDir).'/tokens.css');
        self::assertStringContainsString('Rendering the design token stylesheet...', $output->fetch());
    }
}
