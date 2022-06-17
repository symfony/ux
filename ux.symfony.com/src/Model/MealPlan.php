<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints\NotBlank;

class MealPlan
{
    #[NotBlank]
    private ?string $meal = null;

    #[NotBlank]
    private ?string $mainFood = null;

    public function getMeal(): ?string
    {
        return $this->meal;
    }

    public function setMeal(?string $meal): void
    {
        $this->meal = $meal;
    }

    public function getMainFood(): ?string
    {
        return $this->mainFood;
    }

    public function setMainFood(?string $mainFood): void
    {
        $this->mainFood = $mainFood;
    }
}
