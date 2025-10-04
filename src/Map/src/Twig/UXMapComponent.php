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

use Symfony\UX\Map\Circle;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Polygon;
use Symfony\UX\Map\Polyline;
use Symfony\UX\Map\Rectangle;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class UXMapComponent
{
    public ?float $zoom;

    public ?float $minZoom;

    public ?float $maxZoom;

    public ?Point $center;

    public ?bool $fitBoundsToMarkers;

    /**
     * @var Marker[]
     */
    public array $markers;

    /**
     * @var Polygon[]
     */
    public array $polygons;

    /**
     * @var Polyline[]
     */
    public array $polylines;

    /**
     * @var Circle[]
     */
    public array $circles;

    /**
     * @var Rectangle[]
     */
    public array $rectangles;
}
