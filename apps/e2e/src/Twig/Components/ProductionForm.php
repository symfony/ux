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

use App\Form\Model\ProductionDto;
use App\Form\Type\ProductionType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ProductionForm extends AbstractController
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public ?ProductionDto $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(ProductionType::class, $this->initialFormData ?? new ProductionDto());
    }

    #[LiveListener('movie-selected')]
    public function onMovieSelected(#[LiveArg] string $title): void
    {
        $this->formValues['title'] = $title;
    }

    #[LiveListener('videogame-selected')]
    public function onVideogameSelected(#[LiveArg] string $title): void
    {
        $this->formValues['title'] = $title;
    }
}
