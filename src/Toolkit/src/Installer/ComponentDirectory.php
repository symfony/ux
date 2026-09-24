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

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Assert;

/**
 * The directory, relative to the installation destination, where the Twig components
 * of a recipe are installed.
 *
 * Kits ship their components under "templates/components", which is also where they are
 * installed by default. Pointing this elsewhere re-roots that prefix only: everything else
 * a recipe copies (Stimulus controllers under "assets/controllers") is left untouched.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class ComponentDirectory implements \Stringable
{
    public const DEFAULT_PATH = 'templates/components';

    public readonly string $path;

    /**
     * @throws \InvalidArgumentException if the path is empty, absolute, or escapes its target directory
     */
    public function __construct(string $path = self::DEFAULT_PATH)
    {
        if ('' === trim($path)) {
            throw new \InvalidArgumentException('The component directory must not be empty.');
        }

        if (!Path::isRelative($path)) {
            throw new \InvalidArgumentException(\sprintf('The component directory "%s" must be relative to the destination directory.', $path));
        }

        Assert::pathDoesNotEscapeDirectory($path);

        // Traversal is rejected above, so canonicalizing can only collapse "." segments here.
        $this->path = rtrim(Path::canonicalize(Path::normalize($path)), '/');
    }

    public function __toString(): string
    {
        return $this->path;
    }

    public function isDefault(): bool
    {
        return self::DEFAULT_PATH === $this->path;
    }

    /**
     * Re-root a recipe destination path onto this directory.
     *
     * Paths outside the kit convention (e.g. "assets/controllers/dialog_controller.js")
     * are returned as-is.
     */
    public function resolveDestination(string $destinationRelativePathName): string
    {
        if (!str_starts_with($destinationRelativePathName, self::DEFAULT_PATH.'/')) {
            return $destinationRelativePathName;
        }

        return Path::join($this->path, substr($destinationRelativePathName, \strlen(self::DEFAULT_PATH) + 1));
    }

    /**
     * The prefix installed components get in their Twig name.
     *
     * Component names are derived from the path below the anonymous template directory, so
     * installing into "templates/components/ui" turns "Button" into "ui:Button". Directories
     * outside "templates/components" have no computable prefix: the user registers them with
     * `twig_component.anonymous_template_directory` instead, and names stay unprefixed.
     */
    public function getComponentNamePrefix(): string
    {
        if ($this->isDefault() || !str_starts_with($this->path, self::DEFAULT_PATH.'/')) {
            return '';
        }

        return str_replace('/', ':', substr($this->path, \strlen(self::DEFAULT_PATH) + 1)).':';
    }

    /**
     * The Twig component name of a template following the kit convention
     * (ex: "templates/components/Dialog/Content.html.twig" gives "Dialog:Content"),
     * or null when the path is not such a template.
     */
    public static function componentName(string $relativePathName): ?string
    {
        if (!str_starts_with($relativePathName, self::DEFAULT_PATH.'/') || !str_ends_with($relativePathName, '.html.twig')) {
            return null;
        }

        $name = substr($relativePathName, \strlen(self::DEFAULT_PATH) + 1, -\strlen('.html.twig'));

        return '' === $name ? null : str_replace('/', ':', $name);
    }
}
