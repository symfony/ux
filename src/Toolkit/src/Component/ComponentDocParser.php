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
 * Prop names, defaults and their `## <type> <description>` documentation come from the `{% props %}`
 * declaration ({@see PropsDeclarationParser}), while each block's `{## <description> #}` documentation
 * comes from tokenizing the template ({@see ComponentDocScanner}). Both rely on Twig 3.29 attaching
 * documentation comments to their following token. This assembles the API reference for consumers
 * such as ux.symfony.com; the Toolkit linter validates the same documentation at a lower level.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentDocParser
{
    private readonly PropsDeclarationParser $propsDeclarationParser;
    private readonly ComponentDocScanner $scanner;

    public function __construct(?Environment $twig = null)
    {
        $this->propsDeclarationParser = new PropsDeclarationParser($twig);
        $this->scanner = new ComponentDocScanner($twig);
    }

    public function parse(string $source): ComponentDoc
    {
        $props = [];
        if (null !== $declaration = $this->propsDeclarationParser->parse($source)) {
            foreach ($declaration->props as $prop) {
                [$type, $description] = ComponentDocScanner::splitTypeAndDescription($prop->documentation ?? '');
                $props[] = new Prop($prop->name, $type, $description, $prop->default);
            }
        }

        $blocks = [];
        foreach ($this->scanner->scanBlocks($source)['docs'] as $block) {
            $blocks[] = new Block($block['name'], $block['description']);
        }

        return new ComponentDoc($props, $blocks);
    }
}
