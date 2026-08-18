<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

/**
 * An Attribute to register a LiveListener method.
 *
 * When any component emits the event, an Ajax call will be made to call this
 * method and re-render the component.
 *
 * The event name can be followed by a condition, in parentheses, using the
 * ExpressionLanguage-like syntax evaluated entirely on the client (it never
 * triggers an Ajax call by itself):
 *
 *     #[LiveListener('product_updated(event.id == props.product)')]
 *
 * The condition has access to two variables: "event" (the data emitted along
 * with the event) and "props" (the current props of this component). If the
 * condition does not pass, the listener is skipped entirely.
 *
 * @see https://symfony.com/bundles/ux-live-component/current/index.html#listeners
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class LiveListener extends LiveAction
{
    private string $eventName;
    private ?string $condition;

    /**
     * @param string $eventName The name of the event to listen to (e.g. "itemUpdated"),
     *                           optionally followed by a client-side condition
     *                           (e.g. "itemUpdated(event.id == props.item)")
     */
    public function __construct(string $eventName)
    {
        [$this->eventName, $this->condition] = self::parseEventName($eventName);
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    /**
     * The raw (client-side) condition expression, or null if none was given.
     */
    public function getCondition(): ?string
    {
        return $this->condition;
    }

    /**
     * @return array{0: string, 1: string|null}
     */
    private static function parseEventName(string $eventName): array
    {
        $eventName = trim($eventName);

        if (!str_contains($eventName, '(')) {
            return [$eventName, null];
        }

        if (!preg_match('/^(?<name>[^(]+)\((?<condition>.*)\)$/s', $eventName, $matches)) {
            throw new \InvalidArgumentException(\sprintf('Invalid LiveListener event name "%s": expected format is "eventName" or "eventName(condition)".', $eventName));
        }

        $condition = trim($matches['condition']);

        return [trim($matches['name']), '' !== $condition ? $condition : null];
    }
}
