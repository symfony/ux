<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Example;
use App\UxPackage;

class ExampleRepository
{
    /**
     * @var list<Example>
     */
    private array $examples;

    public function __construct() {
        $this->examples = [
            new Example(UxPackage::Map, 'Basic map (Leaflet)', 'A basic map centered on Paris with zoom level 12', '/ux-map/basic?renderer=leaflet'),
            new Example(UxPackage::Map, 'Basic map (Google)', 'A basic map centered on Paris with zoom level 12', '/ux-map/basic?renderer=google'),
            new Example(UxPackage::Map, 'With markers, fit bounds (Leaflet)', 'A map with 2 markers, and the bounds are automatically adjusted to fit both markers', '/ux-map/with-markers-and-fit-bounds-to-markers?renderer=leaflet'),
            new Example(UxPackage::Map, 'With markers, fit bounds (Google)', 'A map with 2 markers, and the bounds are automatically adjusted to fit both markers', '/ux-map/with-markers-and-fit-bounds-to-markers?renderer=google'),
            new Example(UxPackage::Map, 'With markers, zoomed on Paris (Leaflet)', 'A map with 2 markers (Paris and Lyon), zoomed on Paris', '/ux-map/with-markers-and-zoomed-on-paris?renderer=leaflet'),
            new Example(UxPackage::Map, 'With markers, zoomed on Paris (Google)', 'A map with 2 markers (Paris and Lyon), zoomed on Paris', '/ux-map/with-markers-and-zoomed-on-paris?renderer=google'),
            new Example(UxPackage::Map, 'With markers and info windows (Leaflet)', 'A map with 2 markers (Paris and Lyon), each with an info window', '/ux-map/with-markers-and-info-windows?renderer=leaflet'),
            new Example(UxPackage::Map, 'With markers and info windows (Google)', 'A map with 2 markers (Paris and Lyon), each with an info window', '/ux-map/with-markers-and-info-windows?renderer=google'),
            new Example(UxPackage::Map, 'With custom icon markers (Leaflet)', 'A map with 3 markers (Paris, Lyon, Bordeaux), each with a custom icon', '/ux-map/with-markers-and-custom-icons?renderer=leaflet'),
            new Example(UxPackage::Map, 'With custom icon markers (Google)', 'A map with 3 markers (Paris, Lyon, Bordeaux), each with a custom icon', '/ux-map/with-markers-and-custom-icons?renderer=google'),
            new Example(UxPackage::Map, 'With polygons (Leaflet)', 'A map with two polygons, one that covers main cities in Italy, and one weird shape on France', '/ux-map/with-polygons?renderer=leaflet'),
            new Example(UxPackage::Map, 'With polygons (Google)', 'A map with two polygons, one that covers main cities in Italy, and one weird shape on France', '/ux-map/with-polygons?renderer=google'),
            new Example(UxPackage::Map, 'With polylines (Leaflet)', 'A map with two polylines: one through Paris/Lyon/Marseille/Bordeaux, and the other one through Rennes/Nantes/Tours', '/ux-map/with-polylines?renderer=leaflet'),
            new Example(UxPackage::Map, 'With polylines (Google)', 'A map with two polylines: one through Paris/Lyon/Marseille/Bordeaux, and the other one through Rennes/Nantes/Tours', '/ux-map/with-polylines?renderer=google'),
            new Example(UxPackage::Map, 'With circles (Leaflet)', 'A map with two circles: one centered on Paris, the other on Lyon', '/ux-map/with-circles?renderer=leaflet'),
            new Example(UxPackage::Map, 'With circles (Google)', 'A map with two circles: one centered on Paris, the other on Lyon', '/ux-map/with-circles?renderer=google'),
            new Example(UxPackage::Map, 'With rectangles (Leaflet)', 'A map with two rectangles: one from Paris to Lille, the other from Lyon to Bordeaux', '/ux-map/with-rectangles?renderer=leaflet'),
            new Example(UxPackage::Map, 'With rectangles (Google)', 'A map with two rectangles: one from Paris to Lille, the other from Lyon to Bordeaux', '/ux-map/with-rectangles?renderer=google'),
            new Example(UxPackage::React, 'Basic React Component', 'A basic React component that displays a welcoming message', '/ux-react/'),
            new Example(UxPackage::Svelte, 'Basic Svelte Component', 'A basic Svelte component that displays a welcoming message', '/ux-svelte/'),
           new Example(UxPackage::Translator, 'Basic translation', 'A simple translation example using the Translator component', '/ux-translator/basic'),
           new Example(UxPackage::Translator, 'Translation with parameter', 'Translation example with dynamic parameters', '/ux-translator/with-parameter'),
           new Example(UxPackage::Translator, 'ICU Translation with `select` argument', 'ICU message format example using the `select` argument', '/ux-translator/icu-select'),
           new Example(UxPackage::Translator, 'ICU Translation with `plural` argument', 'ICU message format example using the `plural` argument', '/ux-translator/icu-plural'),
           new Example(UxPackage::Translator, 'ICU Translation with `selectordinal` argument', 'ICU message format example using the `selectordinal` argument', '/ux-translator/icu-selectordinal'),
           new Example(UxPackage::Translator, 'ICU Translation with `date` and `time` arguments', 'ICU message format example using `date` and `time` arguments', '/ux-translator/icu-date-time'),
           new Example(UxPackage::Translator, 'ICU Translation with `number` and `percent` arguments', 'ICU message format example using `number` and `percent` arguments', '/ux-translator/icu-number-percent'),
           new Example(UxPackage::Translator, 'ICU Translation with `number` and `currency` arguments', 'ICU message format example using `number` and `currency` arguments', '/ux-translator/icu-number-currency'),
            new Example(UxPackage::Vue, 'Basic Vue Component', 'A basic Vue component that displays a welcoming message', '/ux-vue/'),
        ];
    }

    /**
     * @return list<Example>
     */
    public function findAll(): array
    {
        return $this->examples;
    }

    public function findAllByPackage(): array
    {
        $grouped = [];

        foreach ($this->examples as $example) {
            $grouped[$example->uxPackage->value][] = $example;
        }

        return $grouped;
    }

    public function findOneByUrl(string $url): ?Example
    {
        foreach ($this->examples as $example) {
            if ($example->url === $url) {
                return $example;
            }
        }

        return null;
    }
}
