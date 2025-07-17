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

use Symfony\UX\Toolkit\Dependency\PhpPackageDependency;
use Symfony\UX\Toolkit\File\File;

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
     * @param array<non-empty-string, PhpPackageDependency> $files
     */
    private array $phpPackageDependencies = [];

    public function addFile(File $file): void
    {
        $this->files[$file->relativePathName] ??= $file;
    }

    /**
     * @return array<non-empty-string, File>
     */
    public function getFiles(): array
    {
        return $this->files;
    }

    public function addPhpPackageDependency(PhpPackageDependency $dependency): void
    {
        if (isset($this->phpPackageDependencies[$dependency->name]) && $dependency->isHigherThan($this->phpPackageDependencies[$dependency->name])) {
            $this->phpPackageDependencies[$dependency->name] = $dependency;

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
}
