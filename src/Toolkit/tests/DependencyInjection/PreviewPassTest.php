<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Resource\FileExistenceResource;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\DependencyInjection\PreviewPass;
use Symfony\UX\Toolkit\Preview\PreviewFilesResource;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class PreviewPassTest extends TestCase
{
    use TestHelperTrait;

    private string $outputDir;

    protected function setUp(): void
    {
        $this->outputDir = sys_get_temp_dir().'/ux_toolkit_preview_pass_'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->outputDir);
    }

    public function testRebuildsTheContainerWhenAKitChanges(): void
    {
        $container = $this->createContainer();

        new PreviewPass()->process($container);

        $kitDir = self::getFixtureKitPath('preview');
        $resources = array_map('strval', $container->getResources());
        $this->assertContains((string) new FileResource($kitDir.'/manifest.json'), $resources);
        $this->assertContains((string) new FileResource($kitDir.'/widget/manifest.json'), $resources);
        $this->assertContains((string) new FileResource($kitDir.'/other/manifest.json'), $resources);
        $this->assertContains((string) new GlobResource($kitDir, '/*/manifest.json', false), $resources);
        $this->assertContains((string) new FileExistenceResource($kitDir.'/kit.css'), $resources);
        $this->assertContains((string) new FileExistenceResource($kitDir.'/kit.js'), $resources);
        $this->assertContains((string) new GlobResource($kitDir, '/*/assets/controllers/**/*_controller.js', false), $resources);
    }

    public function testRebuildsTheContainerWhenAGeneratedFileIsMissingWithoutCreatingAnyAtCompileTime(): void
    {
        $container = $this->createContainer();

        new PreviewPass()->process($container);

        $this->assertDirectoryDoesNotExist($this->outputDir);
        $previewFiles = array_values(array_filter($container->getResources(), static fn ($resource): bool => $resource instanceof PreviewFilesResource));
        $this->assertCount(1, $previewFiles);
        $this->assertFalse($previewFiles[0]->isFresh(time()));

        new Filesystem()->dumpFile($this->outputDir.'/ux-toolkit-preview.js', '');
        new Filesystem()->dumpFile($this->outputDir.'/ux-toolkit-preview.css', '');

        $this->assertTrue($previewFiles[0]->isFresh(time()));
    }

    public function testRequiresATailwindBundleWithSeveralInputs(): void
    {
        $container = $this->createContainer(tailwindInputs: 'assets/styles/app.css');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"symfonycasts/tailwind-bundle" 0.6 or later');

        new PreviewPass()->process($container);
    }

    /**
     * @param list<string>|string $tailwindInputs
     */
    private function createContainer(array|string $tailwindInputs = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('.ux_toolkit.preview.output_dir', $this->outputDir);
        $container->setParameter('.ux_toolkit.preview.kit_dirs', ['preview' => self::getFixtureKitPath('preview')]);
        $container->setDefinition('tailwind.builder', new Definition(null, ['/project', $tailwindInputs]));
        $container->setDefinition('asset_mapper.importmap.config_reader', new Definition(null, ['/project/importmap.php', null]));
        $container->setDefinition('.ux_toolkit.preview.importmap_config_reader', new Definition(null, [null, null, null, null]));
        $container->setDefinition('.ux_toolkit.preview.kit_registry', new Definition(null, [null, null]));
        $container->setDefinition('.ux_toolkit.preview.cache_warmer', new Definition(null, [null, null, null, null]));

        return $container;
    }
}
