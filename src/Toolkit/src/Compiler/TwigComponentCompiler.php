<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Compiler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Compiler\Exception\TwigComponentAlreadyExist;
use Symfony\UX\Toolkit\Registry\DependenciesResolver;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryItem;
use Symfony\UX\Toolkit\Registry\RegistryItemType;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
final readonly class TwigComponentCompiler
{
    public function __construct(
        private ?string $prefix,
        private DependenciesResolver $dependenciesResolver,
        private Filesystem $filesystem,
    ) {
    }

    public function compile(
        Registry $registry,
        RegistryItem $item,
        string $directory,
        bool $overwrite = false,
    ): void {
        // resolve dependencies (avoid circular dependencies)
        $this->dependenciesResolver->resolve($registry);

        if (!$this->filesystem->exists($directory)) {
            $this->filesystem->mkdir($directory);
        }

        // We need to install all children components of each parent component
        $componentsToInstall = [];
        foreach($item->getParents() as $parentName) {
            // Children of the parent component
            $componentsToInstall = array_merge($componentsToInstall, $registry->get($parentName)->getChildren());

            // And the parent itself
            $componentsToInstall = array_merge([$parentName], $componentsToInstall);
        }

        // and the component itself
        $componentsToInstall = array_merge([$item->name], $componentsToInstall);

        foreach ($componentsToInstall as $componentName) {
            $this->installComponent($registry->get($componentName), $directory, $overwrite);
        }
    }

    private function installComponent(RegistryItem $item, string $directory, bool $overwrite): void
    {
        if (RegistryItemType::Component !== $item->type) {
            return;
        }

        $filename = implode(\DIRECTORY_SEPARATOR, [
            $directory,
            $this->prefix,
            $item->name.'.html.twig',
        ]);

        if ($this->filesystem->exists($filename) && !$overwrite) {
            throw new TwigComponentAlreadyExist("The component '{$item->name}' already exists.", 0, null);
        }

        $this->filesystem->dumpFile($filename, $item->code);
    }
}
