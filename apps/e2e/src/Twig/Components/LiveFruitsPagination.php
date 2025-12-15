<?php

namespace App\Twig\Components;

use App\Repository\FruitRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Metadata\UrlMapping;

#[AsLiveComponent]
final class LiveFruitsPagination
{
    use DefaultActionTrait;

    #[LiveProp(url: new UrlMapping(mapPath: true))]
    public int $page = 1;

    public function __construct(
        private FruitRepository $fruitRepository,
    )    {
    }

    public function getFruits(): array
    {
        $page = $this->page < 1 ? 1 : $this->page;

        return $this->fruitRepository->paginate(page: $page, perPage: 5);
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function hasNextPage(): bool
    {
        // not very efficient, but good enough for this example
        return \count($this->fruitRepository->paginate(page: $this->page + 1, perPage: 8)) > 0;
    }

    #[LiveAction]
    public function goToPreviousPage(): void
    {
        if ($this->hasPreviousPage()) {
            $this->page--;
        }
    }

    #[LiveAction]
    public function goToNextPage(): void
    {
        if ($this->hasNextPage()) {
            $this->page++;
        }
    }
}
