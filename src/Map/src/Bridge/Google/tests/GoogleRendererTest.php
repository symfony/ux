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
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;disableDoubleClickZoom&quot;:false,&quot;zoomControlOptions&quot;:{&quot;position&quot;:22},&quot;mapTypeControlOptions&quot;:{&quot;mapTypeIds&quot;:[],&quot;position&quot;:14,&quot;style&quot;:0},&quot;streetViewControlOptions&quot;:{&quot;position&quot;:22},&quot;fullscreenControlOptions&quot;:{&quot;position&quot;:20}},&quot;markers&quot;:[]}"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => $map,
        ];

        yield 'with every options' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;id&quot;&#x3A;&quot;gmap&quot;,&quot;language&quot;&#x3A;&quot;fr&quot;,&quot;region&quot;&#x3A;&quot;FR&quot;,&quot;nonce&quot;&#x3A;&quot;abcd&quot;,&quot;retries&quot;&#x3A;10,&quot;url&quot;&#x3A;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;maps.googleapis.com&#x5C;&#x2F;maps&#x5C;&#x2F;api&#x5C;&#x2F;js&quot;,&quot;version&quot;&#x3A;&quot;quarterly&quot;,&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;disableDoubleClickZoom&quot;:false,&quot;zoomControlOptions&quot;:{&quot;position&quot;:22},&quot;mapTypeControlOptions&quot;:{&quot;mapTypeIds&quot;:[],&quot;position&quot;:14,&quot;style&quot;:0},&quot;streetViewControlOptions&quot;:{&quot;position&quot;:22},&quot;fullscreenControlOptions&quot;:{&quot;position&quot;:20}},&quot;markers&quot;:[]}"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key', id: 'gmap', language: 'fr', region: 'FR', nonce: 'abcd', retries: 10, url: 'https://maps.googleapis.com/maps/api/js', version: 'quarterly'),
            'map' => $map,
        ];

        yield 'with custom attributes' => [
            'expected_render' => '<div data-controller="my-custom-controller symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" class="map" data-symfony--ux-google-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;disableDoubleClickZoom&quot;:false,&quot;zoomControlOptions&quot;:{&quot;position&quot;:22},&quot;mapTypeControlOptions&quot;:{&quot;mapTypeIds&quot;:[],&quot;position&quot;:14,&quot;style&quot;:0},&quot;streetViewControlOptions&quot;:{&quot;position&quot;:22},&quot;fullscreenControlOptions&quot;:{&quot;position&quot;:20}},&quot;markers&quot;:[]}"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => $map,
            'attributes' => ['data-controller' => 'my-custom-controller', 'class' => 'map'],
        ];

        yield 'with markers and infoWindows' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;disableDoubleClickZoom&quot;:false,&quot;zoomControlOptions&quot;:{&quot;position&quot;:22},&quot;mapTypeControlOptions&quot;:{&quot;mapTypeIds&quot;:[],&quot;position&quot;:14,&quot;style&quot;:0},&quot;streetViewControlOptions&quot;:{&quot;position&quot;:22},&quot;fullscreenControlOptions&quot;:{&quot;position&quot;:20}},&quot;markers&quot;:[{&quot;position&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;title&quot;:&quot;Paris&quot;,&quot;infoWindow&quot;:null,&quot;extra&quot;:{}},{&quot;position&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;title&quot;:&quot;Lyon&quot;,&quot;infoWindow&quot;:{&quot;headerContent&quot;:null,&quot;content&quot;:&quot;Lyon&quot;,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:{}},&quot;extra&quot;:{}}]}"></div>',
            'renderer' => new GoogleRenderer(new StimulusHelper(null), apiKey: 'api_key'),
            'map' => (clone $map)
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Paris'))
                ->addMarker(new Marker(new Point(48.8566, 2.3522), 'Lyon', infoWindow: new InfoWindow(content: 'Lyon'))),
        ];

        yield 'with controls enabled' => [
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;disableDoubleClickZoom&quot;:false,&quot;zoomControlOptions&quot;:{&quot;position&quot;:22},&quot;mapTypeControlOptions&quot;:{&quot;mapTypeIds&quot;:[],&quot;position&quot;:14,&quot;style&quot;:0},&quot;streetViewControlOptions&quot;:{&quot;position&quot;:22},&quot;fullscreenControlOptions&quot;:{&quot;position&quot;:20}},&quot;markers&quot;:[]}"></div>',
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
            'expected_render' => '<div data-controller="symfony--ux-google-map--map" data-symfony--ux-google-map--map-provider-options-value="&#x7B;&quot;apiKey&quot;&#x3A;&quot;api_key&quot;&#x7D;" data-symfony--ux-google-map--map-view-value="{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;zoom&quot;:12,&quot;fitBoundsToMarkers&quot;:false,&quot;options&quot;:{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;disableDoubleClickZoom&quot;:false},&quot;markers&quot;:[]}"></div>',
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
