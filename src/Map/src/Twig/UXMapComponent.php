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

use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
final class UXMapComponent
{
    public ?float $zoom;

    public ?Point $center;

    /**
     * @var Marker[]
     */
    public array $markers;
}
