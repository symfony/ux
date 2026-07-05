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

/**
 * The API reference extracted from a Twig component's docblocks: its props and blocks.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class ComponentDoc
{
    /**
     * @param list<Prop>  $props
     * @param list<Block> $blocks
     */
    public function __construct(
        public readonly array $props = [],
        public readonly array $blocks = [],
    ) {
    }
}
