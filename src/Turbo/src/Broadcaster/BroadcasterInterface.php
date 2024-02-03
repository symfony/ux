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
 * Broadcasts an update of an entity.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
interface BroadcasterInterface
{
    /**
     * @param array{id?: string|string[], transports?: string|string[], topics?: string|string[], template?: string, rendered_action?: string, private?: bool, sse_id?: string, sse_type?: string, sse_retry?: int} $options
     */
    public function broadcast(object $entity, string $action, array $options): void;
}
