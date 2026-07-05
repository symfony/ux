<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

use Twig\Environment;

/**
 * Extracts the {@see ComponentDoc} (props and blocks) from a Twig component's source.
 *
 * Props and blocks come from the `{# @prop ... #}` and `{# @block ... #}` docblocks (name,
 * type and description), while each prop's default value is sourced from the `{%- props -%}`
 * declaration — the single source of truth for defaults — rather than duplicated in the
 * docblock. This is the canonical parser shared between the Toolkit linter and consumers such
 * as ux.symfony.com, so the documented API reference stays consistent everywhere.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentDocParser
{
    /**
     * @see https://regex101.com/r/3JXNX7/1
     */
    private const RE_PROP = '/{#\s+@prop\s+(?P<name>\w+)\s+(?P<type>[^\s]+)\s+(?P<description>.+?)\s+#}/s';

    /**
     * @see https://regex101.com/r/jYjXpq/1
     */
    private const RE_BLOCK = '/{#\s+@block\s+(?P<name>\w+)\s+(?P<description>.+?)\s+#}/s';

    private readonly PropsDeclarationParser $propsDeclarationParser;

    public function __construct(?Environment $twig = null)
    {
        $this->propsDeclarationParser = new PropsDeclarationParser($twig);
    }

    public function parse(string $source): ComponentDoc
    {
        $declaration = $this->propsDeclarationParser->parse($source);

        $props = [];
        if (preg_match_all(self::RE_PROP, $source, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $props[] = new Prop(
                    $match['name'],
                    $match['type'],
                    self::normalizeWhitespace($match['description']),
                    $declaration?->get($match['name'])?->default,
                );
            }
        }

        $blocks = [];
        if (preg_match_all(self::RE_BLOCK, $source, $matches, \PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $blocks[] = new Block($match['name'], self::normalizeWhitespace($match['description']));
            }
        }

        return new ComponentDoc($props, $blocks);
    }

    private static function normalizeWhitespace(string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', $value));
    }
}
