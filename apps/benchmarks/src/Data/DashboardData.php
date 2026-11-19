<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Data;

final class DashboardData
{
    public const CSS_ROWS = 25;
    public const TWIG_COMPONENT_ROWS = 500;

    private const STATUSES = ['published', 'draft', 'archived'];
    private const AUTHORS = ['Ada Lovelace', 'Grace Hopper', 'Alan Turing', 'Margaret Hamilton', 'Linus Torvalds'];

    /**
     * @return array<string, mixed>
     */
    public function forRows(int $rows): array
    {
        $posts = [];
        for ($i = 1; $i <= $rows; ++$i) {
            $author = self::AUTHORS[$i % \count(self::AUTHORS)];
            $words = explode(' ', $author);
            $posts[] = [
                'title' => \sprintf('Post number %d about Symfony UX', $i),
                'author' => $author,
                'initials' => implode('', array_map(static fn (string $word): string => $word[0], $words)),
                'status' => self::STATUSES[$i % \count(self::STATUSES)],
            ];
        }

        return [
            'stats' => [
                ['label' => 'Total posts', 'value' => '1,284', 'trend' => '+12%', 'note' => 'Since last month'],
                ['label' => 'Published', 'value' => '942', 'trend' => '+8%', 'note' => 'Live on the site'],
                ['label' => 'Drafts', 'value' => '231', 'trend' => '-3%', 'note' => 'Waiting for review'],
                ['label' => 'Views', 'value' => '48.2k', 'trend' => '+21%', 'note' => 'Over the last 30 days'],
            ],
            'posts' => $posts,
            'fields' => [
                ['id' => 'title', 'label' => 'Title', 'placeholder' => 'A great title'],
                ['id' => 'slug', 'label' => 'Slug', 'placeholder' => 'a-great-title'],
                ['id' => 'category', 'label' => 'Category', 'placeholder' => 'News'],
                ['id' => 'tags', 'label' => 'Tags', 'placeholder' => 'symfony, ux'],
            ],
        ];
    }
}
