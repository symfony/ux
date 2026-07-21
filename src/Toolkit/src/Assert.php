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
        if (1 !== preg_match('/^(@[a-z0-9-~][a-z0-9-._~]*\/)?[a-z0-9-~][a-z0-9-._~]*$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid NPM package name "%s".', $name));
        }
    }

    /**
     * Assert that a component prop name is valid (a camelCase Twig variable, ex: "id", "openOnLoad").
     *
     * @throws \InvalidArgumentException if the prop name is invalid
     */
    public static function propName(string $name): void
    {
        if (1 !== preg_match('/^[a-z][a-zA-Z0-9]*$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid prop name "%s".', $name));
        }
    }

    /**
     * Assert that a component block name is valid (a Twig block name, ex: "content", "icon").
     *
     * @throws \InvalidArgumentException if the block name is invalid
     */
    public static function blockName(string $name): void
    {
        if (1 !== preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid block name "%s".', $name));
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
     * Assert that the "league/commonmark" library is installed.
     *
     * It is an optional (dev) dependency of the Toolkit, only required to render kit and
     * recipe documentation as HTML. Installing the Toolkit to scaffold components does not pull it in.
     *
     * @throws \RuntimeException if "league/commonmark" is not installed
     */
    public static function commonMarkAvailable(): void
    {
        if (!class_exists(\League\CommonMark\Environment\Environment::class)) {
            throw new \RuntimeException('The "league/commonmark" library is required to render Toolkit documentation as HTML. Try running "composer require league/commonmark".');
        }
    }
}
