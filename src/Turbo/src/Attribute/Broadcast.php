<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Attribute;

use Symfony\UX\Turbo\Bridge\Mercure\Broadcaster;

/**
 * Marks the entity as broadcastable.
 *
 * @Annotation
 * @Target({"CLASS"})
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class Broadcast
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_REMOVE = 'remove';

    /**
     * @var array<mixed>
     */
    public array $options;

    /**
     * Options can be any option supported by the broadcaster.
     *
     * @see Broadcaster for the default options when using Mercure
     *
     * @param mixed[] ...$options
     */
    public function __construct(...$options)
    {
        // @phpstan-ignore function.alreadyNarrowedType
        if ([0] === array_keys($options) && \is_array($options[0]) && \is_string(key($options[0]))) {
            $options = $options[0];
        }

        $this->options = $options;
    }
}
