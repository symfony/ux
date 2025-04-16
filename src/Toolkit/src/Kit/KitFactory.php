<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\Dependency\DependenciesResolver;
use Symfony\UX\Toolkit\File\Doc;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class KitFactory
{
    public function __construct(
        private Filesystem $filesystem,
        private DependenciesResolver $dependencyResolver,
    ) {
    }

    /**
     * @throws \InvalidArgumentException if the manifest file is missing a required key
     * @throws \JsonException            if the manifest file is not valid JSON
     */
    public function createKitFromAbsolutePath(string $absolutePath): Kit
    {
        if (!Path::isAbsolute($absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Path "%s" is not absolute.', $absolutePath));
        }

        if (!$this->filesystem->exists($absolutePath)) {
            throw new \InvalidArgumentException(\sprintf('Path "%s" does not exist.', $absolutePath));
        }

        if (!$this->filesystem->exists($manifestPath = Path::join($absolutePath, 'manifest.json'))) {
            throw new \InvalidArgumentException(\sprintf('File "%s" not found.', $manifestPath));
        }

        $manifest = json_decode($this->filesystem->readFile($manifestPath), true, flags: \JSON_THROW_ON_ERROR);

        $kit = new Kit(
            path: $absolutePath,
            name: $manifest['name'] ?? throw new \InvalidArgumentException('Manifest file is missing "name" key.'),
            homepage: $manifest['homepage'] ?? throw new \InvalidArgumentException('Manifest file is missing "homepage" key.'),
            authors: $manifest['authors'] ?? throw new \InvalidArgumentException('Manifest file is missing "authors" key.'),
            license: $manifest['license'] ?? throw new \InvalidArgumentException('Manifest file is missing "license" key.'),
            description: $manifest['description'] ?? null,
            uxIcon: $manifest['ux-icon'] ?? null,
        );

        $this->synchronizeKit($kit);

        return $kit;
    }

    private function synchronizeKit(Kit $kit): void
    {
        $this->synchronizeKitComponents($kit);
        $this->synchronizeKitDocumentation($kit);
    }

    private function synchronizeKitComponents(Kit $kit): void
    {
        $componentsPath = Path::join('templates', 'components');
        $finder = (new Finder())
            ->in($kit->path)
            ->files()
            ->path($componentsPath)
            ->sortByName()
            ->name('*.html.twig')
        ;

        foreach ($finder as $file) {
            $relativePathNameToKit = $file->getRelativePathname();
            $relativePathName = str_replace($componentsPath.\DIRECTORY_SEPARATOR, '', $relativePathNameToKit);
            $componentName = $this->extractComponentName($relativePathName);
            $component = new Component(
                name: $componentName,
                files: [new File(
                    type: FileType::Twig,
                    relativePathNameToKit: $relativePathNameToKit,
                    relativePathName: $relativePathName,
                )],
            );

            $kit->addComponent($component);
        }

        $this->dependencyResolver->resolveDependencies($kit);
    }

    private static function extractComponentName(string $pathnameRelativeToKit): string
    {
        return str_replace(['.html.twig', '/'], ['', ':'], $pathnameRelativeToKit);
    }

    private function synchronizeKitDocumentation(Kit $kit): void
    {
        // Read INSTALL.md if exists
        $fileInstall = Path::join($kit->path, 'INSTALL.md');
        if ($this->filesystem->exists($fileInstall)) {
            $kit->installAsMarkdown = $this->filesystem->readFile($fileInstall);
        }

        // Iterate over Component and find their documentation
        foreach ($kit->getComponents() as $component) {
            $docPath = Path::join($kit->path, 'docs', 'components', $component->name.'.md');
            if ($this->filesystem->exists($docPath)) {
                $component->doc = new Doc($this->filesystem->readFile($docPath));
            }
        }
    }
}
