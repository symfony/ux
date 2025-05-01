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
 * Represents the output after an installation.
 *
 * @internal
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class InstallationReport
{
    /**
     * @param array<File>                 $newFiles
     * @param array<PhpPackageDependency> $suggestedPhpPackages
     */
    public function __construct(
        public readonly array $newFiles,
        public readonly array $suggestedPhpPackages,
    ) {
    }
}
