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
 * Call a method before re-rendering.
 *
 * This hook ONLY happens when rendering via HTTP: it does
 * not happen during the initial render of a component.
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
final class PreReRender
{
    /**
     * @param int $priority If multiple hooks are registered in a component, use to configure
     *                      the order in which they are called (higher called earlier)
     */
    public function __construct(public int $priority = 0)
    {
    }
}
