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
 * Kits ship their components under "templates/components". They are installed by default where
 * Twig looks for anonymous components, which is the same directory unless the application
 * configured another one. Pointing this elsewhere re-roots that prefix only: everything else
 * a recipe copies (Stimulus controllers under "assets/controllers") is left untouched.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class ComponentDirectory implements \Stringable
{
    /**
     * Where kits ship their Twig components.
     */
    public const KIT_PATH = 'templates/components';

    public const DEFAULT_ANONYMOUS_TEMPLATE_DIRECTORY = 'components';

    /**
     * The characters "<twig:" accepts in a component name, besides the ":" separator.
     */
    private const COMPONENT_NAME_SEGMENT_PATTERN = '#^[A-Za-z0-9_@\-.]+$#';

    public readonly string $path;
    public readonly string $anonymousTemplateDirectory;

    /**
     * @param string|null $path                       Defaults to the directory Twig looks into for anonymous components
     * @param string      $anonymousTemplateDirectory The "twig_component.anonymous_template_directory" of the application
     *
     * @throws \InvalidArgumentException if the path is invalid, or gives an invalid component name
     */
    public function __construct(?string $path = null, string $anonymousTemplateDirectory = self::DEFAULT_ANONYMOUS_TEMPLATE_DIRECTORY)
    {
        $this->anonymousTemplateDirectory = trim(Path::canonicalize($anonymousTemplateDirectory), '/');
        $path ??= $this->getAnonymousTemplatePath();

        self::validatePath($path);

        // Traversal is rejected above, so canonicalizing can only collapse "." segments here.
        $this->path = rtrim(Path::canonicalize(Path::normalize($path)), '/');

        foreach (explode('/', $this->getPathBelowAnonymousTemplateDirectory() ?? '') as $segment) {
            if ('' !== $segment && !preg_match(self::COMPONENT_NAME_SEGMENT_PATTERN, $segment)) {
                throw new \InvalidArgumentException(\sprintf('The component directory "%s" cannot be used in a component name.', $path));
            }
        }
    }

    public function __toString(): string
    {
        return $this->path;
    }

    /**
     * @throws \InvalidArgumentException if the path is empty, absolute, or escapes its target directory
     */
    public static function validatePath(string $path): void
    {
        if ('' === trim($path)) {
            throw new \InvalidArgumentException('The component directory must not be empty.');
        }

        if (!Path::isRelative($path)) {
            throw new \InvalidArgumentException(\sprintf('The component directory "%s" must be relative to the destination directory.', $path));
        }

        Assert::pathDoesNotEscapeDirectory($path);
    }

    /**
     * The directory Twig looks into for anonymous components, relative to the installation destination.
     */
    public function getAnonymousTemplatePath(): string
    {
        return 'templates/'.$this->anonymousTemplateDirectory;
    }

    public function isAnonymousTemplateDirectory(): bool
    {
        return $this->getAnonymousTemplatePath() === $this->path;
    }

    /**
     * Re-root a recipe destination path onto this directory.
     *
     * Paths outside the kit convention (e.g. "assets/controllers/dialog_controller.js")
     * are returned as-is.
     */
    public function resolveDestination(string $destinationRelativePathName): string
    {
        if (!str_starts_with($destinationRelativePathName, self::KIT_PATH.'/')) {
            return $destinationRelativePathName;
        }

        return Path::join($this->path, substr($destinationRelativePathName, \strlen(self::KIT_PATH) + 1));
    }

    /**
     * The prefix installed components get in their Twig name.
     *
     * Component names are derived from the path below the anonymous template directory, so
     * installing into "templates/components/ui" turns "Button" into "ui:Button". Directories
     * outside of it have no computable prefix: the user registers them with
     * `twig_component.anonymous_template_directory` instead, and names stay unprefixed.
     */
    public function getComponentNamePrefix(): string
    {
        if (null === $subPath = $this->getPathBelowAnonymousTemplateDirectory()) {
            return '';
        }

        return str_replace('/', ':', $subPath).':';
    }

    /**
     * The Twig component name of a template following the kit convention
     * (ex: "templates/components/Dialog/Content.html.twig" gives "Dialog:Content"),
     * or null when the path is not such a template.
     */
    public static function componentName(string $relativePathName): ?string
    {
        if (!str_starts_with($relativePathName, self::KIT_PATH.'/') || !str_ends_with($relativePathName, '.html.twig')) {
            return null;
        }

        $name = substr($relativePathName, \strlen(self::KIT_PATH) + 1, -\strlen('.html.twig'));

        return '' === $name ? null : str_replace('/', ':', $name);
    }

    private function getPathBelowAnonymousTemplateDirectory(): ?string
    {
        $anonymousTemplatePath = $this->getAnonymousTemplatePath();
        if (!str_starts_with($this->path, $anonymousTemplatePath.'/')) {
            return null;
        }

        return substr($this->path, \strlen($anonymousTemplatePath) + 1);
    }
}
