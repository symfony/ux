<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Leafletjs\Twig;

use Symfony\UX\Leafletjs\Model\Map;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 * @author Michael Cramer <michael@bigmichi1.de>
 *
 * @final
 * @experimental
 */
class LeafletExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_map', [$this, 'renderMap'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function renderMap(Environment $env, Map $map, array $attributes = []): string
    {
        $map->setAttributes(array_merge($map->getAttributes(), $attributes));

        $html = '
            <div
                data-controller="'.trim($map->getDataController().' @symfony/ux-leafletjs/map').'"
                data-leafletjs-target="placeholder"
                data-leafletjs-longitude="'.$map->getLon().'"
                data-leafletjs-latitude="'.$map->getLat().'"
                data-leafletjs-zoom="'.$map->getZoom().'"
                data-leafletjs-map-options="'.twig_escape_filter($env, json_encode($map->getMapOptions()), 'html_attr').'"
        ';

        foreach ($map->getAttributes() as $name => $value) {
            if ('data-controller' === $name) {
                continue;
            }

            if (true === $value) {
                $html .= $name.'="'.$name.'" ';
            } elseif (false !== $value) {
                $html .= $name.'="'.$value.'" ';
            }
        }

        return trim($html).'></div>';
    }
}
