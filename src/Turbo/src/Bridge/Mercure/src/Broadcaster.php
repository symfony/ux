<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Bridge\Mercure;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;

/**
 * Broadcasts updates rendered using Twig with Mercure.
 *
 * Supported options are:
 *
 *  * id (string[]) The (potentially composite) identifier of the broadcasted entity
 *  * transports (string[]) The name of the transports to broadcast to
 *  * topics (string[]) The topics to use; the default topic is derived from the FQCN of the entity and from its id
 *  * rendered_action (string) The turbo-stream action rendered as HTML
 *  * private (bool) Marks Mercure updates as private
 *  * sse_id (string) ID field of the SSE
 *  * sse_type (string) type field of the SSE
 *  * sse_retry (int) retry field of the SSE
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 *
 * @experimental
 */
final class Broadcaster implements BroadcasterInterface
{
    /**
     * @internal
     */
    public const TOPIC_PATTERN = 'https://symfony.com/ux-turbo/%s/%s';

    private $name;
    private $hub;

    /** @var ExpressionLanguage|null */
    private $expressionLanguage;

    public function __construct(string $name, HubInterface $hub)
    {
        $this->name = $name;
        $this->hub = $hub;

        if (class_exists(ExpressionLanguage::class)) {
            $this->expressionLanguage = new ExpressionLanguage();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(object $entity, string $action, array $options): void
    {
        if (isset($options['transports']) && !\in_array($this->name, (array) $options['transports'], true)) {
            return;
        }

        $entityClass = \get_class($entity);

        if (!isset($options['rendered_action'])) {
            throw new \InvalidArgumentException(sprintf('Cannot broadcast entity of class "%s" as option "rendered_action" is missing.', $entityClass));
        }

        if (!isset($options['topic']) && !isset($options['id'])) {
            throw new \InvalidArgumentException(sprintf('Cannot broadcast entity of class "%s": either option "topics" or "id" is missing, or the PropertyAccess component is not installed. Try running "composer require property-access".', $entityClass));
        }

        $topics = [];

        foreach ((array) ($options['topics'] ?? []) as $topic) {
            if (!\is_string($topic)) {
                $topics[] = $topic;
                continue;
            }

            if (0 !== strpos($topic, '@=')) {
                $topics[] = $topic;
                continue;
            }

            if (null === $this->expressionLanguage) {
                throw new \LogicException('The "@=" expression syntax cannot be used without the Expression Language component. Try running "composer require symfony/expression-language".');
            }

            $topics[] = $this->expressionLanguage->evaluate(substr($topic, 2), ['entity' => $entity]);
        }

        $options['topics'] = $topics;

        if (0 === \count($options['topics'])) {
            $options['topics'] = (array) sprintf(self::TOPIC_PATTERN, rawurlencode($entityClass), rawurlencode(implode('-', (array) $options['id'])));
        }

        $update = new Update(
            $options['topics'],
            $options['rendered_action'],
            $options['private'] ?? false,
            $options['sse_id'] ?? null,
            $options['sse_type'] ?? null,
            $options['sse_retry'] ?? null
        );

        $this->hub->publish($update);
    }
}
