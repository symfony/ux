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

/**
 * @internal
 */
final class TopicSet
{
    /**
     * @param array<string|object> $topics
     */
    public function __construct(
        private array $topics,
    ) {
    }

    /**
     * @return array<string|object>
     */
    public function getTopics(): array
    {
        return $this->topics;
    }
}
