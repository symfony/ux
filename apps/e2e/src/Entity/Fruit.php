<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\FruitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FruitRepository::class)]
class Fruit
{
    #[ORM\Id]
    #[ORM\Column(length: 64, nullable: false)]
    private string $id;

    #[ORM\Column(length: 64, nullable: false)]
    private string $name;

    public static function create(string $id, string $name): self
    {
        $fruit = new self();
        $fruit->id = $id;
        $fruit->name = $name;

        return $fruit;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
