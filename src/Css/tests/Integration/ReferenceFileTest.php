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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Css\DependencyInjection\Compiler\ReferenceDumpPass;
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

    public function testASecondContainerBuildDoesNotGenerateTheReferenceAgain(): void
    {
        $container = $this->containerBuilder(self::CONFIG);
        new ReferenceDumpPass()->process($container);
        $decoy = $this->replaceReferenceWithADecoy();

        new ReferenceDumpPass()->process($this->containerBuilder(self::CONFIG));

        $this->assertStringEqualsFile($this->referencePath(), $decoy);
    }

    public function testADeletedReferenceIsGeneratedAgain(): void
    {
        new ReferenceDumpPass()->process($this->containerBuilder(self::CONFIG));
        unlink($this->referencePath());

        new ReferenceDumpPass()->process($this->containerBuilder(self::CONFIG));

        $this->assertStringEqualsFile($this->referencePath(), self::expectedReference(self::CONFIG));
    }

    public function testAChangedConfigGeneratesTheReferenceAgain(): void
    {
        new ReferenceDumpPass()->process($this->containerBuilder(self::CONFIG));
        $this->replaceReferenceWithADecoy();
        $config = ['tokens' => ['colors' => ['red' => '#f00', 'teal' => '#0ff'], 'spacing' => ['md' => '1rem']]];

        new ReferenceDumpPass()->process($this->containerBuilder($config));

        $this->assertStringContainsString("'teal'", file_get_contents($this->referencePath()));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function containerBuilder(array $config): ContainerBuilder
    {
        $defaults = ['semantic_tokens' => [], 'conditions' => [], 'breakpoints' => []];
        $container = new ContainerBuilder();
        $container->register('ux_css.engine', Engine::class)->setArguments([PandaConfigConverter::convert($config + $defaults)]);
        $container->register('ux_css.validator', StyleValidator::class)->setArguments([null, true, true]);
        $container->setParameter('kernel.project_dir', $this->projectDir);
        $container->setParameter('kernel.build_dir', $this->projectDir.'/build');

        return $container;
    }

    /**
     * Keeps the size and the modification time of the reference, so that only a skipped generation leaves it in place.
     */
    private function replaceReferenceWithADecoy(): string
    {
        $path = $this->referencePath();
        $decoy = str_repeat('x', \strlen(file_get_contents($path)));
        $modifiedAt = filemtime($path);
        file_put_contents($path, $decoy);
        touch($path, $modifiedAt);
        clearstatcache();

        return $decoy;
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
