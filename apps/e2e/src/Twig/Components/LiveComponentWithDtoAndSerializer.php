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
final class LiveComponentWithDtoAndSerializer
{
    use DefaultActionTrait;

    #[LiveProp(url: true, useSerializerForHydration: true)]
    public ?Address $address = null;

    #[LiveAction]
    public function initAddress(): void
    {
        $this->address = Address::create(
            country: 'France',
            city: 'Lyon',
        );
    }
}
