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

    public static function stimulusControllerName(string $name): void
    {
        if (1 !== preg_match('/^[a-z][a-z0-9-]*[a-z0-9]$/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Invalid Stimulus controller name "%s".', $name));
        }
    }
}
