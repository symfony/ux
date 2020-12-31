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

namespace Symfony\UX\Turbo\Broadcaster;

use Symfony\Component\Mercure\PublisherInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\UX\Turbo\Broadcast;
use Twig\Environment;

/**
 * Broadcasts updates rendered using Twig with with Mercure.
 *
 * Supported options are:
 *
 * * topics (string[]) Mercure topics to use, defaults to an array containing the Fully Qualified Class Name with "\" characters replaced by ":" characters.
 * * createTemplate (string) The Twig template to render when a new object is created
 * * updateTemplate (string) The Twig template to render when a new object is updated
 * * removeTemplate (string) The Twig template to render when a new object is removed
 * * private (bool) Marks Mercure updates as private
 * * id (string) ID field of the SSE
 * * type (string) type field of the SSE
 * * retry (int) retry field of the SSE
 *
 * The options can also be generated using the ExpressionLanguage language: if the option is a string, it is evaluated as an expression that must return an array.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TwigMercureBroadcaster implements BroadcasterInterface
{
    private const OPTIONS = [
        // Twig options
        'template',
        // Mercure options
        'topics',
        'private',
        'id',
        'type',
        'retry',
    ];

    public function __construct(
        private Environment $twig,
        private ?MessageBusInterface $messageBus = null,
        private ?PublisherInterface $publisher = null,
        private ?ExpressionLanguage $expressionLanguage = null,
        private ?string $entityNamespace = null,
    ) {
        if (null === $this->messageBus && null === $this->publisher) {
            throw new \InvalidArgumentException('A message bus or a publisher must be provided.');
        }

        if (null === $this->expressionLanguage) {
            $this->expressionLanguage = new ExpressionLanguage();
        }
    }

    public function broadcast(object $entity, string $action): void
    {
        if (!$attribute = (new \ReflectionClass($entity))->getAttributes(Broadcast::class)[0] ?? null) {
            return;
        }

        /**
         * @var Broadcast $broadcast
         */
        $broadcast = $attribute->newInstance();
        $options = $this->normalizeOptions($entity, $action, $broadcast->options);

        // What must we do if the template or the block doesn't exist? Throwing for now.
        $data = $this->twig->load($options['template'])->renderBlock($action, ['entity' => $entity, 'action' => $action, 'options' => $options]);

        $update = new Update(
            $options['topics'],
            $data,
            $options['private'] ?? false,
            $options['id'] ?? null,
            $options['type'] ?? null,
            $options['retry'] ?? null,
        );

        $this->messageBus ? $this->messageBus->dispatch($update) : ($this->publisher)($update);
    }

    private function normalizeOptions(object $entity, string $action, mixed $options): array
    {
        if (is_string($options)) {
            if (null === $this->expressionLanguage) {
                throw new \RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }

            $options = $this->expressionLanguage->evaluate($options, ['entity' => $entity, 'action' => $action]);
        }

        if ($extraKeys = array_diff(array_keys($options), self::OPTIONS)) {
            throw new \InvalidArgumentException(sprintf('Unknown broadcast options "%s" on class "%s". Valid options are: "%s"', implode('", "', $extraKeys), $entity::class, implode('", "', self::OPTIONS)));
        }

        $options['topics'] = (array) ($options['topics'] ?? $entity::class);
        if (!isset($options['template'])) {
            $dir = $entity::class;
            if (null !== $this->entityNamespace && str_starts_with($entity::class, $this->entityNamespace)) {
                $dir = substr($entity::class, strlen($this->entityNamespace));
            }

            $options['template'] = sprintf('broadcast/%s.stream.html.twig', str_replace('\\', '/', $dir));
        }

        return $options;
    }
}
