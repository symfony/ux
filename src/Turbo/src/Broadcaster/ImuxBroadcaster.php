<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Broadcaster;

/**
 * Passes the incoming updates to all registered broadcasters (inverse multiplexing).
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class ImuxBroadcaster implements BroadcasterInterface
{
    private $broadcasters;

    /**
     * @param BroadcasterInterface[] $broadcasters
     */
    public function __construct(iterable $broadcasters)
    {
        $this->broadcasters = $broadcasters;
    }

    public function broadcast(object $entity, string $action, array $options): void
    {
        foreach ($this->broadcasters as $broadcaster) {
            $broadcaster->broadcast($entity, $action, $options);
        }
    }
}
