<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Installer;

use Symfony\UX\Toolkit\Dependency\ImportmapPackageDependency;
use Symfony\UX\Toolkit\Dependency\NpmPackageDependency;
use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\File;
use Symfony\UX\Toolkit\Recipe\Recipe;

/**
 * Represents a pool of files and dependencies to be installed.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class Pool
{
    /**
     * @var array<non-empty-string, File>
     */
    private array $files = [];

    /**
     * @var array<non-empty-string, PhpPackageDependency>
     */
    private array $phpPackageDependencies = [];

    /**
     * @var array<non-empty-string, NpmPackageDependency>
     */
    private array $npmPackageDependencies = [];

    /**
     * @var array<non-empty-string, ImportmapPackageDependency>
     */
    private array $importmapPackageDependencies = [];

    public function addFile(Recipe $recipe, File $file): void
    {
        $this->files[$recipe->absolutePath][$file->destinationRelativePathName] ??= $file;
    }

    /**
     * @return array<non-empty-string, array<non-empty-string, File>>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function addPhpPackageDependency(PhpPackageDependency $dependency): void
    {
        if (isset($this->phpPackageDependencies[$dependency->name]) && !$dependency->isHigherThan($this->phpPackageDependencies[$dependency->name])) {
            return;
        }

        $this->phpPackageDependencies[$dependency->name] = $dependency;
    }

    /**
     * @return array<non-empty-string, PhpPackageDependency>
     */
    public function getPhpPackageDependencies(): array
    {
        return $this->phpPackageDependencies;
    }

    public function addNpmPackageDependency(NpmPackageDependency $dependency): void
    {
        if (isset($this->npmPackageDependencies[$dependency->name]) && !$dependency->isHigherThan($this->npmPackageDependencies[$dependency->name])) {
            return;
        }

        $this->npmPackageDependencies[$dependency->name] = $dependency;
    }

    /**
     * @return array<non-empty-string, NpmPackageDependency>
     */
    public function getNpmPackageDependencies(): array
    {
        return $this->npmPackageDependencies;
    }

    public function addImportmapPackageDependency(ImportmapPackageDependency $dependency): void
    {
        $this->importmapPackageDependencies[$dependency->package] = $dependency;
    }

    /**
     * @return array<non-empty-string, ImportmapPackageDependency>
     */
    public function getImportmapPackageDependencies(): array
    {
        return $this->importmapPackageDependencies;
    }
}
