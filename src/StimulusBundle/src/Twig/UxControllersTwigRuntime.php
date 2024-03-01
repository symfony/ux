<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Twig;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\AssetMapper\ImportMap\ImportMapGenerator;
use Symfony\UX\StimulusBundle\AssetMapper\ControllersMapGenerator;
use Symfony\UX\StimulusBundle\Ux\UxPackageReader;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * Returns the link tags for all autoimported CSS files in controllers.json.
 *
 * @internal
 *
 * @author Ryan Weaver <ryan@symfonycasts.com>
 */
final class UxControllersTwigRuntime implements RuntimeExtensionInterface
{
    private array $importMap;

    public function __construct(
        private ControllersMapGenerator $controllersMapGenerator,
        private AssetMapperInterface $assetMapper,
        private UxPackageReader $uxPackageReader,
        private string $projectDir,
    ) {
    }

    /**
     * Returns the CSS <link> tags for all "autoimport" entries in controllers.json.
     */
    public function renderLinkTags(): string
    {
        if (class_exists(ImportMapGenerator::class)) {
            trigger_deprecation('symfony/ux-stimulus-bundle', '2.13.0', 'Calling ux_controller_link_tags() is deprecated and does nothing with symfony/asset-mapper 6.4. The link tags are rendered automatically via the importmap() function.');

            return '';
        }

        $controllersFile = $this->controllersMapGenerator->getControllersJsonPath();
        if (!is_file($controllersFile)) {
            return '';
        }

        $data = json_decode(file_get_contents($controllersFile), true, 512, \JSON_THROW_ON_ERROR);
        $packages = $data['controllers'] ?? [];

        $links = [];
        foreach ($packages as $uxPackageName => $controllers) {
            foreach ($controllers as $controllerData) {
                if (!$controllerData['enabled'] ?? false) {
                    continue;
                }

                foreach ($controllerData['autoimport'] ?? [] as $autoImport => $enabled) {
                    if ($enabled) {
                        $links[] = sprintf('<link rel="stylesheet" href="%s">', $this->getLinkHref($autoImport, $uxPackageName));
                    }
                }
            }
        }

        return implode("\n", $links);
    }

    // duplicated & adapted in ControllersMapGenerator
    private function getLinkHref(string $autoImport, string $uxPackageName): string
    {
        // see if this is a mapped asset path
        $asset = $this->assetMapper->getAsset($autoImport);
        if ($asset) {
            return $asset->publicPath;
        }

        $slashPosition = strpos($autoImport, '/');
        if (false === $slashPosition) {
            throw new \LogicException(sprintf('The autoimport "%s" is not valid.', $autoImport));
        }

        // if the first character is @, then the package name is @symfony/ux-cropperjs
        $parts = explode('/', $autoImport);
        if (str_starts_with($autoImport, '@')) {
            $package = implode('/', \array_slice($parts, 0, 2));
            $file = implode('/', \array_slice($parts, 2));
        } else {
            $package = $parts[0];
            $file = implode('/', \array_slice($parts, 1));
        }

        if ($package === $uxPackageName) {
            // this is a file local to the ux package
            $uxPackageMetadata = $this->uxPackageReader->readPackageMetadata($uxPackageName);
            $filePath = $uxPackageMetadata->packageDirectory.'/'.$file;
            if (!is_file($filePath)) {
                throw new \LogicException(sprintf('An "autoimport" in "%s" refers to "%s". This path could not be found in the asset mapper and the file "%s" does not exist in the package path "%s". And so, the file cannot be loaded.', $this->shortControllersPath(), $autoImport, $file, $uxPackageMetadata->packageDirectory));
            }

            $asset = $this->assetMapper->getAssetFromSourcePath($filePath);
            if (!$asset) {
                throw new \LogicException(sprintf('An "autoimport" in "%s" refers to "%s". This file was found, but the path is not in the asset mapper. And so, the file cannot be loaded.', $this->shortControllersPath(), $autoImport));
            }

            return $asset->publicPath;
        }

        $importMap = $this->readImportMap();
        if (!isset($importMap[$package])) {
            throw new \LogicException(sprintf('An "autoimport" in "%s" refers to "%s". This path could not be found in the asset mapper and no "%s" entry was found in importmap.php. And so, the file cannot be loaded.', $this->shortControllersPath(), $autoImport, $package));
        }

        $importMapEntry = $importMap[$package];
        if (!isset($importMapEntry['url'])) {
            throw new \LogicException(sprintf('An "autoimport" in "%s" refers to "%s". This path could not be found in the asset mapper and no "url" key was found in importmap.php for the package "%s". And so, the file cannot be loaded.', $this->shortControllersPath(), $autoImport, $package));
        }

        $version = $this->parseVersionFromUrl($importMapEntry['url']);

        return $this->getJsDelivrUrl($package, $version, $file);
    }

    private function readImportMap(): array
    {
        if (!isset($this->importMap)) {
            // this should be dynamic, but for now, we'll hardcode it
            $path = $this->projectDir.'/importmap.php';
            $this->importMap = is_file($path) ? (static fn () => include $path)() : [];
        }

        return $this->importMap;
    }

    private function parseVersionFromUrl(string $url): ?string
    {
        $versionPattern = '/(?<=@)\d+(?:\.\d+)+/';
        if (!preg_match($versionPattern, $url, $matches)) {
            return null;
        }

        return $matches[0];
    }

    private function getJsDelivrUrl(string $package, ?string $version, string $file): string
    {
        $version = $version ?? 'latest';
        $package = str_replace('@', '', $package);

        return sprintf('https://cdn.jsdelivr.net/npm/%s@%s/%s', $package, $version, $file);
    }

    private function shortControllersPath(): string
    {
        $path = $this->controllersMapGenerator->getControllersJsonPath();
        $path = realpath($path);
        $projectDir = realpath($this->projectDir);
        if (!str_starts_with($path, $projectDir)) {
            return $path;
        }

        return str_replace($projectDir, '', $path);
    }
}
