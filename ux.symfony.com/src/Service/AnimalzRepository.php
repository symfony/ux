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

use App\Enum\AnimalzType;
use App\Model\Animalz;

class AnimalzRepository
{
    private const string DATA_FILE = __DIR__.'/data/animalz-properties.json';

    /** @var list<Animalz>|null */
    private ?array $zanimalz = null;

    /**
     * @return list<Animalz>
     */
    public function findAll(): array
    {
        return $this->zanimalz ??= $this->loadZanimalz();
    }

    /**
     * @return list<Animalz>
     */
    public function findByNameAndTypeAndLegs(?string $name, ?AnimalzType $type, ?int $maxLegs): array
    {
        $zanimalz = $this->findAll();

        if (null !== $name && '' !== $name) {
            $zanimalz = array_filter(
                $zanimalz,
                fn (Animalz $animalz): bool => str_contains(
                    strtolower($animalz->name),
                    strtolower($name),
                ),
            );
        }

        if (null !== $type) {
            $zanimalz = array_filter(
                $zanimalz,
                fn (Animalz $animalz): bool => $animalz->type1 === $type || $animalz->type2 === $type,
            );
        }

        if (null !== $maxLegs) {
            $zanimalz = array_filter(
                $zanimalz,
                fn (Animalz $animalz): bool => $animalz->legs <= $maxLegs,
            );
        }

        $zanimalz = array_values($zanimalz);
        usort($zanimalz, fn (Animalz $a, Animalz $b): int => $a->name <=> $b->name);

        return $zanimalz;
    }

    public function getMaxLegs(): int
    {
        $zanimalz = $this->findAll();

        return max(array_map(fn (Animalz $a): int => $a->legs, $zanimalz));
    }

    /**
     * @return list<Animalz>
     */
    private function loadZanimalz(): array
    {
        $content = file_get_contents(self::DATA_FILE);

        if (false === $content) {
            return [];
        }

        $data = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);

        return array_map(
            fn (array $item): Animalz => new Animalz(
                name: $item['name'],
                type1: AnimalzType::from($item['type'][0]),
                type2: isset($item['type'][1]) ? AnimalzType::from($item['type'][1]) : null,
                legs: $item['legs'],
                description: $item['description'],
            ),
            $data,
        );
    }
}
