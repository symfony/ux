<?php

namespace App\Twig\Components;

use App\Model\Address;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsLiveComponent]
final class LiveComponentWithDto
{
    use DefaultActionTrait;

    #[LiveProp(url: true, writable: ['country', 'city'])]
    public Address $address;

    #[PreMount()]
    public function preMount(array $data): array
    {
        $this->address = Address::create(
            country: 'France',
            city: 'Lyon',
        );

        return $data;
    }
}
