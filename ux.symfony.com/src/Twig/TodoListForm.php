<?php

namespace App\Twig;

use App\Entity\TodoList;
use App\Form\TodoListFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent]
class TodoListForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?TodoList $todoList;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            TodoListFormType::class,
            $this->todoList
        );
    }
}
