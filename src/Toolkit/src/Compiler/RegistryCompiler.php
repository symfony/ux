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
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Registry\Registry;
use Symfony\UX\Toolkit\Registry\RegistryItem;
use Symfony\UX\Toolkit\Registry\RegistryItemType;

/**
 * @author Jean-François Lépine
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class RegistryCompiler
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
    }

    public function compile(Registry $registry, string $registryDir): void
    {
        $this->filesystem->mkdir($registryDir);

        $registryJson = [
            'name' => $registry->getName(),
            'licenses' => $registry->getLicenses(),
            'authors' => $registry->getAuthors(),
            'homepage' => $registry->getHomepage(),
            'items' => [],
        ];
        $registryJson = array_filter($registryJson);

        foreach ($registry->all() as $item) {
            $itemPath = Path::join($registryDir, $item->theme, $item->type->value.'s', $item->parentName ?: '', $item->name.'.json');

            $itemJson = [
                'name' => $item->name,
                'manifest' => Path::makeRelative($itemPath, $registryDir),
                'theme' => $item->theme,
                'type' => $item->type->value,
                'code' => $item->code,
                'fingerprint' => md5($item->code),
                'dependencies' => $this->getDependencies($registry, $item),
            ];

            if ($item->type && !empty($item->parentName)) {
                $itemJson['parentName'] = $item->parentName;
            }

            $this->filesystem->dumpFile($itemPath, json_encode($itemJson, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");

            unset($itemJson['code']);
            unset($itemJson['fingerprint']);
            $registryJson['items'][] = $itemJson;
        }

        $registryPath = Path::join($registryDir, 'registry.json');
        $this->filesystem->dumpFile($registryPath, json_encode($registryJson, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES)."\n");
    }

    private function getDependencies(Registry $registry, RegistryItem $component): array
    {
        if (RegistryItemType::Component !== $component->type) {
            return [];
        }

        $dependencies = [];

        foreach ($registry->all() as $item) {
            if ($item->theme !== $component->theme || $item->type !== $component->type) {
                continue;
            }

            if ($item->parentName === $component->name) {
                $dependencies[] = $item->name;
            }

            if (str_contains($component->code, '<twig:'.$item->name)) {
                $dependencies[] = $item->name;
            }
        }

        return $dependencies;
    }
}
