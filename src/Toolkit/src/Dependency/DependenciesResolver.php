<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Dependency;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Component\Component;
use Symfony\UX\Toolkit\File\FileType;
use Symfony\UX\Toolkit\Kit\Kit;

/**
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final readonly class DependenciesResolver
{
    /**
     * @see https://regex101.com/r/WasRGf/1
     */
    private const RE_TWIG_COMPONENT_REFERENCES = '/<twig:(?P<componentName>[a-zA-Z0-9:_-]+)/';

    public function __construct(
        private Filesystem $filesystem,
    ) {
    }

    public function resolveDependencies(Kit $kit): void
    {
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
            $fileContent = $this->filesystem->readFile(Path::join($kit->path, $file->relativePathNameToKit));

            if (FileType::Twig === $file->type) {
                if (str_contains($fileContent, 'html_cva')) {
                    $component->addDependency(new PhpPackageDependency('twig/extra-bundle'));
                    $component->addDependency(new PhpPackageDependency('twig/html-extra', new Version(3, 12, 0)));
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
            }
        }
    }
}
