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

        match (\count($this->addresses)) {
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
        return \count($this->addresses) < 2;
    }
}
