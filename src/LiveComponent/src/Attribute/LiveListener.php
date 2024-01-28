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
 * An Attribute to register a LiveListener method.
 *
 * When any component emits the event, an Ajax call will be made to call this
 * method and re-render the component.
 *
 * @see https://symfony.com/bundles/ux-live-component/current/index.html#listeners
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class LiveListener extends LiveAction
{
    /**
     * @param string $eventName The name of the event to listen to (e.g. "itemUpdated")
     */
    public function __construct(private string $eventName)
    {
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }
}
