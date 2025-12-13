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
use App\Model\Animalz;
use App\Service\AnimalzRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'Animalz:Results', template: 'components/Animalz/Results.html.twig')]
final class Results
{
    use DefaultActionTrait;

    #[LiveProp(url: true)]
    public ?string $name = null;

    #[LiveProp(url: true)]
    public ?AnimalzType $type = null;

    #[LiveProp(url: true)]
    public ?int $maxLegs = null;

    public function __construct(
        private readonly AnimalzRepository $animalzRepository,
    ) {
    }

    #[LiveListener('facetSetted')]
    public function reload(
        #[LiveArg]
        ?string $name,
        #[LiveArg]
        ?string $type,
        #[LiveArg]
        ?int $maxLegs,
    ): void {
        $this->name = $name;
        $this->type = null !== $type ? AnimalzType::tryFrom($type) : null;
        $this->maxLegs = $maxLegs;
    }

    /** @return Animalz[] */
    public function getResults(): array
    {
        return $this->animalzRepository->findByNameAndTypeAndLegs($this->name, $this->type, $this->maxLegs);
    }
}
