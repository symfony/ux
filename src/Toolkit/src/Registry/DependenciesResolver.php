<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Registry;

/**
 * @author Jean-François Lépine
 *
 * @internal
 */
class DependenciesResolver
{
    public function resolve(Registry $registry): array
    {
        $resolved = [];
        $unresolved = [];

        $concernedComponents = [];
        foreach ($registry->all() as $item) {
            if (RegistryItemType::Component !== $item->type) {
                continue;
            }

            $concernedComponents[] = $item->name;
        }

        foreach ($concernedComponents as $itemName) {
            [$resolved, $unresolved] = $this->resolveDependency($registry, $itemName, $resolved, $unresolved);
        }

        $sorted = [];
        foreach ($resolved as $itemName) {
            $sorted[] = $registry->get($itemName);
        }

        return $sorted;
    }

    private function resolveDependency(Registry $registry, string $itemName, array $resolved, array $unresolved)
    {
        $unresolved[] = $itemName;

        foreach ($registry->get($itemName)->getParents() as $dep) {
            if (!\in_array($dep, $resolved)) {
                if (!\in_array($dep, $unresolved)) {
                    $unresolved[] = $dep;
                    [$resolved, $unresolved] = $this->resolveDependency($registry, $dep, $resolved, $unresolved);
                } else {
                    throw new \RuntimeException("Circular dependency detected: $itemName -> $dep.");
                }
            }
        }
        if (!\in_array($itemName, $resolved)) {
            $resolved[] = $itemName;
        }

        while (($index = array_search($itemName, $unresolved)) !== false) {
            unset($unresolved[$index]);
        }

        return [$resolved, $unresolved];
    }
}
