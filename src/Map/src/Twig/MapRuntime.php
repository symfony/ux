<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Twig;

use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Renderer\RendererInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class MapRuntime implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly RendererInterface $renderer,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $markers
     * @param array<string, mixed> $polygons
     */
    public function renderMap(
        ?Map $map = null,
        array $attributes = [],
        ?array $markers = null,
        ?array $polygons = null,
        ?array $center = null,
        ?float $zoom = null,
    ): string {
        if ($map instanceof Map) {
            if (null !== $center || null !== $zoom || $markers) {
                throw new \InvalidArgumentException('You cannot set "center", "markers" or "zoom" on an existing Map.');
            }

            return $this->renderer->renderMap($map, $attributes);
        }

        $map = new Map();
        foreach ($markers ?? [] as $marker) {
            $map->addMarker(Marker::fromArray($marker));
        }
        foreach ($polygons ?? [] as $polygons) {
            $map->addPolygon(Polygon::fromArray($polygons));
        }
        if (null !== $center) {
            $map->center(Point::fromArray($center));
        }
        if (null !== $zoom) {
            $map->zoom($zoom);
        }

        return $this->renderer->renderMap($map, $attributes);
    }

    public function render(array $args = []): string
    {
        $map = array_intersect_key($args, ['map' => 0, 'markers' => 0, 'polygons' => 0, 'center' => 1, 'zoom' => 2]);
        $attributes = array_diff_key($args, $map);

        return $this->renderMap(...$map, attributes: $attributes);
    }
}
