<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Icon;

use App\Model\Icon\IconSet;

class IconSetRepository
{
    private const FAVORITE_SETS = [
        'ri',
        'flowbite',
        'tabler',
        'bi',
        'lucide',
        'iconoir',
        'bx',
        'octoicons',
        'iconoir',
        'bootstrap',
    ];

    private array $iconSets;

    private array $terms = [
        'crypto',
    ];

    public function __construct(
        private Iconify $iconify,
    ) {
    }

    /**
     * @return array<IconSet>
     */
    public function findAll(?int $limit = null, ?int $offset = null): array
    {
        if (!isset($this->iconSets)) {
            $iconSets = [];
            foreach ($this->iconify->collections() as $identifier => $data) {
                $iconSets[$identifier] = self::createIconSet($identifier, $data);
            }
            $this->iconSets = $iconSets;
        }

        return array_slice($this->iconSets, $offset ?? 0, $limit);
    }

    public function load(string $identifier): IconSet
    {
        return self::createIconSet($identifier, $this->iconify->collection($identifier));
    }

    public function get(string $identifier): IconSet
    {
        return $this->find($identifier) ?? throw new \InvalidArgumentException(sprintf('Unknown icon set "%s"', $identifier));
    }

    public function find(string $identifier): ?IconSet
    {
        $iconSets = $this->findAll();
        foreach ($iconSets as $iconSet) {
            if ($iconSet->getIdentifier() === $identifier) {
                return $iconSet;
            }
        }

        return null;
    }

    private static function createIconSet(string $identifier, array $data): IconSet
    {
        return new IconSet(
            $identifier,
            $data['name'],
            $data['author'],
            $data['license'],
            $data['total'] ?? null,
            $data['version'] ?? null,
            $data['samples'] ?? [],
            $data['height'] ?? null,
            $data['displayHeight'] ?? null,
            $data['category'] ?? null,
            $data['tags'] ?? [],
            $data['palette'] ?? null,
            $data['suffixes'] ?? null,
            $data['categories'] ?? null,
            in_array($identifier, self::FAVORITE_SETS, true),
        );
    }
}
