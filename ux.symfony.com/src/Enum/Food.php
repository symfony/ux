<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Enum;

enum Food: string
{
    case Eggs = 'eggs';
    case Bacon = 'bacon';
    case Strawberries = 'strawberries';
    case Croissant = 'croissant';
    case Bagel = 'bagel';
    case Kiwi = 'kiwi';
    case Avocado = 'avocado';
    case Waffles = 'waffles';
    case Pancakes = 'pancakes';
    case Salad = 'salad';
    case Tea = 'tea️';
    case Sandwich = 'sandwich';
    case Cheese = 'cheese';
    case Sushi = 'sushi';
    case Pizza = 'pizza';
    case Pint = 'pint';
    case Pasta = 'pasta';

    public function getReadable(): string
    {
        return match ($this) {
            self::Eggs => 'Eggs 🍳',
            self::Bacon => 'Bacon 🥓',
            self::Strawberries => 'Strawberries 🍓',
            self::Croissant => 'Croissant 🥐',
            self::Bagel => 'Bagel 🥯',
            self::Kiwi => 'Kiwi 🥝',
            self::Avocado => 'Avocado 🥑',
            self::Waffles => 'Waffles 🧇',
            self::Pancakes => 'Pancakes 🥞',
            self::Salad => 'Salad 🥙',
            self::Tea => 'Tea ☕️',
            self::Sandwich => 'Sandwich 🥪',
            self::Cheese => 'Cheese 🧀',
            self::Sushi => 'Sushi 🍱',
            self::Pizza => 'Pizza 🍕',
            self::Pint => 'A Pint 🍺',
            self::Pasta => 'Pasta 🍝',
        };
    }
}
