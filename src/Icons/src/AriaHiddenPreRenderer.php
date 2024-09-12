<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Icons;

final class AriaHiddenPreRenderer implements IconPreRendererInterface
{
    public function __invoke(string $name, Icon $icon): Icon
    {
        if ([] === array_intersect(['aria-hidden', 'aria-label', 'aria-labelledby', 'title'], array_keys($icon->getAttributes()))) {
            return $icon->withAttributes(['aria-hidden' => 'true']);
        }

        return $icon;
    }
}
