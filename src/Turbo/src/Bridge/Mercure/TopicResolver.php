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

use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\ProtocolVersion;
use Symfony\UX\Turbo\Broadcaster\IdAccessor;

/**
 * Turns what the Twig functions listen to (topics, entities, entity classes) into Mercure topics.
 *
 * Listening to every entity of a class uses a pattern: a URI Template ("{id}") with the Mercure
 * protocol 0.x, a URL Pattern (":id") with the protocol 1.0, where URI Templates are gone.
 *
 * @internal
 */
final class TopicResolver
{
    public function __construct(
        private readonly IdAccessor $idAccessor,
    ) {
    }

    public static function speaksProtocolV1(?HubInterface $hub): bool
    {
        return null !== $hub
            && enum_exists(ProtocolVersion::class)
            // @phpstan-ignore function.alreadyNarrowedType (HubInterface::getProtocolVersion() exists since symfony/mercure 0.8)
            && method_exists($hub, 'getProtocolVersion')
            && ProtocolVersion::V1 === $hub->getProtocolVersion();
    }

    /**
     * @param list<object|string> $topics
     *
     * @return list<string> Topics, as the 0.x protocol and the "topic" query parameter take them
     */
    public function resolveForProtocolV0(array $topics): array
    {
        return array_map(fn (object|string $topic): string => $this->resolve($topic, false)[1], $topics);
    }

    /**
     * @param list<object|string> $topics
     *
     * @return list<string>|array<string, list<string>> Topics as mercure() and Grant take them:
     *                                                  a list of exact topics, or patterns per matcher type
     */
    public function resolveForProtocolV1(array $topics): array
    {
        $matchers = [];
        foreach ($topics as $topic) {
            [$type, $pattern] = $this->resolve($topic, true);
            $matchers[$type][] = $pattern;
        }

        return 1 === \count($matchers) && isset($matchers['exact']) ? $matchers['exact'] : $matchers;
    }

    /**
     * @return array{0: 'exact'|'urlpattern', 1: string}
     */
    private function resolve(object|string $topic, bool $protocolV1): array
    {
        if (\is_object($topic)) {
            $class = $topic::class;

            if (!$id = $this->idAccessor->getEntityId($topic)) {
                throw new \LogicException(\sprintf('Cannot listen to entity of class "%s" as the PropertyAccess component is not installed. Try running "composer require symfony/property-access".', $class));
            }

            return ['exact', \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($class), rawurlencode(implode('-', $id)))];
        }

        if (!preg_match('/[^a-zA-Z0-9_\x7f-\xff\\\\]/', $topic) && class_exists($topic)) {
            // Subscribe to updates for all objects of this class
            return $protocolV1
                ? ['urlpattern', \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($topic), ':id')]
                : ['exact', \sprintf(Broadcaster::TOPIC_PATTERN, rawurlencode($topic), '{id}')];
        }

        return ['exact', $topic];
    }
}
