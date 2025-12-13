<?php

namespace App\Twig\Components;

use App\Model\Address;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsLiveComponent]
final class LiveComponentWithDtoCollection
{
    use DefaultActionTrait;

    /**
     * @var Address[]
     */
    #[LiveProp(url: true)]
    public array $addresses = [];

    #[LiveAction]
    public function addAddress(): void
    {
        if (!$this->canAddAddress()) {
            return;
        }

        match(count($this->addresses)) {
            0 => $this->addresses[] = Address::create(
                country: 'France',
                city: 'Lyon',
            ),
            1 => $this->addresses[] = Address::create(
                country: 'South Korea',
                city: 'Seoul',
            ),
            default => null,
        };
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->addresses = [];
    }

    public function canAddAddress(): bool
    {
        return count($this->addresses) < 2;
    }
}
