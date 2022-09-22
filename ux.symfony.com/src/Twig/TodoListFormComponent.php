<?php

namespace App\Twig;

use App\Entity\TodoList;
use App\Form\TodoListForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent('todo_list_form')]
class TodoListFormComponent extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?TodoList $todoList;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            TodoListForm::class,
            $this->todoList
        );
    }
}
