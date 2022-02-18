<?php

namespace Symfony\UX\TwigComponent\Attribute;

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class PreMount
{
    /**
     * @param int $priority If multiple hooks are registered in a component, use to configure
     *                      the order in which they are called (higher called earlier)
     */
    public function __construct(public int $priority = 0)
    {
    }
}
