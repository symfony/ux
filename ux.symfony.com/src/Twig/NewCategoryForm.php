<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveResponder;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent]
class NewCategoryForm
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    #[NotBlank]
    public string $name = '';

    #[LiveAction]
    public function saveCategory(EntityManagerInterface $entityManager, LiveResponder $liveResponder): void
    {
        $this->validate();

        $category = new Category();
        $category->setName($this->name);
        $entityManager->persist($category);
        $entityManager->flush();

        $this->dispatchBrowserEvent('modal:close');
        $this->emit('category:created', [
            'category' => $category->getId(),
        ]);

        // reset the fields in case the modal is opened again
        $this->name = '';
        $this->resetValidation();
    }
}
