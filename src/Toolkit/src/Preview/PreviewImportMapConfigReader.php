<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Preview;

use Symfony\Component\AssetMapper\ImportMap\ImportMapConfigReader;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntries;
use Symfony\Component\AssetMapper\ImportMap\ImportMapEntry;
use Symfony\Component\AssetMapper\ImportMap\ImportMapType;
use Symfony\Component\AssetMapper\ImportMap\RemotePackageStorage;

/**
 * Adds one entrypoint per previewed kit to the importmap, without ever writing them to importmap.php.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class PreviewImportMapConfigReader extends ImportMapConfigReader
{
    private ?ImportMapEntries $entries = null;

    /**
     * @param array<string, string> $entrypoints import name => absolute path of the generated JavaScript file
     */
    public function __construct(
        private readonly ImportMapConfigReader $inner,
        private readonly array $entrypoints,
        string $importMapConfigPath,
        RemotePackageStorage $remotePackageStorage,
    ) {
        parent::__construct($importMapConfigPath, $remotePackageStorage);
    }

    public function getEntries(): ImportMapEntries
    {
        if (null !== $this->entries) {
            return $this->entries;
        }

        $this->entries = new ImportMapEntries(iterator_to_array($this->inner->getEntries()));
        foreach ($this->entrypoints as $importName => $path) {
            if (!$this->entries->has($importName)) {
                $this->entries->add(ImportMapEntry::createLocal($importName, ImportMapType::JS, $path, true));
            }
        }

        return $this->entries;
    }

    public function writeEntries(ImportMapEntries $entries): void
    {
        $hostEntries = new ImportMapEntries();
        foreach ($entries as $entry) {
            if (($this->entrypoints[$entry->importName] ?? null) !== $entry->path) {
                $hostEntries->add($entry);
            }
        }

        $this->entries = null;
        $this->inner->writeEntries($hostEntries);
    }

    public function createRemoteEntry(string $importName, ImportMapType $type, string $version, string $packageModuleSpecifier, bool $isEntrypoint): ImportMapEntry
    {
        return $this->inner->createRemoteEntry($importName, $type, $version, $packageModuleSpecifier, $isEntrypoint);
    }

    public function convertPathToFilesystemPath(string $path): string
    {
        return $this->inner->convertPathToFilesystemPath($path);
    }

    public function convertFilesystemPathToPath(string $filesystemPath): ?string
    {
        return $this->inner->convertFilesystemPathToPath($filesystemPath);
    }
}
