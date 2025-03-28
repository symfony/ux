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

namespace Symfony\UX\Toolkit\Registry;

use Symfony\Component\Finder\Finder;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
final readonly class RegistryFactory
{
    public function create(Finder $finder): Registry
    {
        $finderManifest = clone $finder;
        $files = $finderManifest->files()->name('registry.json')->getIterator();
        $files->rewind();
        $manifestFile = $files->current();
        if (!$manifestFile) {
            throw new \RuntimeException('The manifest file is missing.');
        }

        $registry = Registry::empty();
        $manifest = json_decode($manifestFile->getContents(), true);

        foreach ($manifest['items'] ?? [] as $item) {
            $filename = $item['manifest'];
            $localFinder = clone $finder;
            $files = iterator_to_array($localFinder->path($item['manifest']));

            if (1 !== \count($files)) {
                throw new \RuntimeException(\sprintf('The file "%s" declared in the manifest is missing.', $filename));
            }
            $file = reset($files);

            if (!isset($item['fingerprint']) && isset($item['code'])) {
                throw new \RuntimeException(\sprintf('The file "%s" declared in the manifest must have a fingerprint.', $filename));
            }

            $itemObject = RegistryItemFactory::fromJsonFile($file);

            if (isset($item['fingerprint'])) {
                $hash = md5($itemObject->code);
                if ($hash !== $item['fingerprint']) {
                    throw new \RuntimeException(\sprintf('The file "%s" declared in the manifest has an invalid hash.', $filename));
                }
            }

            $registry->add($itemObject);
        }

        return $registry;
    }
}
