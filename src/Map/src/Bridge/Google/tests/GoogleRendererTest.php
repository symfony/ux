<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Map\Bridge\Google\Tests;

use Symfony\UX\Map\Bridge\Google\GoogleOptions;
use Symfony\UX\Map\Bridge\Google\Renderer\GoogleRenderer;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Test\RendererTestCase;
use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

class GoogleRendererTest extends RendererTestCase
{
    public function provideTestRenderMap(): iterable
    {
        $map = (new Map())
            ->center(new Point(48.8566, 2.3522))
            ->zoom(12);

        yield 'simple map, with minimum options' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;null,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false,&quot;zoomControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;mapTypeControlOptions&quot;&#x3A;&#x7B;&quot;mapTypeIds&quot;&#x3A;&#x5B;&#x5D;,&quot;position&quot;&#x3A;14,&quot;style&quot;&#x3A;0&#x7D;,&quot;streetViewControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;fullscreenControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;20&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;&#x7D;"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => $map,
        ];

        yield 'with every options' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;id&quot;&#x3A;&quot;gmap&quot;,&quot;language&quot;&#x3A;&quot;fr&quot;,&quot;region&quot;&#x3A;&quot;FR&quot;,&quot;nonce&quot;&#x3A;&quot;abcd&quot;,&quot;retries&quot;&#x3A;10,&quot;url&quot;&#x3A;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;maps.googleapis.com&#x5C;&#x2F;maps&#x5C;&#x2F;api&#x5C;&#x2F;js&quot;,&quot;version&quot;&#x3A;&quot;quarterly&quot;,&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;null,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false,&quot;zoomControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;mapTypeControlOptions&quot;&#x3A;&#x7B;&quot;mapTypeIds&quot;&#x3A;&#x5B;&#x5D;,&quot;position&quot;&#x3A;14,&quot;style&quot;&#x3A;0&#x7D;,&quot;streetViewControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;fullscreenControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;20&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;&#x7D;"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key', id: 'gmap', language: 'fr', region: 'FR', nonce: 'abcd', retries: 10, url: 'https://maps.googleapis.com/maps/api/js', version: 'quarterly'),
            'map' => $map,
        ];

        yield 'with custom attributes' => [
            'expected_render' => '<div data-controller="my-custom-controller symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;null,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false,&quot;zoomControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;mapTypeControlOptions&quot;&#x3A;&#x7B;&quot;mapTypeIds&quot;&#x3A;&#x5B;&#x5D;,&quot;position&quot;&#x3A;14,&quot;style&quot;&#x3A;0&#x7D;,&quot;streetViewControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;fullscreenControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;20&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;&#x7D;" class="map"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => $map,
            'attributes' => ['data-controller' => 'my-custom-controller', 'class' => 'map'],
        ];

        yield 'with markers and infoWindows' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;null,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false,&quot;zoomControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;mapTypeControlOptions&quot;&#x3A;&#x7B;&quot;mapTypeIds&quot;&#x3A;&#x5B;&#x5D;,&quot;position&quot;&#x3A;14,&quot;style&quot;&#x3A;0&#x7D;,&quot;streetViewControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;fullscreenControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;20&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Paris&quot;,&quot;infoWindow&quot;&#x3A;null,&quot;extra&quot;&#x3A;&#x7B;&#x7D;&#x7D;,&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Lyon&quot;,&quot;infoWindow&quot;&#x3A;&#x7B;&quot;headerContent&quot;&#x3A;null,&quot;content&quot;&#x3A;&quot;Lyon&quot;,&quot;position&quot;&#x3A;null,&quot;opened&quot;&#x3A;false,&quot;autoClose&quot;&#x3A;true,&quot;extra&quot;&#x3A;&#x7B;&#x7D;&#x7D;,&quot;extra&quot;&#x3A;&#x7B;&#x7D;&#x7D;&#x5D;&#x7D;"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => (clone $map)
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Paris'))
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'))),
        ];

        yield 'with controls enabled' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;null,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false,&quot;zoomControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;mapTypeControlOptions&quot;&#x3A;&#x7B;&quot;mapTypeIds&quot;&#x3A;&#x5B;&#x5D;,&quot;position&quot;&#x3A;14,&quot;style&quot;&#x3A;0&#x7D;,&quot;streetViewControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;fullscreenControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;20&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;&#x7D;"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => (clone $map)
                ->options(new GoogleOptions(
                    zoomControl: true,
                    mapTypeControl: true,
                    streetViewControl: true,
                    fullscreenControl: true,
                )),
        ];

        yield 'without controls enabled' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;12,&quot;fitBoundsToMarkers&quot;&#x3A;false,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;null,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x5D;&#x7D;"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => (clone $map)
                ->options(new GoogleOptions(
                    zoomControl: false,
                    mapTypeControl: false,
                    streetViewControl: false,
                    fullscreenControl: false,
                )),
        ];
    }
}
