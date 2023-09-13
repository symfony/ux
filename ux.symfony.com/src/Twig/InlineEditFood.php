<?php

namespace App\Twig;

use App\Entity\Food;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent]
class InlineEditFood extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    /** This allows us to have a data-model="food.name" */
    #[LiveProp(writable: ['name'])]
    /** When we validate, we want to also validate the Food object */
    #[Assert\Valid]
    public Food $food;

    /** Tracks whether the component is in "edit" mode or not */
    #[LiveProp]
    public bool $isEditing = false;

    /**
     * A temporary message to show to the user.
     *
     * This is purposely not a LiveProp: this is a "temporary" value that
     * will only show one time.
     */
    public ?string $flashMessage = null;

    #[LiveAction]
    public function activateEditing()
    {
        $this->isEditing = true;
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager)
    {
        // if validation fails, this throws an exception & the component re-renders
        $this->validate();

        $this->isEditing = false;
        $this->flashMessage = 'Saved! Well, not actually because this is a demo (if you refresh, the value will go back).';

        // in a real app, we would save!
        // $entityManager->flush();
    }
}
