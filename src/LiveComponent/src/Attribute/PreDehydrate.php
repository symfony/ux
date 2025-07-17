<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

/**
 * An attribute to register a PreDehydrate hook.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class PreDehydrate
{
    /**
     * @param int $priority If multiple hooks are registered in a component, use to configure
     *                      the order in which they are called (higher called earlier)
     */
    public function __construct(public int $priority = 0)
    {
    }
}
