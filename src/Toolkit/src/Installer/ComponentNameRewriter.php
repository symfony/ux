<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Installer;

use Symfony\UX\Toolkit\Kit\Kit;

/**
 * Prefixes the references to the components of a kit, once they are installed in a
 * sub-directory of the components root (e.g. "<twig:Button>" becomes "<twig:ui:Button>").
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentNameRewriter
{
    /**
     * @var array<string, true>
     */
    private readonly array $names;

    /**
     * @param list<string> $names the names of the components to prefix
     */
    public function __construct(
        array $names,
        private readonly string $prefix,
    ) {
        $this->names = array_fill_keys($names, true);
    }

    /**
     * Prefixes the components that the recipes of the kit install through the "%COMPONENTS_DIR%" placeholder.
     */
    public static function forKit(Kit $kit, string $prefix): self
    {
        if ('' === $prefix) {
            return new self([], $prefix);
        }

        $placeholder = InstallationDirectories::COMPONENTS_DIR_PLACEHOLDER.'/';
        $names = [];
        foreach ($kit->getRecipes() as $recipe) {
            foreach ($recipe->getFiles() as $file) {
                if (str_starts_with($file->destinationRelativePathName, $placeholder) && str_ends_with($file->destinationRelativePathName, '.html.twig')) {
                    $names[] = str_replace('/', ':', substr($file->destinationRelativePathName, \strlen($placeholder), -\strlen('.html.twig')));
                }
            }
        }

        return new self($names, $prefix);
    }

    public function rewrite(string $contents): string
    {
        if ('' === $this->prefix || [] === $this->names) {
            return $contents;
        }

        // "<twig:Button" and "</twig:Dialog:Content"
        $contents = preg_replace_callback(
            '/(<\/?twig:)([\w:-]+)/',
            fn (array $matches): string => isset($this->names[$matches[2]]) ? $matches[1].$this->prefix.$matches[2] : $matches[0],
            $contents,
        );

        // "component('Button'" and "component("Dialog:Content""
        return preg_replace_callback(
            '/(\bcomponent\(\s*([\'"]))([\w:-]+)(\2)/',
            fn (array $matches): string => isset($this->names[$matches[3]]) ? $matches[1].$this->prefix.$matches[3].$matches[4] : $matches[0],
            $contents,
        );
    }
}
