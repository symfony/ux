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
use Symfony\UX\Css\DependencyInjection\PandaConfigConverter;
use Symfony\UX\Css\Engine\Engine;
use Symfony\UX\Css\Reference\ReferenceDumper;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Symfony\UX\Css\Validation\StyleValidator;

final class ReferenceFileTest extends TestCase
{
    private const CONFIG = [
        'tokens' => ['colors' => ['red' => '#f00'], 'spacing' => ['md' => '1rem']],
    ];

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
        $this->bootKernel();

        $this->assertStringEqualsFile($this->referencePath(), self::expectedReference(self::CONFIG));
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
        $config = ['tokens' => ['colors' => ['red' => '#f00', 'teal' => '#0ff'], 'spacing' => ['md' => '1rem']]];

        $this->bootKernel(config: $config);

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

    private function bootKernel(bool $debug = true, array $config = self::CONFIG): void
    {
        $kernel = new TestKernel($config, 'test', $debug, $this->projectDir);
        $kernel->boot();
    }

    private function referencePath(): string
    {
        return $this->projectDir.'/config/reference_css.php';
    }

    private static function expectedReference(array $config): string
    {
        $defaults = ['semantic_tokens' => [], 'conditions' => [], 'breakpoints' => []];
        $project = PandaConfigConverter::convert($config + $defaults);
        $validator = new StyleValidator(Engine::fromProjectConfig($project));

        return new ReferenceDumper($validator)->dump();
    }
}
