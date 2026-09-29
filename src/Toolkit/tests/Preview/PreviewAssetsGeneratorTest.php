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
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Preview\PreviewAssetsGenerator;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class PreviewAssetsGeneratorTest extends TestCase
{
    use TestHelperTrait;

    private Filesystem $filesystem;
    private string $workDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_preview_');
        $this->filesystem->remove($this->workDir);
        $this->filesystem->mkdir($this->workDir);
        $this->workDir = Path::canonicalize($this->workDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workDir);
    }

    public function testGeneratesTailwindEntryFromImportmapDependencies(): void
    {
        $kit = $this->copyFixtureKit('preview');

        $this->createGenerator()->generate('preview', $kit, $this->workDir.'/output');

        $expected = <<<CSS
            @import "tailwindcss";
            @import "/project/assets/vendor/tw-animate-css/dist/tw-animate.css";
            @import "/project/assets/vendor/widget-lib/dist/widget.css";
            @import "{$this->workDir}/kits/preview/kit.css";
            @source "/project/assets/vendor/flowbite";
            @source "{$this->workDir}/kits/preview";

            CSS;
        $this->assertSame($expected, file_get_contents($this->workDir.'/output/ux-toolkit-preview.css'));
    }

    public function testGeneratesJavaScriptEntrypointRegisteringControllersInPathOrder(): void
    {
        $kit = $this->copyFixtureKit('preview');

        $this->createGenerator()->generate('preview', $kit, $this->workDir.'/output');

        $expected = <<<'JS'
            import "./ux-toolkit-preview.css";
            import "../kits/preview/kit.js";
            import { Application } from "@hotwired/stimulus";
            import controller0 from "../kits/preview/other/assets/controllers/widget_controller.js";
            import controller1 from "../kits/preview/widget/assets/controllers/date_picker_controller.js";
            import controller2 from "../kits/preview/widget/assets/controllers/widget_controller.js";

            const app = Application.start();
            app.register("widget", controller0);
            app.register("date-picker", controller1);
            app.register("widget", controller2);

            JS;
        $this->assertSame($expected, file_get_contents($this->workDir.'/output/ux-toolkit-preview.js'));
    }

    public function testKitWithoutStylesheetImportsItsCssPackagesFromItsEntrypoint(): void
    {
        $kit = $this->copyFixtureKit('preview-plain');

        $files = $this->createGenerator()->generate('preview-plain', $kit, $this->workDir.'/output');

        $expected = <<<'JS'
            import "bootstrap/dist/css/bootstrap.min.css";
            import { Application } from "@hotwired/stimulus";

            const app = Application.start();

            JS;
        $this->assertSame($expected, file_get_contents($this->workDir.'/output/ux-toolkit-preview-plain.js'));
        $this->assertSame([$this->workDir.'/output/ux-toolkit-preview-plain.js'], $files);
    }

    public function testNamesTheGeneratedFilesAfterTheGivenKitName(): void
    {
        $kit = $this->copyFixtureKit('preview');

        $files = $this->createGenerator()->generate('acme', $kit, $this->workDir.'/output');

        $this->assertSame([$this->workDir.'/output/ux-toolkit-acme.js', $this->workDir.'/output/ux-toolkit-acme.css'], $files);
    }

    public function testDoesNotRewriteUnchangedFiles(): void
    {
        $kit = $this->copyFixtureKit('preview');
        $generator = $this->createGenerator();
        $generator->generate('preview', $kit, $this->workDir.'/output');
        touch($this->workDir.'/output/ux-toolkit-preview.js', 1000000000);

        $generator->generate('preview', $kit, $this->workDir.'/output');

        clearstatcache();
        $this->assertSame(1000000000, filemtime($this->workDir.'/output/ux-toolkit-preview.js'));
    }

    public function testGeneratesEvenWhenImportmapPackagesAreNotDownloadedYet(): void
    {
        $kit = $this->copyFixtureKit('preview');
        $generator = new PreviewAssetsGenerator(new RemotePackageStorage($this->workDir.'/missing-vendor'), $this->filesystem);

        $generator->generate('preview', $kit, $this->workDir.'/output');

        $this->assertFileExists($this->workDir.'/output/ux-toolkit-preview.css');
    }

    private function createGenerator(): PreviewAssetsGenerator
    {
        return new PreviewAssetsGenerator(new RemotePackageStorage('/project/assets/vendor'), $this->filesystem);
    }

    private function copyFixtureKit(string $kitName): Kit
    {
        $kitDir = $this->workDir.'/kits/'.$kitName;
        $this->filesystem->mirror(self::getFixtureKitPath($kitName), $kitDir);
        $kitFactory = new KitFactory($this->filesystem, new KitSynchronizer($this->filesystem, new RecipeSynchronizer()));

        return $kitFactory->createKitFromAbsolutePath($kitDir);
    }
}
