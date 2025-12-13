<?php

namespace App\Twig\Components;

use App\Model\Address;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class LiveComponentWithDtoAndCustomHydrationMethods
{
    use DefaultActionTrait;

    #[LiveProp(url: true, hydrateWith: 'hydrateAddress', dehydrateWith: 'dehydrateAddress')]
    public Address|null $address = null;

    #[LiveAction]
    public function initAddress(): void
    {
        $this->address = Address::create(
            country: 'France',
            city: 'Lyon',
        );
    }

    public function dehydrateAddress(Address|null $address): array|null
    {
        if (null === $address) {
            return null;
        }

        return [
            'x-country' => $address->country,
            'x-city' => $address->city
        ];
    }

    public function hydrateAddress(array|null $data): Address
    {
        $address = new Address();

        if (null !== $data) {
            $address->country = $data['x-country'];
            $address->city = $data['x-city'];
        }

        return $address;
    }
}
