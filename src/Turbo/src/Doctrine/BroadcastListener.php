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
 */
final class BroadcastListener implements ResetInterface
{
    /**
     * @var array<class-string, array<mixed>>
     */
    private $broadcastedClasses;

    /**
     * @var \SplObjectStorage<object, array<mixed>>
     */
    private $createdEntities;
    /**
     * @var \SplObjectStorage<object, array<mixed>>
     */
    private $updatedEntities;
    /**
     * @var \SplObjectStorage<object, array<mixed>>
     */
    private $removedEntities;

    public function __construct(
        private BroadcasterInterface $broadcaster,
        private ?Reader $annotationReader = null,
    ) {
        $this->reset();
    }

    /**
     * Collects created, updated and removed entities.
     */
    public function onFlush(EventArgs $eventArgs): void
    {
        if (!$eventArgs instanceof OnFlushEventArgs) {
            return;
        }

        $em = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager() : $eventArgs->getEntityManager();
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

        $em = method_exists($eventArgs, 'getObjectManager') ? $eventArgs->getObjectManager() : $eventArgs->getEntityManager();

        try {
            foreach ($this->createdEntities as $entity) {
                $options = $this->createdEntities[$entity];
                $id = $em->getClassMetadata($entity::class)->getIdentifierValues($entity);
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
        $class = ClassUtil::getEntityClass($entity);

        if (!isset($this->broadcastedClasses[$class])) {
            $this->broadcastedClasses[$class] = [];
            $r = new \ReflectionClass($class);

            if ($options = $r->getAttributes(Broadcast::class)) {
                foreach ($options as $option) {
                    $this->broadcastedClasses[$class][] = $option->newInstance()->options;
                }
            } elseif ($this->annotationReader && $options = $this->annotationReader->getClassAnnotations($r)) {
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
