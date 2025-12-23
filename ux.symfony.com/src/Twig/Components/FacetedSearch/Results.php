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
use App\Model\Animal;
use App\Service\AnimalRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'FacetedSearch:Results', template: 'components/FacetedSearch/Results.html.twig')]
final class Results
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public ?string $name = null;

    #[LiveProp(url: true)]
    public array $habitats = [];

    #[LiveProp(url: true)]
    public ?AnimalColor $color = null;

    #[LiveProp(url: true)]
    public ?int $maxLegs = null;

    private array $results;

    public function __construct(
        private readonly AnimalRepository $animalzRepository,
    ) {
    }

    #[LiveListener('FacetedSearch:Filters:Updated')]
    public function reload(
        #[LiveArg]
        ?string $name = null,
        #[LiveArg]
        array $habitats = [],
        #[LiveArg]
        ?string $color = null,
        #[LiveArg]
        ?int $maxLegs = null,
    ): void {
        $this->name = $name;
        $this->habitats = $habitats;
        $this->color = (null !== $color) ? AnimalColor::tryFrom($color) : null;
        $this->maxLegs = $maxLegs;
    }

    /**
     * Returns the animals matching the facet values.
     *
     * @return Animal[]
     */
    public function getResults(): array
    {
        return $this->results ??= $this->animalzRepository->search(
            name: $this->name,
            habitats: $this->habitats,
            color: $this->color,
            maxLegs: $this->maxLegs,
        );
    }
}
