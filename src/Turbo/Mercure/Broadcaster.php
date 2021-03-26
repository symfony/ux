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
use Symfony\UX\Turbo\Attribute\Broadcast;
use Symfony\UX\Turbo\Broadcaster\BroadcasterInterface;
use Twig\Environment;

/**
 * Broadcasts updates rendered using Twig with Mercure.
 *
 * Supported options are:
 *
 *  * transports (string[]) The name of the transports to broadcast to
 *  * topics (string[]) The topics to use; the default topic is derived from the FQCN of the entity and from its id
 *  * template (string) The Twig template to render when a new object is created, updated or removed
 *  * private (bool) Marks Mercure updates as private
 *  * id (string) ID field of the SSE
 *  * type (string) type field of the SSE
 *  * retry (int) retry field of the SSE
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
    private $templatePrefixes;

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

    /**
     * @param array<string, string> $templatePrefixes
     */
    public function __construct(string $name, Environment $twig, HubInterface $hub, ?PropertyAccessorInterface $propertyAccessor, array $templatePrefixes = [])
    {
        if (80000 > \PHP_VERSION_ID) {
            throw new \LogicException('The broadcast feature requires PHP 8.0 or greater, you must either upgrade to PHP 8 or disable it.');
        }

        $this->name = $name;
        $this->twig = $twig;
        $this->propertyAccessor = $propertyAccessor ?? PropertyAccess::createPropertyAccessor();
        $this->hub = $hub;
        $this->templatePrefixes = $templatePrefixes;
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

        if (isset($options['transports']) && !\in_array($this->name, $options['transports'], true)) {
            return;
        }

        // Will throw if the template or the block doesn't exist
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

        $entityClass = \get_class($entity);

        if ($extraKeys = array_diff(array_keys($options), self::OPTIONS)) {
            throw new \InvalidArgumentException(sprintf('Unknown broadcast options "%s" on class "%s". Valid options are: "%s"', implode('", "', $extraKeys), $entityClass, implode('", "', self::OPTIONS)));
        }

        $options['topics'] = (array) ($options['topics'] ?? sprintf(self::TOPIC_PATTERN, rawurlencode($entityClass), rawurlencode($this->propertyAccessor->getValue($entity, 'id'))));
        if (isset($options['template'])) {
            return $options;
        }

        $file = $entityClass;
        foreach ($this->templatePrefixes as $namespace => $prefix) {
            if (0 === strpos($entityClass, $namespace)) {
                $file = substr_replace($entityClass, $prefix, 0, \strlen($namespace));
                break;
            }
        }

        $options['template'] = str_replace('\\', '/', $file).'.stream.html.twig';

        return $options;
    }
}
