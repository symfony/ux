<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\Turbo;

use Symfony\UX\Turbo\Broadcast\TwigMercureBroadcaster;

/**
 * Marks the entity as broadcastable.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Broadcast
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_REMOVE = 'remove';

    /**
     * @var mixed[]
     */
    public array $options;

    /**
     * Options can be any option supported by the broadcaster.
     *
     * @see TwigMercureBroadcaster for the default options
     */
    public function __construct(mixed ...$options)
    {
        $this->options = $options;
    }
}
