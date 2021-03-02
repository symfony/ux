<?php

/*
 * This file is part of the Symfony UX Turbo package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Turbo\Broadcast;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

/**
 * Detects changes made from Doctrine entities and broadcasts updates to the Mercure hub.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @see https://github.com/api-platform/core/blob/master/src/Bridge/Doctrine/EventListener/PublishMercureUpdatesListener.php Adapted from API Platform.
 *
 * @todo backport MongoDB support
 *
 * @experimental
 */
final class BroadcastListener implements ResetInterface
{
    private $broadcaster;

    /**
     * @var \SplObjectStorage<object, object>
     */
    private \SplObjectStorage $createdEntities;
    /**
     * @var \SplObjectStorage<object, object>
     */
    private \SplObjectStorage $updatedEntities;
    /**
     * @var \SplObjectStorage<object, object>
     */
    private \SplObjectStorage $removedEntities;

    public function __construct(BroadcasterInterface $broadcaster)
    {
        if (80000 > \PHP_VERSION_ID) {
            throw new \LogicException('The broadcast feature requires PHP 8.0 or greater, you must either upgrade to PHP 8 or disable it.');
        }

        $this->reset();

        $this->broadcaster = $broadcaster;
    }

    /**
     * Collects created, updated and removed entities.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if (!$eventArgs instanceof OnFlushEventArgs) {
            return;
        }

        $uow = $eventArgs->getEntityManager()->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->storeEntitiesToPublish($entity, 'createdEntities');
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->storeEntitiesToPublish($entity, 'updatedEntities');
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->storeEntitiesToPublish($entity, 'removedEntities');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush(): void
    {
        try {
            foreach ($this->createdEntities as $entity) {
                $this->broadcaster->broadcast($entity, Broadcast::ACTION_CREATE);
            }

            foreach ($this->updatedEntities as $entity) {
                $this->broadcaster->broadcast($entity, Broadcast::ACTION_UPDATE);
            }

            foreach ($this->removedEntities as $entity) {
                $this->broadcaster->broadcast($entity, Broadcast::ACTION_REMOVE);
            }
        } finally {
            $this->reset();
        }
    }

    public function reset(): void
    {
        $this->createdEntities = new \SplObjectStorage();
        $this->updatedEntities = new \SplObjectStorage();
        $this->removedEntities = new \SplObjectStorage();
    }

    private function storeEntitiesToPublish(object $entity, string $property): void
    {
        if ((new \ReflectionClass($entity))->getAttributes(Broadcast::class)) {
            $this->{$property}->attach('removedEntities' === $property ? clone $entity : $entity);
        }
    }
}
