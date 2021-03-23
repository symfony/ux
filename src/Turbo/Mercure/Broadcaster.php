<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Mercure;

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Security\Core\Authorization\ExpressionLanguage;
use Symfony\UX\Turbo\Broadcast;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Twig\Environment;

/**
 * Broadcasts updates rendered using Twig with Mercure.
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
    private $twig;
    private $hub;
    private $propertyAccessor;
    private $entityNamespace;

    /**
     * @var ExpressionLanguage|null
     */
    private $expressionLanguage;

    private const OPTIONS = [
        // Generic options
        'transports',
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
        string $name,
        Environment $twig,
        HubInterface $hub,
        ?PropertyAccessorInterface $propertyAccessor,
        ?ExpressionLanguage $expressionLanguage = null,
        ?string $entityNamespace = null
    ) {
        if (80000 > \PHP_VERSION_ID) {
            throw new \LogicException('The broadcast feature requires PHP 8.0 or greater, you must either upgrade to PHP 8 or disable it.');
        }

        $this->name = $name;
        $this->twig = $twig;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->hub = $hub;
        $this->entityNamespace = $entityNamespace;

        if ($expressionLanguage) {
            $this->expressionLanguage = $expressionLanguage;
        } elseif (class_exists(ExpressionLanguage::class)) {
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

        if (isset($options['transports']) && !\in_array($this->name, $options['transports'], false)) {
            return;
        }

        // What must we do if the template or the block doesn't exist? Throwing for now.
        $data = $this->twig->load($options['template'])->renderBlock($action, ['entity' => $entity, 'action' => $action, 'options' => $options]);

        $update = new Update(
            $options['topics'],
            $data,
            $options['private'] ?? false,
            $options['id'] ?? null,
            $options['type'] ?? null,
            $options['retry'] ?? null
        );

        $this->hub->publish($update);
    }

    /**
     * @param mixed[] $options
     *
     * @return mixed[]
     */
    private function normalizeOptions(object $entity, string $action, array $options): array
    {
        if (isset($options['transports'])) {
            $options['transports'] = (array) $options['transports'];
        }

        if (\is_string($options[0] ?? null)) {
            if (null === $this->expressionLanguage) {
                throw new \RuntimeException('The Expression Language component is not installed. Try running "composer require symfony/expression-language".');
            }

            $options = $this->expressionLanguage->evaluate($options[0], ['entity' => $entity, 'action' => $action]);
        }

        $entityClass = \get_class($entity);

        if ($extraKeys = array_diff(array_keys($options), self::OPTIONS)) {
            throw new \InvalidArgumentException(sprintf('Unknown broadcast options "%s" on class "%s". Valid options are: "%s"', implode('", "', $extraKeys), $entityClass, implode('", "', self::OPTIONS)));
        }

        $options['topics'] = (array) ($options['topics'] ?? sprintf(self::TOPIC_PATTERN, rawurlencode($entityClass), rawurlencode($this->propertyAccessor->getValue($entity, 'id'))));
        if (isset($options['template'])) {
            return $options;
        }

        $dir = $entityClass;
        if ($this->entityNamespace && 0 === strpos($entityClass, $this->entityNamespace)) {
            $dir = substr($entityClass, \strlen($this->entityNamespace));
        }

        $options['template'] = sprintf('broadcast/%s.stream.html.twig', str_replace('\\', '/', $dir));

        return $options;
    }
}
