<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Installer;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Installer\InstallationDirectories;
use Symfony\UX\Toolkit\Installer\Installer;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

final class SubDirectoryInstallationTest extends KernelTestCase
{
    use TestHelperTrait;

    private const EXTERNAL_CONTROLLERS = ['csrf-protection'];

    private Filesystem $filesystem;
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootKernel();
        $this->filesystem = self::getContainer()->get('filesystem');
        $this->tmpDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_test_');
        $this->filesystem->remove($this->tmpDir);
        $this->filesystem->mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tmpDir);

        parent::tearDown();
    }

    /**
     * @return iterable<array{string}>
     */
    public static function provideKits(): iterable
    {
        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            yield $kitName => [$kitName];
        }
    }

    #[DataProvider('provideKits')]
    public function testEveryReferenceResolvesOnceTheKitIsInstalledInSubDirectories(string $kitName): void
    {
        $kit = self::getContainer()->get('ux_toolkit.kit.kit_factory')->createKitFromAbsolutePath(self::getLocalKitPath($kitName));
        $directories = new InstallationDirectories('templates/components', 'ui', 'assets/controllers', 'ui');
        $installer = new Installer($this->filesystem, static fn () => true, $directories);

        foreach ($kit->getRecipes() as $recipe) {
            $installer->installRecipe($kit, $recipe, $this->tmpDir, true);
        }

        $components = $this->findInstalledComponents();
        $controllers = $this->findInstalledControllers();
        $this->assertNotSame([], $components);

        $unresolved = [];
        foreach (new Finder()->in($this->tmpDir)->files()->name(['*.twig', '*.js']) as $file) {
            $contents = $file->getContents();

            preg_match_all('/<\/?twig:([\w:-]+)/', $contents, $matches);
            foreach ($matches[1] as $name) {
                if (!str_starts_with($name, 'ux:') && !\in_array($name, $components, true)) {
                    $unresolved[] = \sprintf('%s: component "%s"', $file->getRelativePathname(), $name);
                }
            }

            foreach ($this->findReferencedControllers($contents) as $reference => $identifier) {
                if (!\in_array($identifier, $controllers, true) && !\in_array($identifier, self::EXTERNAL_CONTROLLERS, true)) {
                    $unresolved[] = \sprintf('%s: controller "%s" in %s', $file->getRelativePathname(), $identifier, $reference);
                }
            }
        }

        $this->assertSame([], array_values(array_unique($unresolved)));
    }

    /**
     * @return list<string>
     */
    private function findInstalledComponents(): array
    {
        $components = [];
        foreach (new Finder()->in($this->tmpDir.'/templates/components')->files()->name('*.html.twig') as $file) {
            $path = Path::normalize($file->getRelativePathname());
            $components[] = str_replace('/', ':', substr($path, 0, -\strlen('.html.twig')));
        }

        return $components;
    }

    /**
     * The identifiers StimulusBundle registers for the controllers found in "assets/controllers".
     *
     * @return list<string>
     */
    private function findInstalledControllers(): array
    {
        if (!is_dir($this->tmpDir.'/assets/controllers')) {
            return [];
        }

        $controllers = [];
        foreach (new Finder()->in($this->tmpDir.'/assets/controllers')->files()->name('*_controller.js') as $file) {
            $path = Path::normalize($file->getRelativePathname());
            $controllers[] = str_replace(['_', '/'], ['-', '--'], substr($path, 0, -\strlen('_controller.js')));
        }

        return $controllers;
    }

    /**
     * @return iterable<string, string> where the identifier is referenced => the identifier
     */
    private function findReferencedControllers(string $contents): iterable
    {
        preg_match_all('/data-controller[\'"]?\s*(?:~=|=|:|,)\s*([\'"])(.*?)\1/s', $contents, $matches);
        foreach ($matches[2] as $value) {
            foreach (preg_split('/\s+/', trim($value)) as $identifier) {
                if (preg_match('/^[a-z][a-z0-9-]*$/', $identifier)) {
                    yield 'data-controller "'.$value.'"' => $identifier;
                }
            }
        }

        preg_match_all('/data-action[\'"]?\s*(?:=|:|,)\s*([\'"])(.*?)\1/s', $contents, $matches);
        foreach ($matches[2] as $value) {
            preg_match_all('/(?:^|\s|->)([a-z][a-z0-9-]*)#/', $value, $descriptors);
            foreach ($descriptors[1] as $identifier) {
                yield 'data-action "'.$value.'"' => $identifier;
            }
        }

        // "data-bs-target" belongs to Bootstrap, not to a Stimulus controller
        preg_match_all('/(?<![\w-])data-(?!bs-)([a-z][a-z0-9-]*?)-target(?![\w-])/', $contents, $matches);
        foreach ($matches[1] as $i => $identifier) {
            yield $matches[0][$i] => $identifier;
        }

        preg_match_all('/getControllerForElementAndIdentifier\([^,()]*,\s*([\'"])([\w-]+)\1/', $contents, $matches);
        foreach ($matches[2] as $i => $identifier) {
            yield $matches[0][$i] => $identifier;
        }
    }
}
