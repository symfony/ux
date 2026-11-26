<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit;

final class Assert
{
    /**
     * Assert that the kit name is valid (ex: "Shadcn", "Tailwind", "Bootstrap", etc.).
     *
     * @param non-empty-string $name
     *
     * @throws \InvalidArgumentException if the kit name is invalid
     */
    public static function kitName(string $name): void
    {
        if (1 !== preg_match('/^[a-zA-Z0-9](?:[a-zA-Z0-9-_ ]{0,61}[a-zA-Z0-9])?$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid kit name "%s".', $name));
        }
    }

    /**
     * Assert that the component name is valid (ex: "Button", "Input", "Card", "Card:Header", etc.).
     *
     * @param non-empty-string $name
     *
     * @throws \InvalidArgumentException if the component name is invalid
     */
    public static function componentName(string $name): void
    {
        if (1 !== preg_match('/^[A-Z][a-zA-Z0-9]*(?::[A-Z][a-zA-Z0-9]*)*$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid component name "%s".', $name));
        }
    }

    /**
     * Assert that the PHP package name is valid (ex: "twig/html-extra", "symfony/framework-bundle", etc.).
     *
     * @param non-empty-string $name
     *
     * @throws \InvalidArgumentException if the PHP package name is invalid
     */
    public static function phpPackageName(string $name): void
    {
        // Taken from https://github.com/composer/composer/blob/main/res/composer-schema.json
        if (1 !== preg_match('/^[a-z0-9]([_.-]?[a-z0-9]+)*\/[a-z0-9](([_.]|-{1,2})?[a-z0-9]+)*$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid PHP package name "%s".', $name));
        }
    }

    /**
     * Assert that the NPM package name is valid (ex: "react", "@hotwired/stimulus", etc.).
     *
     * @param non-empty-string $name
     *
     * @throws \InvalidArgumentException if the NPM package name is invalid
     */
    public static function npmPackageName(string $name): void
    {
        // Taken from https://github.com/dword-design/package-name-regex/blob/master/src/index.ts
        if (1 !== preg_match('/^(@[a-z0-9~][a-z0-9-._~]*\/)?[a-z0-9~][a-z0-9-._~]*$/D', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid NPM package name "%s".', $name));
        }
    }

    /**
     * Assert that a version constraint is valid for Composer and NPM (ex: "^1.0", ">=1.2 <2.0", "^1.0 || ^2.0", "dev-main", "latest", etc.).
     *
     * Operators are only accepted at the start of a constraint, parts are separated by a single space, a comma or "|" / "||",
     * so the constraint cannot hold an option, a shell metacharacter, a control character or a console markup tag.
     *
     * @param non-empty-string $version
     *
     * @throws \InvalidArgumentException if the constraint version is invalid
     */
    public static function constraintVersion(string $version): void
    {
        $part = '(?:(?:[<>]=?|!=|[=^~]) ?)?[A-Za-z0-9*][A-Za-z0-9.*+@_-]*';

        if (1 !== preg_match(\sprintf('/^%1$s(?:(?: ?\|\|? ?| ?, ?| - | )%1$s)*$/D', $part), $version)) {
            throw new \InvalidArgumentException(\sprintf('Invalid constraint version "%s".', $version));
        }
    }

    /**
     * Assert that the Importmap package name is valid (ex: "react", "@hotwired/stimulus", "bootstrap/dist/css/bootstrap.min.css", etc.).
     *
     * @param non-empty-string $name
     *
     * @throws \InvalidArgumentException if the Importmap package name is invalid
     */
    public static function importmapPackageName(string $name): void
    {
        if (1 !== preg_match('/^(@[a-z0-9~][a-z0-9-._~]*\/)?[a-z0-9~][a-z0-9-._~]*(\/[A-Za-z0-9_~-][A-Za-z0-9._~-]*)*$/D', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid importmap package name "%s".', $name));
        }
    }

    /**
     * Assert that a relative path does not escape its target directory through a ".." segment.
     *
     * This rejects any path containing a ".." segment (using either "/" or "\" as separator),
     * which would let a crafted recipe read from or write to a location outside its directory.
     *
     * @throws \InvalidArgumentException if the path escapes its target directory
     */
    public static function pathDoesNotEscapeDirectory(string $path): void
    {
        if (\in_array('..', preg_split('#[\\\\/]+#', $path), true)) {
            throw new \InvalidArgumentException(\sprintf('The path "%s" must not escape its target directory.', $path));
        }
    }

    /**
     * Assert that a relative path does not contain glob characters, so that it is always read literally.
     *
     * @throws \InvalidArgumentException if the path contains a glob character
     */
    public static function pathHasNoGlobCharacters(string $path): void
    {
        if (1 === preg_match('/[*?\[\]{}]/', $path)) {
            throw new \InvalidArgumentException(\sprintf('The path "%s" must not contain glob characters.', $path));
        }
    }
}
