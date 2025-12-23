<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Enum\AnimalColor;
use App\Enum\AnimalHabitat;
use App\Model\Animal;

final class AnimalRepository
{
    private const string DATA_FILE = __DIR__.'/data/animals.json';

    private array $animal;

    /**
     * @return list<Animal>
     */
    public function search(
        ?string $name,
        array $habitats,
        ?AnimalColor $color,
        ?int $maxLegs,
    ): array
    {
        $animals = $this->findAll();

        if (null !== $name && '' !== $name) {
            $animals = array_filter($animals, fn (Animal $animal): bool => false !== stripos($animal->name, $name));
        }

        if ([] !== $habitats) {
            $animals = array_filter($animals, function (Animal $animal) use ($habitats): bool {
                $animalHabitats = array_map(fn (AnimalHabitat $h) => $h->value, $animal->habitats);

                return [] !== array_intersect($habitats, $animalHabitats);
            });
        }

        if (null !== $color) {
            $animals = array_filter($animals, fn (Animal $animal): bool => in_array($color, $animal->colors));
        }

        if (null !== $maxLegs) {
            $animals = array_filter($animals, fn (Animal $animal): bool => $animal->legs <= $maxLegs);
        }

        $animals = array_values($animals);
        usort($animals, fn (Animal $a, Animal $b): int => $a->name <=> $b->name);

        return $animals;
    }

    /**
     * @return list<Animal>
     */
    public function findAll(): array
    {
        return $this->animal ??= $this->loadAnimal();
    }

    /**
     * @return list<Animal>
     */
    private function loadAnimal(): array
    {
        //
        $content = file_get_contents(self::DATA_FILE);

        if (false === $content) {
            return [];
        }

        /**
         * @var list<array{name: string, emoji: string, habitat: string|string[], color: string|string[], legs: int}> $data
         */
        $data = \json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return array_map(
            fn (array $item): Animal => new Animal(
                name: $item['name'],
                photo: $item['emoji'],
                habitats: array_map(AnimalHabitat::from(...), (array) $item['habitat']),
                colors: array_map(AnimalColor::from(...), (array) $item['colors']),
                legs: $item['legs'],
            ),
            $data,
        );
    }
}
