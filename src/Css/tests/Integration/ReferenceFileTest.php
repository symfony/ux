<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Css\Reference\ReferenceDumper;
use Symfony\UX\Css\Tests\Fixtures\Dtcg;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Symfony\UX\Css\Validation\StyleValidator;

final class ReferenceFileTest extends TestCase
{
    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = TestKernel::temporaryDirectory();
        new Filesystem()->mkdir([$this->projectDir.'/config', $this->projectDir.'/templates']);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->projectDir.'/config')) {
            new Filesystem()->chmod($this->projectDir.'/config', 0o755);
        }
        new Filesystem()->remove($this->projectDir);
    }

    public function testTheReferenceIsWrittenInDebug(): void
    {
        $kernel = $this->bootKernel();

        $this->assertStringEqualsFile($this->referencePath(), self::expectedReference($kernel));
    }

    public function testNoReferenceIsWrittenOutsideOfDebug(): void
    {
        $this->bootKernel(debug: false);

        $this->assertFileDoesNotExist($this->referencePath());
    }

    public function testAnUnchangedConfigLeavesTheFileAlone(): void
    {
        $this->bootKernel();
        touch($this->referencePath(), 1000);

        $this->bootKernel();

        clearstatcache();
        $this->assertSame(1000, filemtime($this->referencePath()));
    }

    public function testAChangedConfigRewritesTheFile(): void
    {
        $this->bootKernel();
        $tokens = self::designTokens();
        $tokens['color']['teal'] = Dtcg::color('#0ff');

        $this->bootKernel(tokens: $tokens);

        $this->assertStringContainsString("'teal'", file_get_contents($this->referencePath()));
    }

    public function testAMissingConfigDirectoryIsLeftAlone(): void
    {
        new Filesystem()->remove($this->projectDir.'/config');

        $this->bootKernel();

        $this->assertDirectoryDoesNotExist($this->projectDir.'/config');
    }

    public function testAReadOnlyConfigDirectoryIsLeftAlone(): void
    {
        new Filesystem()->chmod($this->projectDir.'/config', 0o555);

        $this->bootKernel();

        $this->assertFileDoesNotExist($this->referencePath());
    }

    private function bootKernel(bool $debug = true, ?array $tokens = null): TestKernel
    {
        $kernel = new TestKernel(
            debug: $debug,
            projectDir: $this->projectDir,
            designTokens: $tokens ?? self::designTokens(),
        );
        $kernel->boot();

        return $kernel;
    }

    private function referencePath(): string
    {
        return $this->projectDir.'/config/reference_css.php';
    }

    private static function expectedReference(TestKernel $kernel): string
    {
        $engine = $kernel->getContainer()->get('ux_css.engine');

        return new ReferenceDumper(new StyleValidator($engine))->dump();
    }

    private static function designTokens(): array
    {
        return [
            'color' => ['red' => Dtcg::color('#f00')],
            'dimension' => ['spacing' => ['md' => Dtcg::dimension(1)]],
        ];
    }
}
