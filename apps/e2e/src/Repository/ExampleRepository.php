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

    public function __construct()
    {
        $this->examples = [
            new Example(UxPackage::Autocomplete, 'Autocomplete (without AJAX)', 'An autocomplete form field, by using the choses from the choice type field.', 'app_ux_autocomplete_without_ajax'),
            new Example(UxPackage::Autocomplete, 'Autocomplete (custom controller)', 'An autocomplete form field, with a custom Stimulus controller for AJAX results.', 'app_ux_autocomplete_custom_controller'),
            new Example(UxPackage::ChartJs, 'Line chart without options', 'A basic line chart displaying monthly data without additional options.', 'app_ux_chartjs_without_options'),
            new Example(UxPackage::ChartJs, 'Line chart with options', 'A line chart with custom options (showLines: false) that displays data points without connecting lines.', 'app_ux_chartjs_with_options'),
            new Example(UxPackage::ChartJs, 'Pie chart', 'A pie chart displaying data distribution across different categories.', 'app_ux_chartjs_pie'),
            new Example(UxPackage::ChartJs, 'Pie chart with options', 'A pie chart with custom options to control the appearance and behavior.', 'app_ux_chartjs_pie_with_options'),
            new Example(UxPackage::LiveComponent, 'Examples filtering', "On this page, you can filter all examples by query terms, and observe how the UI and URLs update during and after processing.", 'app_home'),
            new Example(UxPackage::LiveComponent, 'Counter', 'A basic counter that you can increment or decrement.', 'app_ux_live_component_counter'),
            new Example(UxPackage::Turbo, 'Turbo Drive navigation', 'Navigate between pages without full page reload using Turbo Drive.', 'app_ux_turbo_drive'),
            new Example(UxPackage::Turbo, 'Turbo Frame', 'A scoped section that navigates independently from the rest of the page.', 'app_ux_turbo_frame'),
            new Example(UxPackage::Turbo, 'Turbo Stream after form submit', 'Update page content with Turbo Streams after a form submission.', 'app_ux_turbo_stream'),
            new Example(UxPackage::LiveComponent, 'Registration form', 'A registration form with live validation using Symfony Forms and the Validator component.', 'app_ux_live_component_registration_form'),
            new Example(UxPackage::LiveComponent, 'Paginated fruits list', 'A paginated list of fruits, where the current page is persisted in the URL as a path parameter.', 'app_ux_live_component_fruits'),
            new Example(UxPackage::LiveComponent, 'With DTO', 'A live component that uses a DTO to encapsulate its state.', 'app_ux_live_component_with_dto'),
            new Example(UxPackage::LiveComponent, 'With DTO Collection', 'A live component that uses a collection of Data Transfer Objects (DTOs) to encapsulate its state.', 'app_ux_live_component_with_dto_collection'),
            new Example(UxPackage::LiveComponent, 'With DTO and Serializer', 'A live component that uses a DTO along with the Symfony Serializer component.', 'app_ux_live_component_with_dto_and_serializer'),
            new Example(UxPackage::LiveComponent, 'With DTO and custom Hydration/Dehydration methods', 'A live component that uses a DTO along with custom methods to hydrate/dehydrate the DTO.', 'app_ux_live_component_with_dto_and_custom_hydration_methods'),
            new Example(UxPackage::LiveComponent, 'With DTO and dedicated HydrationExtension', 'A live component that uses a DTO along with dedicated HydrationExtension to hydrate/dehydrate the DTO.', 'app_ux_live_component_with_dto_and_hydration_extension'),
            new Example(UxPackage::LiveComponent, 'Item list', 'A live component with LiveProp, LiveAction and LiveArg.', 'app_ux_live_component_item_list'),
            new Example(UxPackage::LiveComponent, 'With aliased LiveProps', 'A live component with LiveProps statically and dynamically aliased.', 'app_ux_live_component_with_aliased_live_props'),
            new Example(UxPackage::Map, 'Basic map (Leaflet)', 'A basic map centered on Paris with zoom level 12', 'app_ux_map_basic', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'Basic map (Google)', 'A basic map centered on Paris with zoom level 12', 'app_ux_map_basic', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With markers, fit bounds (Leaflet)', 'A map with 2 markers, and the bounds are automatically adjusted to fit both markers', 'app_ux_map_with_markers_and_fit_bounds_to_markers', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With markers, fit bounds (Google)', 'A map with 2 markers, and the bounds are automatically adjusted to fit both markers', 'app_ux_map_with_markers_and_fit_bounds_to_markers', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With markers, zoomed on Paris (Leaflet)', 'A map with 2 markers (Paris and Lyon), zoomed on Paris', 'app_ux_map_with_markers_and_zoomed_on_paris', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With markers, zoomed on Paris (Google)', 'A map with 2 markers (Paris and Lyon), zoomed on Paris', 'app_ux_map_with_markers_and_zoomed_on_paris', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With markers and info windows (Leaflet)', 'A map with 2 markers (Paris and Lyon), each with an info window', 'app_ux_map_with_markers_and_info_windows', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With markers and info windows (Google)', 'A map with 2 markers (Paris and Lyon), each with an info window', 'app_ux_map_with_markers_and_info_windows', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With custom icon markers (Leaflet)', 'A map with 3 markers (Paris, Lyon, Bordeaux), each with a custom icon', 'app_ux_map_with_markers_and_custom_icons', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With custom icon markers (Google)', 'A map with 3 markers (Paris, Lyon, Bordeaux), each with a custom icon', 'app_ux_map_with_markers_and_custom_icons', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With polygons (Leaflet)', 'A map with two polygons, one that covers main cities in Italy, and one weird shape on France', 'app_ux_map_with_polygons', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With polygons (Google)', 'A map with two polygons, one that covers main cities in Italy, and one weird shape on France', 'app_ux_map_with_polygons', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With polylines (Leaflet)', 'A map with two polylines: one through Paris/Lyon/Marseille/Bordeaux, and the other one through Rennes/Nantes/Tours', 'app_ux_map_with_polylines', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With polylines (Google)', 'A map with two polylines: one through Paris/Lyon/Marseille/Bordeaux, and the other one through Rennes/Nantes/Tours', 'app_ux_map_with_polylines', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With circles (Leaflet)', 'A map with two circles: one centered on Paris, the other on Lyon', 'app_ux_map_with_circles', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With circles (Google)', 'A map with two circles: one centered on Paris, the other on Lyon', 'app_ux_map_with_circles', ['renderer' => 'google']),
            new Example(UxPackage::Map, 'With rectangles (Leaflet)', 'A map with two rectangles: one from Paris to Lille, the other from Lyon to Bordeaux', 'app_ux_map_with_rectangles', ['renderer' => 'leaflet']),
            new Example(UxPackage::Map, 'With rectangles (Google)', 'A map with two rectangles: one from Paris to Lille, the other from Lyon to Bordeaux', 'app_ux_map_with_rectangles', ['renderer' => 'google']),
            new Example(UxPackage::React, 'Basic React Component', 'A basic React component that displays a welcoming message', 'app_ux_react_index'),
            new Example(UxPackage::Svelte, 'Basic Svelte Component', 'A basic Svelte component that displays a welcoming message', 'app_ux_svelte_index'),
            new Example(UxPackage::Translator, 'Basic translation', 'A simple translation example using the Translator component', 'app_ux_translator_basic'),
            new Example(UxPackage::Translator, 'Translation with parameter', 'Translation example with dynamic parameters', 'app_ux_translator_with_parameter'),
            new Example(UxPackage::Translator, 'ICU Translation with `select` argument', 'ICU message format example using the `select` argument', 'app_ux_translator_icu_select'),
            new Example(UxPackage::Translator, 'ICU Translation with `plural` argument', 'ICU message format example using the `plural` argument', 'app_ux_translator_icu_plural'),
            new Example(UxPackage::Translator, 'ICU Translation with `selectordinal` argument', 'ICU message format example using the `selectordinal` argument', 'app_ux_translator_icu_selectordinal'),
            new Example(UxPackage::Translator, 'ICU Translation with `date` and `time` arguments', 'ICU message format example using `date` and `time` arguments', 'app_ux_translator_icu_date_time'),
            new Example(UxPackage::Translator, 'ICU Translation with `number` and `percent` arguments', 'ICU message format example using `number` and `percent` arguments', 'app_ux_translator_icu_number_percent'),
            new Example(UxPackage::Translator, 'ICU Translation with `number` and `currency` arguments', 'ICU message format example using `number` and `currency` arguments', 'app_ux_translator_icu_number_currency'),
            new Example(UxPackage::Vue, 'Basic Vue Component', 'A basic Vue component that displays a welcoming message', 'app_ux_vue_index'),
        ];
    }

    /**
     * @return list<Example>
     */
    public function findAll(): array
    {
        return $this->examples;
    }

    /**
     * @return array<string, list<Example>>
     */
    public function findAllGroupedByPackage(string|null $query = null): array
    {
        $grouped = [];
        $examples = $this->examples;

        if (null !== $query) {
            $query = strtolower($query);
            $examples = array_filter($examples,
                fn(Example $example) => false !== mb_stripos($example->uxPackage->name . ' ' . $example->name . ' ' . $example->description, $query)
            );
        }

        foreach ($examples as $example) {
            $grouped[$example->uxPackage->value][] = $example;
        }

        return $grouped;
    }

    public function findOneByRoute(string $routeName): ?Example
    {
        foreach ($this->examples as $example) {
            if ($example->routeName === $routeName) {
                return $example;
            }
        }

        return null;
    }
}
