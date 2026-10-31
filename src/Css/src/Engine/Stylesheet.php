<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Engine;

use Symfony\UX\Css\Engine\Css\AtRule;
use Symfony\UX\Css\Engine\Css\Node;
use Symfony\UX\Css\Engine\Css\Optimizer;
use Symfony\UX\Css\Engine\Css\Root;
use Symfony\UX\Css\Exception\InvalidArgumentException;

/**
 * Port of `Stylesheet::processDecoder()` and `toCss()` (packages/core/src/stylesheet.ts) for the utilities layer.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class Stylesheet
{
    public function __construct(
        private readonly ?Breakpoints $breakpoints = null,
    ) {
    }

    /**
     * @param list<AtomicStyle> $styles
     * @param list<Node>        $tokens the content of the tokens layer, see {@see TokenCss}
     */
    public function render(array $styles, array $tokens = []): string
    {
        return $this->build($styles, $tokens)->toString();
    }

    /**
     * @param list<AtomicStyle> $styles
     * @param list<Node>        $tokens
     */
    public function build(array $styles, array $tokens = []): Root
    {
        $layer = new AtRule('layer', 'utilities');
        foreach (SortStyleRules::sort($styles) as $style) {
            $layer->append(...StyleStringifier::stringify($style->result));
        }

        $root = new Root([...([] !== $tokens ? [new AtRule('layer', 'tokens', $tokens)] : []), $layer]);
        $this->expandBreakpointAtRules($root);

        return new Optimizer()->optimize($root);
    }

    /**
     * Port of `Breakpoints::expandScreenAtRule()` (packages/core/src/breakpoints.ts).
     */
    private function expandBreakpointAtRules(Root $root): void
    {
        $conditions = null;
        $root->walk(function (Node $node) use (&$conditions): void {
            if (!$node instanceof AtRule || 'breakpoint' !== $node->name) {
                return;
            }

            $conditions ??= $this->breakpoints?->getConditions() ?? [];
            $condition = $conditions[$node->params]
                ?? throw new InvalidArgumentException(\sprintf('No "%s" breakpoint found.', $node->params));
            $node->name = 'media';
            $node->params = (string) $condition->params;
        });
    }
}
