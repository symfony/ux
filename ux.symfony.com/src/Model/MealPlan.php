<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

use App\Enum\Food;
use App\Enum\Meal;
use App\Enum\PizzaSize;
use Symfony\Component\Validator\Constraints as Assert;

class MealPlan
{
    #[Assert\NotNull]
    private ?Meal $meal = null;

    #[Assert\NotNull]
    private ?Food $mainFood = null;

    #[Assert\When(
        expression: 'this.getMainFood() === enum("App\\\Enum\\\Food::Pizza")',
        constraints: [
            new Assert\NotNull(message: 'Please select a Pizza Size.'),
        ],
    )]
    private ?PizzaSize $pizzaSize = null;

    public function getMeal(): ?Meal
    {
        return $this->meal;
    }

    public function setMeal(?Meal $meal): void
    {
        $this->meal = $meal;
    }

    public function getMainFood(): ?Food
    {
        return $this->mainFood;
    }

    public function setMainFood(?Food $mainFood): void
    {
        $this->mainFood = $mainFood;
    }

    public function getPizzaSize(): ?PizzaSize
    {
        return $this->pizzaSize;
    }

    public function setPizzaSize(?PizzaSize $pizzaSize): void
    {
        $this->pizzaSize = $pizzaSize;
    }
}
