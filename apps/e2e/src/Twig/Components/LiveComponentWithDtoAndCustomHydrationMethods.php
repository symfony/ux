<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    public ?Address $address = null;

    #[LiveAction]
    public function initAddress(): void
    {
        $this->address = Address::create(
            country: 'France',
            city: 'Lyon',
        );
    }

    public function dehydrateAddress(?Address $address): ?array
    {
        if (null === $address) {
            return null;
        }

        return [
            'x-country' => $address->country,
            'x-city' => $address->city,
        ];
    }

    public function hydrateAddress(?array $data): Address
    {
        $address = new Address();

        if (null !== $data) {
            $address->country = $data['x-country'];
            $address->city = $data['x-city'];
        }

        return $address;
    }
}
