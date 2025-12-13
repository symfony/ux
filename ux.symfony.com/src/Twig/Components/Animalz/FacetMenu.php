<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Animalz;

use App\Enum\AnimalzType;
use App\Service\AnimalzRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'Animalz:FacetMenu', template: 'components/Animalz/FacetMenu.html.twig')]
final class FacetMenu
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    private const array EMITTABLE_PROPS = ['name', 'type', 'maxLegs'];

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public ?string $name = null;

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public ?AnimalzType $type = null;

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public ?int $maxLegs = null;

    public function __construct(
        private readonly AnimalzRepository $animalzRepository,
    ) {
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->name = null;
        $this->type = null;
        $this->maxLegs = null;

        $this->emitChange();
    }

    public function emitChange(): void
    {
        $this->nullTheProps();
        $this->emit('facetSetted', $this->convertPropertiesToAssociativeArray());
    }

    /** @return list<AnimalzType> */
    public function getTypeChoices(): array
    {
        return AnimalzType::cases();
    }

    public function getLegsMax(): int
    {
        return $this->animalzRepository->getMaxLegs();
    }

    /** @return array<string, mixed> */
    private function convertPropertiesToAssociativeArray(): array
    {
        $props = array_intersect_key(
            get_object_vars($this),
            array_flip(self::EMITTABLE_PROPS),
        );

        if ($props['type'] instanceof AnimalzType) {
            $props['type'] = $props['type']->value;
        }

        return $props;
    }

    private function nullTheProps(): void
    {
        $this->name = '' === $this->name ? null : $this->name;
    }
}
