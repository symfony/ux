<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Doc;

/**
 * A recipe's documentation rendered as HTML, with the table of contents extracted from the same render.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class RenderedRecipeDoc
{
    /**
     * @param list<array{level: int, title: string, id: string}> $tableOfContents
     */
    public function __construct(
        public readonly string $html,
        public readonly array $tableOfContents,
    ) {
    }
}
