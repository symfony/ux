<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Twig;

/**
 * @internal
 */
final class TemplateNameParser
{
    /**
     * Copied from Twig\Loader\FilesystemLoader, and adjusted to needs for this class.
     *
     * @see \Twig\Loader\FilesystemLoader::parseName
     */
    public static function parse(string $name): string
    {
        if (str_starts_with($name, '@')) {
            if (!str_contains($name, '/')) {
                throw new \LogicException(\sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }

            return $name;
        }

        return $name;
    }
}
