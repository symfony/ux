<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Live;

use App\Data\PostRepository;
use App\Model\PostCategory;
use App\Model\PostPage;
use App\Model\PostSort;
use App\Model\PostStatus;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

#[AsLiveComponent]
final class PostsTable
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    public const PER_PAGE_CHOICES = [10, 25, 50, 100, 250, 500];

    #[LiveProp(writable: true, url: new UrlMapping(as: 'q'), onUpdated: 'resetPagination')]
    public string $query = '';

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPagination')]
    public ?PostStatus $status = null;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPagination')]
    public ?PostCategory $category = null;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPagination')]
    public PostSort $sort = PostSort::Newest;

    #[LiveProp(writable: true, url: true, onUpdated: 'resetPagination')]
    public bool $featured = false;

    #[LiveProp(writable: true, url: true, onUpdated: 'onPerPageUpdated')]
    public int $perPage = PostRepository::PER_PAGE;

    #[LiveProp(url: true)]
    public int $page = 1;

    /**
     * @var list<int>
     */
    #[LiveProp(writable: true)]
    public array $selected = [];

    /**
     * @var list<int>
     */
    #[LiveProp]
    public array $deleted = [];

    private ?PostPage $postPage = null;

    public function __construct(
        private readonly PostRepository $posts,
    ) {
    }

    public function resetPagination(): void
    {
        $this->page = 1;
        $this->selected = [];
        $this->postPage = null;
    }

    public function onPerPageUpdated(int $previous): void
    {
        if ($previous !== $this->perPage) {
            $this->resetPagination();
        }
    }

    public function getPostPage(): PostPage
    {
        $perPage = \in_array($this->perPage, self::PER_PAGE_CHOICES, true) ? $this->perPage : PostRepository::PER_PAGE;

        return $this->postPage ??= $this->posts->paginate(trim($this->query), $this->status, $this->category, $this->sort, $this->featured, $this->page, $perPage, $this->deleted);
    }

    /**
     * @return array<string, int>
     */
    public function getStatusCounts(): array
    {
        return $this->posts->countByStatus($this->deleted);
    }

    /**
     * @return array<string, int>
     */
    public function getCategoryCounts(): array
    {
        return $this->posts->countByCategory($this->deleted);
    }

    #[LiveAction]
    public function goToPage(#[LiveArg] int $page): void
    {
        $this->resetPagination();
        $this->page = max(1, $page);
    }

    #[LiveAction]
    public function bulkDelete(): void
    {
        $selectedIds = array_map('intval', $this->selected);
        $this->deleted = array_values(array_unique([...$this->deleted, ...$selectedIds]));
        $this->selected = [];
        $this->postPage = null;

        $this->emit('posts:changed', ['deleted' => $this->deleted]);
    }
}
