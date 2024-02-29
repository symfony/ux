<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Ux;

use Composer\InstalledVersions;

/**
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
class UxPackageReader
{
    public function __construct(private string $projectDir)
    {
    }

    public function readPackageMetadata(string $packageName): UxPackageMetadata
    {
        // remove the '@' from the name to get back to the PHP package name
        $phpPackageName = substr($packageName, 1);
        if (class_exists(InstalledVersions::class) && InstalledVersions::isInstalled($phpPackageName)) {
            $phpPackagePath = InstalledVersions::getInstallPath($phpPackageName);
        } else {
            $phpPackagePath = $this->projectDir.'/vendor/'.$phpPackageName;
        }

        if (!is_dir($phpPackagePath)) {
            throw new \RuntimeException(sprintf('Could not find package "%s" referred to from controllers.json.', $phpPackageName));
        }
        $packageConfigJsonPath = $phpPackagePath.'/assets/package.json';
        if (!file_exists($packageConfigJsonPath)) {
            $packageConfigJsonPath = $phpPackagePath.'/Resources/assets/package.json';
        }
        if (!file_exists($packageConfigJsonPath)) {
            throw new \RuntimeException(sprintf('Could not find package.json in the "%s" package.', $phpPackagePath));
        }

        $packageConfigJson = file_get_contents($packageConfigJsonPath);
        $packageConfigData = json_decode($packageConfigJson, true);

        return new UxPackageMetadata(
            \dirname($packageConfigJsonPath),
            $packageConfigData['symfony'] ?? [],
            $phpPackageName
        );
    }
}
