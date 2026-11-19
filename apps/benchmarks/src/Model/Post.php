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

final class Post
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $author,
        public readonly PostStatus $status,
        public readonly PostCategory $category,
        public readonly int $views,
        public readonly int $comments,
        public readonly bool $featured,
        public readonly \DateTimeImmutable $publishedAt,
    ) {
    }
}
