<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

final class PostPage
{
    /**
     * @param list<Post> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {
    }

    public function pageCount(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    public function firstIndex(): int
    {
        return 0 === $this->total ? 0 : ($this->page - 1) * $this->perPage + 1;
    }

    public function lastIndex(): int
    {
        return min($this->total, $this->page * $this->perPage);
    }

    /**
     * @return list<int|null>
     */
    public function window(int $around = 1): array
    {
        $last = $this->pageCount();
        $pages = [];
        $previous = 0;
        foreach (range(1, $last) as $page) {
            if (1 !== $page && $last !== $page && abs($page - $this->page) > $around) {
                continue;
            }
            if ($page - $previous > 1) {
                $pages[] = null;
            }
            $pages[] = $page;
            $previous = $page;
        }

        return $pages;
    }
}
