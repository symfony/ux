<?php

namespace App\Twig;

use App\Entity\TodoList;
use App\Form\TodoListForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('todo_list_form')]
class TodoListFormComponent extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp(fieldName: 'formData')]
    public ?TodoList $todoList;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            TodoListForm::class,
            $this->todoList
        );
    }

    #[LiveAction]
    public function addItem(): void
    {
        $this->formValues['todoItems'][] = [];
    }

    #[LiveAction]
    public function removeItem(#[LiveArg] int $index): void
    {
        unset($this->formValues['todoItems'][$index]);
    }
}
