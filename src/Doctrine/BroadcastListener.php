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

namespace Symfony\UX\Turbo\Doctrine;

use Doctrine\Common\EventArgs;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\Contracts\Service\ResetInterface;
use Symfony\UX\Turbo\Broadcast;
use Symfony\UX\Turbo\Twig\Utils;
use Twig\Environment;

/**
 * Detects changes made from Doctrine entities and broadcast updates to the Mercure hub.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @link https://github.com/api-platform/core/blob/master/src/Bridge/Doctrine/EventListener/PublishMercureUpdatesListener.php Adapted from API Platform.
 * @todo backport MongoDB support
 */
final class BroadcastListener implements ResetInterface
{
    use Utils;

    private \SplObjectStorage $createdEntities;
    private \SplObjectStorage $updatedEntities;
    private \SplObjectStorage $removedEntities;

    public function __construct(
        private Environment $twig,
        private ?MessageBusInterface $messageBus = null,
        private ?PublisherInterface $publisher = null,
        private ?ExpressionLanguage $expressionLanguage = null,
    )
    {
        if (null === $this->messageBus && null === $this->publisher) {
            throw new \InvalidArgumentException('A message bus or a publisher must be provided.');
        }

        if (null === $this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }

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
                $this->publishUpdates($entity, $this->createdEntities[$entity], Broadcast::ACTION_CREATE);
            }

            foreach ($this->updatedEntities as $entity) {
                $this->publishUpdates($entity, $this->updatedEntities[$entity], Broadcast::ACTION_UPDATE);
            }

            foreach ($this->removedEntities as $entity) {
                $this->publishUpdates($entity, $this->removedEntities[$entity], Broadcast::ACTION_REMOVE);
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

    /**
     * @param object $entity
     */
    private function storeEntitiesToPublish($entity, string $property): void
    {
        if (!$attributes = (new \ReflectionClass($entity))->getAttributes(Broadcast::class)) {
            return;
        }

        $this->{$property}['removedEntities' === $property ? clone $entity : $entity] = $attributes;
    }

    /**
     * @param \ReflectionAttribute[] $attributes
     */
    private function publishUpdates(object $entity, array $attributes, string $action): void
    {
        foreach ($attributes as $attribute) {
            /**
             * @var Broadcast $broadcast
             */
            $broadcast = $attribute->newInstance();

            if (null !== $broadcast->topic) {
                $topic = $broadcast->topic;
            } elseif ($broadcast->topicExpression) {
                if (null === $this->expressionLanguage) {
                    throw new \RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
                }

                $topic = $this->expressionLanguage->evaluate($broadcast->topicExpression, ['entity' => $entity]);
            } else {
                $topic = $this->escapeId($entity::class);
            }

            $template = $broadcast->template ?? sprintf('broadcast/%s.stream.html.twig', $this->escapeId($entity::class));
            $data = $this->twig->render($template, ['entity' => $entity, 'action' => $action, 'topic' => $topic]);

            $update = new Update($topic, $data, $broadcast->private);

            $this->messageBus ? $this->messageBus->dispatch($update) : ($this->publisher)($update);
        }
    }
}
