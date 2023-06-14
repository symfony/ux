<?php

namespace App\Enum;

enum Meal: string
{
    case Breakfast = 'breakfast';
    case SecondBreakfast = 'second_breakfast';
    case Elevenses = 'elevenses';
    case Lunch = 'lunch';
    case Dinner = 'dinner';

    public function getReadable(): string
    {
        return match ($this) {
            self::Breakfast => 'Breakfast',
            self::SecondBreakfast => 'Second Breakfast',
            self::Elevenses => 'Elevenses',
            self::Lunch => 'Lunch',
            self::Dinner => 'Dinner',
        };
    }

    /**
     * @return list<Food>
     */
    public function getFoodChoices(): array
    {
        return match ($this) {
            self::Breakfast => [Food::Eggs, Food::Bacon, Food::Strawberries, Food::Croissant],
            self::SecondBreakfast => [Food::Bagel, Food::Kiwi, Food::Avocado, Food::Waffles],
            self::Elevenses => [Food::Pancakes, Food::Strawberries, Food::Tea],
            self::Lunch => [Food::Sandwich, Food::Cheese, Food::Sushi],
            self::Dinner => [Food::Pizza, Food::Pint, Food::Pasta],
        };
    }
}
