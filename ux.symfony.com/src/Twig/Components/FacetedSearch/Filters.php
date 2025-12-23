<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\FacetedSearch;

use App\Enum\AnimalColor;
use App\Enum\AnimalHabitat;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'FacetedSearch:Filters', template: 'components/FacetedSearch/Filters.html.twig')]
final class Filters
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public ?string $name = null;

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public ?AnimalColor $color = null;

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public array $habitats = [];

    #[LiveProp(writable: true, onUpdated: 'emitChange', url: true)]
    public ?int $maxLegs = null;

    #[LiveAction]
    public function reset(): void
    {
        $this->name = null;
        $this->habitats = [];
        $this->color = null;
        $this->maxLegs = null;

        $this->emitChange();
    }

    public function hasFilters(): bool
    {
        return (null !== $this->name || null !== $this->color || [] !== $this->habitats || null !== $this->maxLegs);
    }

    public function emitChange(): void
    {
        $this->emit('FacetedSearch:Filters:Updated', [
            'name' => $this->name,
            'color' =>  $this->color,
            'habitats' => $this->habitats,
            'maxLegs' => $this->maxLegs,
        ]);
    }

    /**
     * @return list<AnimalHabitat>
     */
    public function getHabitatChoices(): array
    {
        return AnimalHabitat::cases();
    }

    /**
     * @return list<AnimalColor>
     */
    public function getColorChoices(): array
    {
        return AnimalColor::cases();
    }

    /**
     * @return array{min: 0, max: positive-int}>
     */
    public function getLegsRange(): array
    {
        return ['min' => 0, 'max' => 100];
    }
}
