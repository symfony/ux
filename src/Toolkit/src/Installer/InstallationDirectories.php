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
 * The directories where recipes install their files, referenced by the "%COMPONENTS_DIR%"
 * and "%CONTROLLERS_DIR%" placeholders in the destinations of their "copy-files".
 *
 * Components and controllers installed in a subdirectory get it as a prefix of their names,
 * as TwigComponent and the Stimulus loaders derive these names from the path of the files.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class InstallationDirectories
{
    public const DEFAULT_COMPONENTS_DIR = 'templates/components';
    public const DEFAULT_CONTROLLERS_DIR = 'assets/controllers';

    public const COMPONENTS_DIR_PLACEHOLDER = '%COMPONENTS_DIR%';
    public const CONTROLLERS_DIR_PLACEHOLDER = '%CONTROLLERS_DIR%';

    private readonly string $componentsDir;
    private readonly ?string $componentsSubdirectory;
    private readonly string $controllersDir;
    private readonly ?string $controllersSubdirectory;

    public function __construct(
        string $componentsDir = self::DEFAULT_COMPONENTS_DIR,
        ?string $componentsSubdirectory = null,
        string $controllersDir = self::DEFAULT_CONTROLLERS_DIR,
        ?string $controllersSubdirectory = null,
    ) {
        $this->componentsDir = self::normalizeDirectory($componentsDir);
        $this->componentsSubdirectory = null === $componentsSubdirectory ? null : self::normalizeSubdirectory($componentsSubdirectory);
        $this->controllersDir = self::normalizeDirectory($controllersDir);
        $this->controllersSubdirectory = null === $controllersSubdirectory ? null : self::normalizeSubdirectory($controllersSubdirectory);
    }

    /**
     * @throws \InvalidArgumentException if the path contains an unknown placeholder
     */
    public static function assertKnownPlaceholders(string $path): void
    {
        preg_match_all('/%[A-Z_]+%/', $path, $matches);

        foreach ($matches[0] as $placeholder) {
            if (self::COMPONENTS_DIR_PLACEHOLDER !== $placeholder && self::CONTROLLERS_DIR_PLACEHOLDER !== $placeholder) {
                throw new \InvalidArgumentException(\sprintf('Unknown placeholder "%s" in "%s", supported placeholders are "%s", "%s".', $placeholder, $path, self::COMPONENTS_DIR_PLACEHOLDER, self::CONTROLLERS_DIR_PLACEHOLDER));
            }
        }
    }

    /**
     * @throws \InvalidArgumentException if the path contains an unknown placeholder
     */
    public function resolve(string $path): string
    {
        self::assertKnownPlaceholders($path);

        return strtr($path, [
            self::COMPONENTS_DIR_PLACEHOLDER => Path::join($this->componentsDir, $this->componentsSubdirectory ?? ''),
            self::CONTROLLERS_DIR_PLACEHOLDER => Path::join($this->controllersDir, $this->controllersSubdirectory ?? ''),
        ]);
    }

    /**
     * The prefix TwigComponent adds to the name of an anonymous component installed in the
     * components subdirectory (e.g. "ui:" for "ui").
     */
    public function getComponentNamePrefix(): string
    {
        return null === $this->componentsSubdirectory ? '' : str_replace('/', ':', $this->componentsSubdirectory).':';
    }

    /**
     * The prefix Stimulus loaders add to the identifier of a controller installed in the
     * controllers subdirectory (e.g. "ui--" for "ui").
     */
    public function getStimulusIdentifierPrefix(): string
    {
        return null === $this->controllersSubdirectory ? '' : str_replace(['_', '/'], ['-', '--'], $this->controllersSubdirectory).'--';
    }

    /**
     * @throws \InvalidArgumentException if the directory is not a relative path to a directory inside the project
     */
    public static function normalizeDirectory(string $directory): string
    {
        if ('' === $directory) {
            throw new \InvalidArgumentException('The directory must not be empty.');
        }
        if (str_contains($directory, "\0")) {
            throw new \InvalidArgumentException('The directory must not contain a null byte.');
        }
        // Path::isRelative() accepts "C:\" and "\\server" outside of Windows, where the same configuration may be used
        if (!Path::isRelative($directory) || preg_match('/^(?:[\/\\\\]|[a-z][a-z0-9+.-]*:)/i', $directory)) {
            throw new \InvalidArgumentException(\sprintf('The directory "%s" must be a relative path.', $directory));
        }

        Assert::pathDoesNotEscapeDirectory($directory);

        if ('' === $canonicalDirectory = Path::canonicalize($directory)) {
            throw new \InvalidArgumentException(\sprintf('The directory "%s" must be inside the project.', $directory));
        }

        return $canonicalDirectory;
    }

    /**
     * @throws \InvalidArgumentException if the subdirectory contains anything else than directory names
     */
    public static function normalizeSubdirectory(string $subdirectory): string
    {
        // its path ends up in component names and Stimulus identifiers
        if (!preg_match('/^[\w-]+(?:\/[\w-]+)*\/?$/D', $subdirectory)) {
            throw new \InvalidArgumentException(\sprintf('The subdirectory "%s" must only contain letters, digits, "_" and "-", with "/" between directories.', $subdirectory));
        }

        return rtrim($subdirectory, '/');
    }
}
