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

use App\Enum\AnimalColor;
use App\Enum\AnimalHabitat;

readonly final class Animal
{
    /**
     * @param non-empty-list<AnimalHabitat> $habitats
     * @param non-empty-list<AnimalColor> $colors
     */
    public function __construct(
        public string $name,
        public string $photo,
        public array $habitats,
        public array $colors,
        public int $legs,
    ) {
    }

    public function getColor(): AnimalColor
    {
        return $this->colors[0];
    }
}
