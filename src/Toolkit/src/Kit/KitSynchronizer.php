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
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\Toolkit\Asset\StimulusController;
use Symfony\UX\Toolkit\Dependency\ComponentDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\Dependency\StimulusControllerDependency;
use Symfony\UX\Toolkit\Dependency\Version;
use Symfony\UX\Toolkit\File\Doc;
use Symfony\UX\Toolkit\File\File;
use Symfony\UX\Toolkit\File\FileType;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class KitSynchronizer
{
    /**
     * @see https://regex101.com/r/WasRGf/1
     */
    private const RE_TWIG_COMPONENT_REFERENCES = '/<twig:(?P<componentName>[a-zA-Z0-9:_-]+)/';

    /**
     * @see https://regex101.com/r/inIBID/1
     */
    private const RE_STIMULUS_CONTROLLER_REFERENCES = '/data-controller=(["\'])(?P<controllersName>.+?)\1/';

    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function synchronize(Kit $kit): void
    {
        $this->synchronizeComponents($kit);
        $this->synchronizeStimulusControllers($kit);
        $this->synchronizeDocumentation($kit);
    }

    private function synchronizeComponents(Kit $kit): void
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

        foreach ($kit->getComponents() as $component) {
            $this->resolveComponentDependencies($kit, $component);
        }
    }

    private function resolveComponentDependencies(Kit $kit, Component $component): void
    {
        // Find dependencies based on component name
        foreach ($kit->getComponents() as $otherComponent) {
            if ($component->name === $otherComponent->name) {
                continue;
            }

            // Find components with the component name as a prefix
            if (str_starts_with($otherComponent->name, $component->name.':')) {
                $component->addDependency(new ComponentDependency($otherComponent->name));
            }
        }

        // Find dependencies based on file content
        foreach ($component->files as $file) {
            if (!$this->filesystem->exists($filePath = Path::join($kit->path, $file->relativePathNameToKit))) {
                throw new \RuntimeException(\sprintf('File "%s" not found', $filePath));
            }

            $fileContent = file_get_contents($filePath);

            if (FileType::Twig === $file->type) {
                if (str_contains($fileContent, 'html_cva')) {
                    $component->addDependency(new PhpPackageDependency('twig/extra-bundle'));
                    $component->addDependency(new PhpPackageDependency('twig/html-extra', new Version('3.12.0')));
                }

                if (str_contains($fileContent, 'tailwind_merge')) {
                    $component->addDependency(new PhpPackageDependency('tales-from-a-dev/twig-tailwind-extra'));
                }

                if (str_contains($fileContent, '<twig:') && preg_match_all(self::RE_TWIG_COMPONENT_REFERENCES, $fileContent, $matches)) {
                    foreach ($matches[1] as $componentReferenceName) {
                        if ($componentReferenceName === $component->name) {
                            continue;
                        }

                        if ('ux:icon' === strtolower($componentReferenceName)) {
                            $component->addDependency(new PhpPackageDependency('symfony/ux-icons'));
                        } elseif ('ux:map' === strtolower($componentReferenceName)) {
                            $component->addDependency(new PhpPackageDependency('symfony/ux-map'));
                        } elseif (null === $componentReference = $kit->getComponent($componentReferenceName)) {
                            throw new \RuntimeException(\sprintf('Component "%s" not found in component "%s" (file "%s")', $componentReferenceName, $component->name, $file->relativePathNameToKit));
                        } else {
                            $component->addDependency(new ComponentDependency($componentReference->name));
                        }
                    }
                }

                if (str_contains($fileContent, 'data-controller=') && preg_match_all(self::RE_STIMULUS_CONTROLLER_REFERENCES, $fileContent, $matches)) {
                    $controllersName = array_filter(array_map(fn (string $name) => trim($name), explode(' ', $matches['controllersName'][0])));
                    foreach ($controllersName as $controllerReferenceName) {
                        $component->addDependency(new StimulusControllerDependency($controllerReferenceName));
                    }
                }
            }
        }
    }

    private function synchronizeStimulusControllers(Kit $kit): void
    {
        $controllersPath = Path::join('assets', 'controllers');
        $finder = (new Finder())
            ->in($kit->path)
            ->files()
            ->path($controllersPath)
            ->sortByName()
            ->name('*.js')
        ;

        foreach ($finder as $file) {
            $relativePathNameToKit = $file->getRelativePathname();
            $relativePathName = str_replace($controllersPath.\DIRECTORY_SEPARATOR, '', $relativePathNameToKit);
            $controllerName = $this->extractStimulusControllerName($relativePathName);
            $controller = new StimulusController(
                name: $controllerName,
                files: [new File(
                    type: FileType::StimulusController,
                    relativePathNameToKit: $relativePathNameToKit,
                    relativePathName: $relativePathName,
                )],
            );

            $kit->addStimulusController($controller);
        }
    }

    private function synchronizeDocumentation(Kit $kit): void
    {
        // Read INSTALL.md if exists
        $fileInstall = Path::join($kit->path, 'INSTALL.md');
        if ($this->filesystem->exists($fileInstall)) {
            $kit->installAsMarkdown = file_get_contents($fileInstall);
        }

        // Iterate over Component and find their documentation
        foreach ($kit->getComponents() as $component) {
            $docPath = Path::join($kit->path, 'docs', 'components', $component->name.'.md');
            if ($this->filesystem->exists($docPath)) {
                $component->doc = new Doc(file_get_contents($docPath));
            }
        }
    }

    private static function extractComponentName(string $pathnameRelativeToKit): string
    {
        return str_replace(['.html.twig', '/'], ['', ':'], $pathnameRelativeToKit);
    }

    private static function extractStimulusControllerName(string $pathnameRelativeToKit): string
    {
        return str_replace(['_controller.js', '-controller.js', '/', '_'], ['', '', '--', '-'], $pathnameRelativeToKit);
    }
}
