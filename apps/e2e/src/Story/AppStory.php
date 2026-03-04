<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Story;

use App\Factory\FruitFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        FruitFactory::createSequence([
            ['id' => 'apple', 'name' => 'Apple'],
            ['id' => 'banana', 'name' => 'Banana'],
            ['id' => 'cherry', 'name' => 'Cherry'],
            ['id' => 'coconut', 'name' => 'Coconut'],
            ['id' => 'grape', 'name' => 'Grape'],
            ['id' => 'kiwi', 'name' => 'Kiwi'],
            ['id' => 'lemon', 'name' => 'Lemon'],
            ['id' => 'mango', 'name' => 'Mango'],
            ['id' => 'orange', 'name' => 'Orange'],
            ['id' => 'papaya', 'name' => 'Papaya'],
            ['id' => 'peach', 'name' => 'Peach'],
            ['id' => 'pineapple', 'name' => 'Pineapple'],
            ['id' => 'pear', 'name' => 'Pear'],
            ['id' => 'pomegranate', 'name' => 'Pomegranate'],
            ['id' => 'pomelo', 'name' => 'Pomelo'],
            ['id' => 'raspberry', 'name' => 'Raspberry'],
            ['id' => 'strawberry', 'name' => 'Strawberry'],
            ['id' => 'watermelon', 'name' => 'Watermelon'],
        ]);
    }
}
