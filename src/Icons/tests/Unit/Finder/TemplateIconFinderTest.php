<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons\Tests\Unit\Finder;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Icons\Finder\TemplateIconFinder;
use Twig\Environment;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

final class TemplateIconFinderTest extends TestCase
{
    private string $templateDir;
    private string $iconDir;

    protected function setUp(): void
    {
        $root = sys_get_temp_dir().'/ux-icons-template-finder-'.bin2hex(random_bytes(6));
        $this->templateDir = $root.'/templates';
        $this->iconDir = $root.'/icons';

        $filesystem = new Filesystem();
        $filesystem->dumpFile($this->templateDir.'/page.html.twig', '{{ ux_icon(\'lucide:star\') }}<twig:ux:icon name="lucide:user" />');
        $filesystem->dumpFile($this->templateDir.'/bundles/AcmeBundle/override.html.twig', '{{ ux_icon("tabler:bell") }}');
        $filesystem->dumpFile($this->iconDir.'/logo.svg', '<svg viewBox="0 0 24 24"><path d="M0 0"/></svg>');
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove(\dirname($this->templateDir));
    }

    public function testFindsIconNamesAndPrefixLessLocalIcons(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath($this->templateDir);

        $icons = $this->finder($loader)->icons();

        $this->assertEqualsCanonicalizing(['lucide:star', 'lucide:user', 'tabler:bell', 'logo'], $icons);
    }

    public function testADirectoryRegisteredSeveralTimesIsScannedOnce(): void
    {
        $once = new FilesystemLoader();
        $once->addPath($this->templateDir);

        // What TwigBundle does with a bundle views directory: the same path under several namespaces
        $several = new FilesystemLoader();
        $several->addPath($this->templateDir);
        $several->addPath($this->templateDir, 'Acme');
        $several->addPath($this->templateDir, '!Acme');

        $this->assertSame(
            $this->finder($once)->icons(),
            $this->finder($several)->icons(),
        );
        $this->assertSame([$this->templateDir], $this->scannedPaths($several));
    }

    public function testATemplateNestedInTwoRegisteredDirectoriesIsScannedOnce(): void
    {
        $loader = new FilesystemLoader();
        $loader->addPath($this->templateDir);
        $loader->addPath($this->templateDir.'/bundles/AcmeBundle', 'Acme');

        $finder = $this->finder($loader);
        $files = $this->scannedFiles($finder, $loader);

        $this->assertEqualsCanonicalizing(['lucide:star', 'lucide:user', 'tabler:bell', 'logo'], array_values($finder->icons()));
        // Finder cannot avoid walking the nested template twice, icons() must not read it twice
        $this->assertCount(3, $files);
        $this->assertCount(2, array_unique($files));
    }

    public function testChainLoaderDoesNotScanTheSameTemplateTwice(): void
    {
        $first = new FilesystemLoader();
        $first->addPath($this->templateDir);
        $second = new FilesystemLoader();
        $second->addPath($this->templateDir, 'Acme');

        $finder = $this->finder($chain = new ChainLoader([$first, $second]));
        $files = $this->scannedFiles($finder, $chain);

        $this->assertEqualsCanonicalizing(['lucide:star', 'lucide:user', 'tabler:bell', 'logo'], array_values($finder->icons()));
        $this->assertCount(4, $files);
        $this->assertCount(2, array_unique($files));
    }

    private function finder(FilesystemLoader|ChainLoader $loader): TemplateIconFinder
    {
        return new TemplateIconFinder(new Environment($loader), $this->iconDir);
    }

    /**
     * The distinct directories the loader contributes, as the finder computes them.
     *
     * @return list<string>
     */
    private function scannedPaths(FilesystemLoader $loader): array
    {
        $paths = [];
        foreach ([FilesystemLoader::MAIN_NAMESPACE, ...$loader->getNamespaces()] as $namespace) {
            foreach ($loader->getPaths($namespace) as $path) {
                $paths[$path] = $path;
            }
        }

        return array_values($paths);
    }

    /**
     * The template files the finder walks, duplicates included.
     *
     * @return list<string>
     */
    private function scannedFiles(TemplateIconFinder $finder, FilesystemLoader|ChainLoader $loader): array
    {
        $templateFiles = new \ReflectionMethod($finder, 'templateFiles');

        return iterator_to_array($templateFiles->invoke($finder, $loader), false);
    }
}
