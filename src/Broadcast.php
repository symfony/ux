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

namespace Symfony\UX\Turbo;

/**
 * Marks the entity as broadcastable.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 * @todo add support for advanced SSE options such as ID and retry
 */
#[\Attribute(\Attribute::TARGET_CLASS|\Attribute::IS_REPEATABLE)]
final class Broadcast
{
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_REMOVE = 'remove';

    public function __construct(
        /**
         * Mercure topic, defaults to the Fully Qualified Class Name with "\" characters replaced by ":" characters.
         */
        public ?string $topic = null,
        /**
         * An expression to generate the topic evaluated using the Expression Language component.
         */
        public ?string $topicExpression = null,
        /**
         * The Twig template to use. Defaults to "broadcast/{fully-qualified-class-name}.stream.html.twig) with the "\" characters replaced by ":" characters (subdirectories). Ex: templates/broadcast/App:Entity:Book.stream.html.twig
         */
        public ?string $template = null,
        /**
         * Marks Mercure updates as private.
         */
        public bool $private = false,
    ) {}
}
