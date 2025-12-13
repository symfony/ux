<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LiveCounter
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public int $value = 0;

    #[LiveAction]
    public function decrement(): void
    {
        $this->value -= 1;
    }

    #[LiveAction]
    public function increment(): void
    {
        $this->value += 1;
    }
}
