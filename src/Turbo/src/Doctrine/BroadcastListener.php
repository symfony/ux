<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\EventArgs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

/**
 * Detects changes made from Doctrine entities and broadcasts updates to the broadcasters.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class BroadcastListener implements ResetInterface
{
    private $broadcaster;
    private $annotationReader;

    /**
     * @var array<class-string, array[]>
     */
    private $broadcastedClasses;

    /**
     * @var \SplObjectStorage<object, array>
     */
    private $createdEntities;
    /**
     * @var \SplObjectStorage<object, array>
     */
    private $updatedEntities;
    /**
     * @var \SplObjectStorage<object, array>
     */
    private $removedEntities;

    public function __construct(BroadcasterInterface $broadcaster, Reader $annotationReader = null)
    {
        $this->reset();

        $this->broadcaster = $broadcaster;
        $this->annotationReader = $annotationReader;
    }

    /**
     * Collects created, updated and removed entities.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if (!$eventArgs instanceof OnFlushEventArgs) {
            return;
        }

        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            $this->storeEntitiesToPublish($em, $entity, 'createdEntities');
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $this->storeEntitiesToPublish($em, $entity, 'updatedEntities');
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->storeEntitiesToPublish($em, $entity, 'removedEntities');
        }
    }

    /**
     * Publishes updates for changes collected on flush, and resets the store.
     */
    public function postFlush(EventArgs $eventArgs): void
    {
        if (!$eventArgs instanceof PostFlushEventArgs) {
            return;
        }

        $em = $eventArgs->getEntityManager();

        try {
            foreach ($this->createdEntities as $entity) {
                $options = $this->createdEntities[$entity];
                $id = $em->getClassMetadata(\get_class($entity))->getIdentifierValues($entity);
                foreach ($options as $option) {
                    $option['id'] = $id;
                    $this->broadcaster->broadcast($entity, Broadcast::ACTION_CREATE, $option);
                }
            }

            foreach ($this->updatedEntities as $entity) {
                foreach ($this->updatedEntities[$entity] as $option) {
                    $this->broadcaster->broadcast($entity, Broadcast::ACTION_UPDATE, $option);
                }
            }

            foreach ($this->removedEntities as $entity) {
                foreach ($this->removedEntities[$entity] as $option) {
                    $this->broadcaster->broadcast($entity, Broadcast::ACTION_REMOVE, $option);
                }
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

    private function storeEntitiesToPublish(EntityManagerInterface $em, object $entity, string $property): void
    {
        $class = \get_class($entity);

        if (!isset($this->broadcastedClasses[$class])) {
            $this->broadcastedClasses[$class] = [];
            $r = null;

            if (\PHP_VERSION_ID >= 80000 && $options = ($r = new \ReflectionClass($class))->getAttributes(Broadcast::class)) {
                foreach ($options as $option) {
                    $this->broadcastedClasses[$class][] = $option->newInstance()->options;
                }
            } elseif ($this->annotationReader && $options = $this->annotationReader->getClassAnnotations($r ?? new \ReflectionClass($class))) {
                foreach ($options as $option) {
                    if ($option instanceof Broadcast) {
                        $this->broadcastedClasses[$class][] = $option->options;
                    }
                }
            }
        }

        if ($options = $this->broadcastedClasses[$class]) {
            if ('createdEntities' !== $property) {
                $id = $em->getClassMetadata($class)->getIdentifierValues($entity);
                foreach ($options as $k => $option) {
                    $options[$k]['id'] = $id;
                }
            }

            $this->{$property}->attach($entity, $options);
        }
    }
}
