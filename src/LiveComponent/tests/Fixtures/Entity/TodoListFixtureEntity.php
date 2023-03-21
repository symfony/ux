<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class TodoListFixtureEntity
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public string $listTitle = '';

    /**
     * @ORM\OneToMany(targetEntity=TodoItemFixtureEntity::class, mappedBy="todoList")
     */
    private Collection $todoItems;

    public function __construct(string $listTitle = '')
    {
        $this->listTitle = $listTitle;
        $this->todoItems = new ArrayCollection();
    }

    public function getTodoItems(): Collection
    {
        return $this->todoItems;
    }

    public function addTodoItem(TodoItemFixtureEntity $todoItem): self
    {
        if (!$this->todoItems->contains($todoItem)) {
            $this->todoItems[] = $todoItem;
            $todoItem->setTodoList($this);
        }

        return $this;
    }

    public function removeTodoItem(TodoItemFixtureEntity $todoItem): self
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
