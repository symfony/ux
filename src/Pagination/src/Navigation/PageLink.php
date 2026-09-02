<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Pagination\Navigation;

/**
 * A single link in a pagination navigation bar.
 *
 * Represents either a numbered page link or a gap (ellipsis).
 *
 * @author Simon André <smn.andre@gmail.com>
 */
final class PageLink
{
    public function __construct(
        public readonly int $page,
        public readonly string $url,
        public readonly bool $isCurrent,
        public readonly bool $isGap,
    ) {
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function isCurrent(): bool
    {
        return $this->isCurrent;
    }

    public function isGap(): bool
    {
        return $this->isGap;
    }
}
