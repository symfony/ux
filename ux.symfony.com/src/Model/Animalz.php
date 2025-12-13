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

use App\Enum\AnimalzType;

final class Animalz
{
    public function __construct(
        public string $name,
        public AnimalzType $type1,
        public ?AnimalzType $type2,
        public int $legs,
        public string $description,
    ) {
    }
}
