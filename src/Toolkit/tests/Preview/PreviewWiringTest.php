<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Preview;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Tests\Fixtures\PreviewKernel;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class PreviewWiringTest extends TestCase
{
    use TestHelperTrait;

    private Filesystem $filesystem;
    private string $projectDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        // On the drive of the kits: relative imports cannot cross Windows drives.
        $varDir = Path::join(__DIR__, '../../var');
        $this->filesystem->mkdir($varDir);
        $this->projectDir = $this->filesystem->tempnam($varDir, 'preview_app_');
        $this->filesystem->remove($this->projectDir);
        $this->filesystem->dumpFile($this->projectDir.'/assets/styles/app.css', "@import \"tailwindcss\";\n");
        $this->projectDir = Path::canonicalize(realpath($this->projectDir));
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->projectDir);
    }

    public function testGeneratesPreviewFilesForEveryLocalKitAtBoot(): void
    {
        $this->bootKernel();

        $outputDir = $this->projectDir.'/var/ux_toolkit/preview';
        foreach (['bootstrap', 'common', 'flowbite-4', 'shadcn'] as $kitId) {
            $this->assertFileExists($outputDir.'/ux-toolkit-'.$kitId.'.js', \sprintf('The script of the kit "%s" is not generated at boot.', $kitId));
        }
        $this->assertFileDoesNotExist($outputDir.'/ux-toolkit-bootstrap.css', 'A stylesheet is generated for "bootstrap", which ships no "kit.css".');
        $this->assertStringContainsString('/assets/vendor/shadcn/dist/tailwind.css', file_get_contents($outputDir.'/ux-toolkit-shadcn.css'));
        $this->assertStringContainsString('/assets/vendor/flowbite/dist/flowbite.min.css', file_get_contents($outputDir.'/ux-toolkit-flowbite-4.css'));
    }

    public function testAppendsKitStylesheetsAfterTheApplicationTailwindInputs(): void
    {
        $container = $this->bootKernel()->getContainer();

        $inputs = array_map(Path::canonicalize(...), $container->get('test.tailwind.builder')->getInputCssPaths());

        $outputDir = $this->projectDir.'/var/ux_toolkit/preview';
        $this->assertSame([
            $this->projectDir.'/assets/styles/app.css',
            $outputDir.'/ux-toolkit-common.css',
            $outputDir.'/ux-toolkit-flowbite-4.css',
            $outputDir.'/ux-toolkit-shadcn.css',
        ], $inputs);
    }

    public function testExposesOneImportmapEntrypointPerKit(): void
    {
        $container = $this->bootKernel()->getContainer();

        $entries = $container->get('test.importmap.config_reader')->getEntries();

        $this->assertTrue($entries->has('ux-toolkit-shadcn'));
        $this->assertTrue($entries->get('ux-toolkit-shadcn')->isEntrypoint);
    }

    public function testMapsKitsAndGeneratedFilesInAssetMapper(): void
    {
        $container = $this->bootKernel()->getContainer();

        $asset = $container->get('test.asset_mapper')->getAsset('@symfony/ux-toolkit/preview/ux-toolkit-shadcn.js');

        $importedPaths = array_map(static fn ($import) => $import->assetLogicalPath, $asset->getJavaScriptImports());
        $this->assertContains('@symfony/ux-toolkit/kits/shadcn/accordion/assets/controllers/accordion_controller.js', $importedPaths);
        $this->assertContains('@symfony/ux-toolkit/preview/ux-toolkit-shadcn.css', $importedPaths);
    }

    public function testRegistersNothingByDefault(): void
    {
        $container = $this->bootKernel(preview: false)->getContainer();

        $this->assertDirectoryDoesNotExist($this->projectDir.'/var/ux_toolkit', 'Preview files are generated while the preview is disabled.');
        $this->assertFalse($container->get('test.importmap.config_reader')->getEntries()->has('ux-toolkit-shadcn'));
        $this->assertCount(1, $container->get('test.tailwind.builder')->getInputCssPaths());
        $this->assertNull($container->get('test.asset_mapper')->getAsset('@symfony/ux-toolkit/kits/shadcn/accordion/assets/controllers/accordion_controller.js'), 'Kits are mapped while the preview is disabled.');
    }

    public function testOnlyPublishesTheScriptsOfTheKits(): void
    {
        $assetMapper = $this->bootKernel(preview: ['kits' => ['shadcn', self::getFixtureKitPath('preview')]])->getContainer()->get('test.asset_mapper');

        $notMapped = [
            'shadcn/manifest.json',
            'shadcn/INSTALL.md',
            'shadcn/button/README.md',
            'shadcn/button/templates/components/Button.html.twig',
            'preview/widget/manifest.json',
            'preview/widget/config/widget.yaml',
            'preview/widget/NOTES.txt',
            'preview/widget/tests/widget.spec.ts',
            'preview/widget/tests/screenshots/default-light.png',
            'preview/LICENSE',
            'shadcn/kit.css',
        ];
        $mapped = [
            'preview/kit.js',
            'preview/widget/assets/controllers/widget_controller.js',
        ];

        foreach ($notMapped as $path) {
            $this->assertNull($assetMapper->getAsset('@symfony/ux-toolkit/kits/'.$path), \sprintf('Only the ".js" files of a kit are mapped, but "%s" is.', $path));
        }
        foreach ($mapped as $path) {
            $this->assertNotNull($assetMapper->getAsset('@symfony/ux-toolkit/kits/'.$path), \sprintf('The ".js" files of a kit are mapped, but "%s" is not.', $path));
        }
    }

    public function testPreviewsOnlyTheListedKits(): void
    {
        $container = $this->bootKernel(preview: ['kits' => ['shadcn']])->getContainer();

        $outputDir = $this->projectDir.'/var/ux_toolkit/preview';
        $this->assertSame(['shadcn'], array_keys($container->get('test.preview.kit_registry')->getKits()));
        $inputs = array_map(Path::canonicalize(...), $container->get('test.tailwind.builder')->getInputCssPaths());
        $this->assertSame([$this->projectDir.'/assets/styles/app.css', $outputDir.'/ux-toolkit-shadcn.css'], $inputs);
        $this->assertFalse($container->get('test.importmap.config_reader')->getEntries()->has('ux-toolkit-flowbite-4'));
        $this->assertFileDoesNotExist($outputDir.'/ux-toolkit-flowbite-4.js', 'A script is generated for "flowbite-4", which is not in the previewed kits.');
        $this->assertNull($container->get('test.asset_mapper')->getAsset('@symfony/ux-toolkit/kits/flowbite-4/kit.js'), '"flowbite-4" is mapped, but it is not in the previewed kits.');
    }

    public function testPreviewsExternalKits(): void
    {
        $container = $this->bootKernel(preview: ['kits' => [self::getFixtureKitPath('preview')]])->getContainer();

        $asset = $container->get('test.asset_mapper')->getAsset('@symfony/ux-toolkit/preview/ux-toolkit-preview.js');

        $importedPaths = array_map(static fn ($import) => $import->assetLogicalPath, $asset->getJavaScriptImports());
        $this->assertContains('@symfony/ux-toolkit/kits/preview/widget/assets/controllers/widget_controller.js', $importedPaths);
        $inputs = array_map(Path::canonicalize(...), $container->get('test.tailwind.builder')->getInputCssPaths());
        $this->assertContains($this->projectDir.'/var/ux_toolkit/preview/ux-toolkit-preview.css', $inputs);
        $this->assertTrue($container->get('test.importmap.config_reader')->getEntries()->has('ux-toolkit-preview'));
    }

    public function testExposesLocalAndExternalKitsByName(): void
    {
        $this->filesystem->mirror(self::getFixtureKitPath('preview'), $this->projectDir.'/kits/acme');

        $container = $this->bootKernel(preview: ['kits' => ['shadcn', 'kits/acme']])->getContainer();

        $kits = $container->get('test.preview.kit_registry')->getKits();
        $this->assertSame(['shadcn', 'acme'], array_keys($kits));
        $this->assertSame($this->projectDir.'/kits/acme', Path::canonicalize($kits['acme']->absolutePath));
    }

    public function testRemovesThePreviewFilesOfKitsNoLongerPreviewed(): void
    {
        $this->bootKernel(preview: ['kits' => ['shadcn', 'common']]);

        $this->bootKernel(preview: ['kits' => ['shadcn']]);

        $this->assertFileExists($this->projectDir.'/var/ux_toolkit/preview/ux-toolkit-shadcn.js', 'The script of "shadcn", still previewed, is removed.');
        $this->assertFileDoesNotExist($this->projectDir.'/var/ux_toolkit/preview/ux-toolkit-common.js', 'The script of "common", no longer previewed, is kept.');
    }

    public function testResolvesParametersInTheConfiguration(): void
    {
        $this->filesystem->mirror(self::getFixtureKitPath('preview'), $this->projectDir.'/kits/acme');

        $container = $this->bootKernel(preview: '%kernel.debug%')->getContainer();
        $this->assertTrue($container->has('test.preview.kit_registry'));

        $container = $this->bootKernel(preview: ['kits' => ['%kernel.project_dir%/kits/acme']])->getContainer();
        $this->assertSame(['acme'], array_keys($container->get('test.preview.kit_registry')->getKits()));
    }

    public function testRejectsAnExternalKitNamedLikeAnotherPreviewedKit(): void
    {
        $this->filesystem->mirror(self::getFixtureKitPath('preview'), $this->projectDir.'/forks/shadcn');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"shadcn"');

        $this->bootKernel(preview: ['kits' => ['shadcn', 'forks/shadcn']]);
    }

    public function testRejectsAnExternalKitWithoutManifest(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('manifest.json');

        $this->bootKernel(preview: ['kits' => ['assets']]);
    }

    public function testPreviewsKitsWithoutStylesheetWithoutTheTailwindBundle(): void
    {
        $container = $this->bootKernel(preview: ['kits' => ['bootstrap']], withTailwind: false)->getContainer();

        $this->assertTrue($container->get('test.importmap.config_reader')->getEntries()->has('ux-toolkit-bootstrap'));
    }

    public function testRequiresTheTailwindBundle(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('symfonycasts/tailwind-bundle');

        $this->bootKernel(withTailwind: false);
    }

    private function bootKernel(array|bool|string $preview = true, bool $withTailwind = true): PreviewKernel
    {
        $kernel = new PreviewKernel($this->projectDir, $preview, $withTailwind);
        $kernel->boot();

        return $kernel;
    }
}
