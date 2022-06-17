<?php

namespace App\Entity;

use App\Repository\TodoListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Valid;

#[ORM\Entity(repositoryClass: TodoListRepository::class)]
class TodoList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column()]
    private ?int $id = null;

    #[ORM\Column()]
    #[NotBlank]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'todoList', targetEntity: TodoItem::class, orphanRemoval: true, cascade: ['persist'])]
    #[Valid]
    private Collection $todoItems;

    public function __construct()
    {
        $this->todoItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, TodoItem>
     */
    public function getTodoItems(): Collection
    {
        return $this->todoItems;
    }

    public function addTodoItem(TodoItem $todoItem): self
    {
        if (!$this->todoItems->contains($todoItem)) {
            $this->todoItems[] = $todoItem;
            $todoItem->setTodoList($this);
        }

        return $this;
    }

    public function removeTodoItem(TodoItem $todoItem): self
    {
        if ($this->todoItems->removeElement($todoItem)) {
            // set the owning side to null (unless already changed)
            if ($todoItem->getTodoList() === $this) {
                $todoItem->setTodoList(null);
            }
        }

        return $this;
    }
}
