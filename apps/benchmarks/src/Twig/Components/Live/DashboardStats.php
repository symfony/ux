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
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class DashboardStats
{
    use DefaultActionTrait;

    /**
     * @var list<int>
     */
    #[LiveProp]
    public array $deleted = [];

    /**
     * @var array<string, int>|null
     */
    private ?array $statusCounts = null;

    public function __construct(
        private readonly PostRepository $posts,
    ) {
    }

    /**
     * @return array<string, int>
     */
    public function getStatusCounts(): array
    {
        return $this->statusCounts ??= $this->posts->countByStatus($this->deleted);
    }

    public function getTotalPosts(): int
    {
        return array_sum($this->getStatusCounts());
    }

    public function getTotalViews(): int
    {
        return $this->posts->sumViews($this->deleted);
    }

    public function getTotalComments(): int
    {
        return $this->posts->sumComments($this->deleted);
    }

    #[LiveAction]
    public function refresh(): void
    {
    }

    /**
     * @param list<int> $deleted
     */
    #[LiveListener('posts:changed')]
    public function onPostsChanged(#[LiveArg] array $deleted): void
    {
        $this->deleted = $deleted;
        $this->statusCounts = null;
    }
}
