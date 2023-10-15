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

namespace Symfony\UX\TwigComponent\Twig;

final class TemplateNameParser
{
    /**
     * Copied from Twig\Loader\FilesystemLoader, and adjusted to needs for this class (no namespace returned).
     *
     * @see \Twig\Loader\FilesystemLoader::parseName
     */
    public static function parse(string $name): mixed
    {
        if (isset($name[0]) && '@' == $name[0]) {
            if (false === $pos = strpos($name, '/')) {
                throw new \LogicException(sprintf('Malformed namespaced template name "%s" (expecting "@namespace/template_name").', $name));
            }

            return substr($name, $pos + 1);
        }

        return $name;
    }
}
