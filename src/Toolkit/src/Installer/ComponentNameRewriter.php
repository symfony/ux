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

/**
 * Prefixes the component references of a kit template when its components are installed
 * in a directory that changes their Twig name.
 *
 * Recipes reference each other by bare name (ex: "<twig:Button>" inside the Dialog recipe),
 * but a component installed in "templates/components/ui" is named "ui:Button". Only names
 * belonging to the kit are rewritten, so "<twig:ux:icon>" and "<twig:block>" are left alone.
 *
 * @internal
 *
 * @author Romain Monteil <monteil.romain@gmail.com>
 */
final class ComponentNameRewriter
{
    /**
     * Matches an opening or closing component tag, capturing the whole name at once so
     * "Dialog" and "Dialog:Content" cannot be confused with one another.
     */
    private const COMPONENT_TAG_PATTERN = '#(?P<tag></?twig:)(?P<name>[A-Z][a-zA-Z0-9]*(?::[A-Z][a-zA-Z0-9]*)*)#';

    /**
     * @param array<string,true> $componentNames
     */
    public function __construct(
        private readonly array $componentNames,
        private readonly string $prefix,
    ) {
    }

    public function rewrite(string $twigSource): string
    {
        if ('' === $this->prefix || [] === $this->componentNames) {
            return $twigSource;
        }

        return preg_replace_callback(
            self::COMPONENT_TAG_PATTERN,
            fn (array $matches) => isset($this->componentNames[$matches['name']])
                ? $matches['tag'].$this->prefix.$matches['name']
                : $matches[0],
            $twigSource,
        );
    }
}
