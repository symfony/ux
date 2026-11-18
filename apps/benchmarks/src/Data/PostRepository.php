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

use App\Model\Post;
use App\Model\PostCategory;
use App\Model\PostPage;
use App\Model\PostSort;
use App\Model\PostStatus;

final class PostRepository
{
    public const TOTAL = 1_000;
    public const PER_PAGE = 100;

    private const WORDS = ['Building', 'Profiling', 'Caching', 'Testing', 'Shipping', 'Debugging', 'Refactoring'];
    private const TOPICS = ['live components', 'Twig templates', 'Stimulus controllers', 'asset mapping', 'form themes', 'icon sets'];
    private const AUTHORS = ['Ada Lovelace', 'Grace Hopper', 'Alan Turing', 'Margaret Hamilton', 'Linus Torvalds'];

    /**
     * @var list<Post>|null
     */
    private ?array $posts = null;

    /**
     * @param list<int> $deleted
     */
    public function paginate(string $query, ?PostStatus $status, ?PostCategory $category, PostSort $sort, bool $featuredOnly, int $page, int $perPage, array $deleted = []): PostPage
    {
        $needle = mb_strtolower($query);
        $matches = static fn (Post $post): bool => (null === $status || $status === $post->status)
            && (null === $category || $category === $post->category)
            && (!$featuredOnly || $post->featured)
            && ('' === $needle || str_contains(mb_strtolower($post->title), $needle) || str_contains(mb_strtolower($post->author), $needle));
        $posts = array_values(array_filter($this->alive($deleted), $matches));

        usort($posts, static fn (Post $a, Post $b): int => match ($sort) {
            PostSort::Newest => [$b->publishedAt, $b->id] <=> [$a->publishedAt, $a->id],
            PostSort::Oldest => [$a->publishedAt, $a->id] <=> [$b->publishedAt, $b->id],
            PostSort::MostViewed => [$b->views, $a->id] <=> [$a->views, $b->id],
            PostSort::MostCommented => [$b->comments, $a->id] <=> [$a->comments, $b->id],
            PostSort::Title => [$a->title, $a->id] <=> [$b->title, $b->id],
        });

        $total = \count($posts);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $lastPage);
        $items = \array_slice($posts, ($page - 1) * $perPage, $perPage);

        return new PostPage($items, $total, $page, $perPage);
    }

    public function find(int $id): Post
    {
        $posts = $this->all();
        if (!isset($posts[$id - 1])) {
            throw new \InvalidArgumentException(\sprintf('Post %d does not exist.', $id));
        }

        return $posts[$id - 1];
    }

    /**
     * @param list<int> $deleted
     *
     * @return array<string, int>
     */
    public function countByStatus(array $deleted = []): array
    {
        $counts = array_fill_keys(array_map(static fn (PostStatus $status): string => $status->value, PostStatus::cases()), 0);
        foreach ($this->alive($deleted) as $post) {
            ++$counts[$post->status->value];
        }

        return $counts;
    }

    /**
     * @param list<int> $deleted
     *
     * @return array<string, int>
     */
    public function countByCategory(array $deleted = []): array
    {
        $counts = array_fill_keys(array_map(static fn (PostCategory $category): string => $category->value, PostCategory::cases()), 0);
        foreach ($this->alive($deleted) as $post) {
            ++$counts[$post->category->value];
        }

        return $counts;
    }

    /**
     * @param list<int> $deleted
     */
    public function sumViews(array $deleted = []): int
    {
        return array_sum(array_map(static fn (Post $post): int => $post->views, $this->alive($deleted)));
    }

    /**
     * @param list<int> $deleted
     */
    public function sumComments(array $deleted = []): int
    {
        return array_sum(array_map(static fn (Post $post): int => $post->comments, $this->alive($deleted)));
    }

    /**
     * @param list<int> $deleted
     *
     * @return list<Post>
     */
    private function alive(array $deleted): array
    {
        if ([] === $deleted) {
            return $this->all();
        }

        $deletedIds = array_flip($deleted);
        $isAlive = static fn (Post $post): bool => !isset($deletedIds[$post->id]);

        return array_values(array_filter($this->all(), $isAlive));
    }

    /**
     * @return list<Post>
     */
    private function all(): array
    {
        if (null !== $this->posts) {
            return $this->posts;
        }

        $statuses = PostStatus::cases();
        $categories = PostCategory::cases();
        $start = new \DateTimeImmutable('2026-01-01');
        $posts = [];
        for ($i = 1; $i <= self::TOTAL; ++$i) {
            $topic = 0 === $i % 7 ? 'symfony UX' : self::TOPICS[$i % \count(self::TOPICS)];
            $posts[] = new Post(
                id: $i,
                title: \sprintf('%s %d: %s', self::WORDS[$i % \count(self::WORDS)], $i, $topic),
                author: self::AUTHORS[$i % \count(self::AUTHORS)],
                status: $statuses[$i % \count($statuses)],
                category: $categories[$i % \count($categories)],
                views: ($i * 37) % 5000,
                comments: ($i * 11) % 100,
                featured: 0 === $i % 5,
                publishedAt: $start->modify(\sprintf('+%d hours', $i * 7)),
            );
        }

        return $this->posts = $posts;
    }
}
