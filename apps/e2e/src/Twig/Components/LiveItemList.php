<?php

namespace App\Twig\Components;

use App\Model\Address;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsLiveComponent]
final class LiveItemList
{
    use DefaultActionTrait;

    /**
     * @var string[]
     */
    #[LiveProp(writable: true, url: true)]
    public array $items = [];

    #[LiveAction]
    public function addItem(): void
    {
        $this->items[] = '';
    }

    #[LiveAction]
    public function deleteItems(): void
    {
        $this->items = [];
    }

    #[LiveAction]
    public function deleteItem(#[LiveArg] int $key): void
    {
        unset($this->items[$key]);
    }
}
