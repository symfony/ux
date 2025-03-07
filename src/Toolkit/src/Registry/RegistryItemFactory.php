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

use Symfony\Component\Finder\SplFileInfo;

/**
 * @internal
 */
final readonly class RegistryItemFactory
{
    /**
     * https://regex101.com/r/8NcORd/1.
     */
    private const REGEX_RELATIVE_FILE = '#^(?P<theme>default|new-york)/(?P<type>component|example)s/(?P<nameOrParentName>[A-Z][a-zA-Z]*)(?:/(?P<name>[A-Z][a-zA-Z]*))?\.html\.twig$#';

    public static function fromJsonFile(SplFileInfo $file): RegistryItem
    {
        $json = json_decode($file->getContents(), true);
        if (null === $json) {
            throw new \RuntimeException(\sprintf('The file "%s" is not a valid JSON file.', $file->getRelativePathname()));
        }

        if (!isset($json['name'], $json['type'], $json['theme'], $json['code'])) {
            throw new \RuntimeException(\sprintf('The file "%s" must contain the following keys: "name", "type", "theme" and "code".', $file->getRelativePathname()));
        }

        return new RegistryItem(
            $json['name'],
            RegistryItemType::from($json['type']),
            $json['theme'],
            $json['parentName'] ?? null,
            $json['code'],
            $json['children'] ?? [],
        );
    }

    public static function fromTwigFile(SplFileInfo $file): RegistryItem
    {
        if (!preg_match(self::REGEX_RELATIVE_FILE, $file->getRelativePathname(), $matches)) {
            throw new \InvalidArgumentException(\sprintf('Unable to parse file path "%s", it must match the following pattern: "<default|new-york>/<components|examples>/(<parent>/)?<name>.html.twig"', $file->getRelativePathname()));
        }

        $name = $matches['name'] ?? $matches['nameOrParentName'];
        $parentName = $matches['nameOrParentName'] ?? null;
        if ($name === $parentName) {
            $parentName = null;
        }

        // @todo: we should improve the way we detect examples, or document it
        $isExample = preg_match('#examples#', $file->getRelativePathname());
        $type = $isExample ? RegistryItemType::Example : RegistryItemType::Component;
        
        // @todo; we should discuss if theme is relevant:
        // today the theming is managed by the Registry itself, we could image removing all theming notion from the RegistryItem
        $theme = ''; // $matches['theme'] ?? null;

        return new RegistryItem(
            $name,
            $type,
            $theme,
            $parentName,
            $file->getContents(),
        );
    }
}
