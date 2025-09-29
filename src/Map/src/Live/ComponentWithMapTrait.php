<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Live;

use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\Map\Map;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Symfony\UX\TwigComponent\Attribute\PostMount;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 */
trait ComponentWithMapTrait
{
    /**
     * @internal
     */
    #[LiveProp(hydrateWith: 'hydrateMap', dehydrateWith: 'dehydrateMap')]
    #[ExposeInTemplate(getter: 'getMap')]
    public ?Map $map = null;

    abstract protected function instantiateMap(): Map;

    public function getMap(): Map
    {
        return $this->map ??= $this->instantiateMap();
    }

    /**
     * @internal
     */
    #[PostMount]
    public function initializeMap(array $data): array
    {
        // allow the Map object to be passed into the component() as "map"
        if (\array_key_exists('map', $data)) {
            $this->map = $data['map'];
            unset($data['map']);
        }

        return $data;
    }

    /**
     * @internal
     */
    public function hydrateMap(array $data): Map
    {
        return Map::fromArray($data);
    }

    /**
     * @internal
     */
    public function dehydrateMap(Map $map): array
    {
        return $map->toArray();
    }
}
