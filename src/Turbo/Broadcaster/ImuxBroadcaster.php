<?php

declare(strict_types=1);

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

    public function broadcast(object $entity, string $action): void
    {
        foreach ($this->broadcasters as $broadcaster) {
            $broadcaster->broadcast($entity, $action);
        }
    }
}
