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

trigger_deprecation('symfony/ux-turbo', '3.1', 'The "%s" class is deprecated since Symfony UX 3.1 and will be removed in 4.0.', TopicSet::class);

/**
 * @internal
 *
 * @deprecated since Symfony UX 3.1, will be removed in 4.0.
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
