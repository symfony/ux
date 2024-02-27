<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\AssetMapper;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\MappedAsset;
use Symfony\UX\StimulusBundle\Ux\UxPackageMetadata;

/**
 * Finds the MappedAsset for an "autoimport" string.
 */
class AutoImportLocator
{
    public function __construct(
        private ImportMapConfigReader $importMapConfigReader,
        private AssetMapperInterface $assetMapper,
    ) {
    }

    // parts of this method are duplicated & adapted from UxControllersTwigRuntime
    public function locateAutoImport(string $path, UxPackageMetadata $packageMetadata): MappedControllerAutoImport
    {
        // see if this is a mapped asset path
        if ($asset = $this->assetMapper->getAsset($path)) {
            return new MappedControllerAutoImport($asset->sourcePath, false);
        }

        $slashPosition = strpos($path, '/');
        if (false === $slashPosition) {
            throw new \LogicException(sprintf('The autoimport "%s" is not valid.', $path));
        }

        $parts = explode('/', ltrim($path, '@'));
        if (2 > \count($parts)) {
            throw new \LogicException(sprintf('The autoimport "%s" is not valid.', $path));
        }
        $package = implode('/', \array_slice($parts, 0, 2));
        $file = implode('/', \array_slice($parts, 2));

        if ($package === $packageMetadata->packageName) {
            // this is a file local to the ux package
            $filePath = $packageMetadata->packageDirectory.'/'.$file;
            if (!is_file($filePath)) {
                throw new \LogicException(sprintf('An "autoimport" in "controllers.json" refers to "%s". This path could not be found in the asset mapper and the file "%s" does not exist in the package path "%s". And so, the file cannot be loaded.', $path, $filePath, $packageMetadata->packageDirectory));
            }

            $asset = $this->assetMapper->getAssetFromSourcePath($filePath);
            if (!$asset) {
                throw new \LogicException(sprintf('An "autoimport" in "controllers.json" refers to "%s". This file was found, but the path is not in the asset mapper. And so, the file cannot be loaded. This is a misconfiguration with the bundle providing this.', $path));
            }

            return new MappedControllerAutoImport($asset->sourcePath, false);
        }

        $entry = $this->importMapConfigReader->findRootImportMapEntry($path);
        if (!$entry) {
            throw new \LogicException(sprintf('The autoimport "%s" could not be found in importmap.php. Try running "php bin/console importmap:require %s".', $path, $path));
        }

        return new MappedControllerAutoImport($path, true);
    }
}
