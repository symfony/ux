<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Tests\Fixtures;

use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

/**
 * @internal
 */
final class CollectingBroadcaster implements BroadcasterInterface
{
    /** @var list<array{string, array<string, mixed>}> */
    public array $broadcasts = [];

    public function broadcast(object $entity, string $action, array $options): void
    {
        $this->broadcasts[] = [$action, $options];
    }
}
