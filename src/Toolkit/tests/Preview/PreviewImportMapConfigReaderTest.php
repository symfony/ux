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
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Preview\PreviewImportMapConfigReader;

final class PreviewImportMapConfigReaderTest extends TestCase
{
    private Filesystem $filesystem;
    private string $workDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->workDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_importmap_');
        $this->filesystem->remove($this->workDir);
        $this->filesystem->dumpFile($this->workDir.'/importmap.php', "<?php\n\nreturn ['app' => ['path' => './assets/app.js', 'entrypoint' => true]];\n");
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->workDir);
    }

    public function testExposesGeneratedEntrypoints(): void
    {
        $reader = $this->createReader(['ux-toolkit-shadcn' => '/project/var/ux_toolkit/preview/ux-toolkit-shadcn.js']);

        $entry = $reader->getEntries()->get('ux-toolkit-shadcn');

        $this->assertSame('/project/var/ux_toolkit/preview/ux-toolkit-shadcn.js', $entry->path);
        $this->assertTrue($entry->isEntrypoint);
        $this->assertTrue($reader->getEntries()->has('app'));
    }

    public function testNeverWritesGeneratedEntrypoints(): void
    {
        $reader = $this->createReader(['ux-toolkit-shadcn' => '/project/var/ux_toolkit/preview/ux-toolkit-shadcn.js']);
        $entries = $reader->getEntries();
        $entries->add(ImportMapEntry::createLocal('other', ImportMapType::JS, './assets/other.js', false));

        $reader->writeEntries($entries);

        $written = include $this->workDir.'/importmap.php';
        $this->assertSame(['app', 'other'], array_keys($written));
    }

    public function testKeepsAHostEntryWithTheSameName(): void
    {
        $this->filesystem->dumpFile($this->workDir.'/importmap.php', "<?php\n\nreturn ['ux-toolkit-shadcn' => ['path' => './assets/mine.js', 'entrypoint' => true]];\n");
        $reader = $this->createReader(['ux-toolkit-shadcn' => '/project/var/ux_toolkit/preview/ux-toolkit-shadcn.js']);

        $reader->writeEntries($reader->getEntries());

        $this->assertSame('./assets/mine.js', $reader->getEntries()->get('ux-toolkit-shadcn')->path);
        $written = include $this->workDir.'/importmap.php';
        $this->assertSame('./assets/mine.js', $written['ux-toolkit-shadcn']['path']);
    }

    public function testReusesTheMergedEntriesUntilTheyAreWritten(): void
    {
        $reader = $this->createReader(['ux-toolkit-shadcn' => '/project/var/ux_toolkit/preview/ux-toolkit-shadcn.js']);
        $entries = $reader->getEntries();
        $this->assertSame($entries, $reader->getEntries());

        $entries->add(ImportMapEntry::createLocal('other', ImportMapType::JS, './assets/other.js', false));
        $reader->writeEntries($entries);

        $this->assertNotSame($entries, $reader->getEntries());
        $this->assertTrue($reader->getEntries()->has('other'));
        $this->assertTrue($reader->getEntries()->has('ux-toolkit-shadcn'));
    }

    public function testDelegatesPathConversionsToTheDecoratedReader(): void
    {
        $importMapPath = $this->workDir.'/importmap.php';
        $storage = new RemotePackageStorage($this->workDir.'/assets/vendor');
        $inner = new class($importMapPath, $storage) extends ImportMapConfigReader {
            public function convertPathToFilesystemPath(string $path): string
            {
                return '/from/inner/'.$path;
            }
        };

        $reader = new PreviewImportMapConfigReader($inner, [], $importMapPath, $storage);

        $this->assertSame('/from/inner/./assets/app.js', $reader->convertPathToFilesystemPath('./assets/app.js'));
    }

    /**
     * @param array<string, string> $entrypoints
     */
    private function createReader(array $entrypoints): PreviewImportMapConfigReader
    {
        $importMapPath = $this->workDir.'/importmap.php';
        $storage = new RemotePackageStorage($this->workDir.'/assets/vendor');

        return new PreviewImportMapConfigReader(new ImportMapConfigReader($importMapPath, $storage), $entrypoints, $importMapPath, $storage);
    }
}
