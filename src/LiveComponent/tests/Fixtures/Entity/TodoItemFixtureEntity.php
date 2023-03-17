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
class TodoItemFixtureEntity
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
    private ?string $name = null;

    /**
     * @ORM\ManyToOne(targetEntity=TodoListFixtureEntity::class, inversedBy="todoItems")
     */
    private TodoListFixtureEntity $todoList;

    /**
     * @param string $name
     */
    public function __construct(string $name = null)
    {
        $this->name = $name;
    }

    public function getTodoList(): TodoListFixtureEntity
    {
        return $this->todoList;
    }

    public function setTodoList(?TodoListFixtureEntity $todoList): self
    {
        $this->todoList = $todoList;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name)
    {
        $this->name = $name;
    }
}
