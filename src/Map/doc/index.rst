Symfony UX Map
==============

**EXPERIMENTAL** This component is currently experimental and is likely
to change, or even change drastically.

Symfony UX Map is a Symfony bundle integrating interactive Maps in
Symfony applications. It is part of `the Symfony UX initiative`_.

Installation
------------

Install the bundle using Composer and Symfony Flex:

.. code-block:: terminal

    $ composer require symfony/ux-map

Configuration
-------------

Configuration is done in your ``config/packages/ux_map.yaml`` file:

.. code-block:: yaml

    # config/packages/ux_map.yaml
    ux_map:
        renderer: '%env(resolve:default::UX_MAP_DSN)%'

        # Google Maps specific configuration
        google_maps:
            # Configure the default Map Id (https://developers.google.com/maps/documentation/get-map-id),
            # without to manually configure it in each map instance (through "new GoogleOptions(mapId: 'your_map_id')").
            default_map_id: null

The ``UX_MAP_DSN`` environment variable configure which renderer to use.

Map renderers
~~~~~~~~~~~~~

The Symfony UX Map bundle supports multiple renderers. A map renderer is a
service that provides the code and graphic assets required to render and
interact with a map in the browser.

Available renderers
~~~~~~~~~~~~~~~~~~~

UX Map ships with two renderers: `Google Maps`_ and `Leaflet`_.

==============  ===============================================================
Renderer
==============  ===============================================================
`Google Maps`_  **Install**: ``composer require symfony/ux-google-map`` \
                **DSN**: ``UX_MAP_DSN=google://GOOGLE_MAPS_API_KEY@default`` \
`Leaflet`_      **Install**: ``composer require symfony/ux-leaflet-map`` \
                **DSN**: ``UX_MAP_DSN=leaflet://default`` \
==============  ===============================================================

.. tip::

    Read the `Symfony UX Map Leaflet bridge docs`_ and the
    `Symfony UX Map Google Maps brige docs`_ to learn about the configuration
    options available for each renderer.

Create a map
------------

A map is created by calling ``new Map()``. You can configure the center, zoom, and add markers.
Start by creating a new map instance::

    use Symfony\UX\Map\Map;

    // Create a new map instance
    $myMap = (new Map());

Center and zoom
~~~~~~~~~~~~~~~

You can set the center and zoom of the map using the ``center()`` and ``zoom()`` methods::

    use Symfony\UX\Map\Map;
    use Symfony\UX\Map\Point;

    $myMap
        // Explicitly set the center and zoom
        ->center(new Point(46.903354, 1.888334))
        ->zoom(6)

        // Or automatically fit the bounds to the markers
        ->fitBoundsToMarkers()
    ;

Add markers
~~~~~~~~~~~

You can add markers to a map using the ``addMarker()`` method::

    $myMap
        ->addMarker(new Marker(
            position: new Point(48.8566, 2.3522),
            title: 'Paris'
        ))

        // With an info window associated to the marker:
        ->addMarker(new Marker(
            position: new Point(45.7640, 4.8357),
            title: 'Lyon',
            infoWindow: new InfoWindow(
                headerContent: '<b>Lyon</b>',
                content: 'The French town in the historic Rhône-Alpes region, located at the junction of the Rhône and Saône rivers.'
            ),
            icon: new Icon(
                content: '<svg>....</svg>'
                icontType: 'html'
            )
        ))

        // You can also pass arbitrary data via the `extra` option in both the marker
        // and the infoWindow; you can later use this data in your custom Stimulus controllers
        ->addMarker(new Marker(
            position: new Point(45.7740, 4.8351),
            extra: [
                'icon_mask_url' => 'https://maps.gstatic.com/mapfiles/place_api/icons/v2/tree_pinlet.svg',
            ],
            infoWindow: new InfoWindow(
                // ...
                extra: [
                    'num_items' => 3,
                    'includes_link' => true,
                ],
            ),
        ))
    ;

Add Marker icons
~~~~~~~~~~~~~~~~

.. versionadded:: 2.24

    ``Marker`` icon customization is available since UX Map 2.24.

A ``Marker`` can be customized with an ``Icon`` instance, which can either be an UX Icon, an URL, or a SVG content::

        // It can be a UX Icon (requires `symfony/ux-icons` package)...
        $icon = Icon::ux('fa:map-marker')->width(24)->height(24);
        // ... or an URL pointing to an image
        $icon = Icon::url('https://example.com/marker.png')->width(24)->height(24);
        // ... or a plain SVG string
        $icon = Icon::svg('<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24">...</svg>');

        $map->addMarker(new Marker(
            // ...
            icon: $icon
        ));

Add Polygons
~~~~~~~~~~~~

You can also add Polygons, which represents an area enclosed by a series of ``Point`` instances::

    $myMap->addPolygon(new Polygon(
        points: [
            new Point(48.8566, 2.3522),
            new Point(45.7640, 4.8357),
            new Point(43.2965, 5.3698),
            new Point(44.8378, -0.5792),
        ],
        infoWindow: new InfoWindow(
            content: 'Paris, Lyon, Marseille, Bordeaux',
        ),
    ));

Add Polylines
~~~~~~~~~~~~~

You can add Polylines, which represents a path made by a series of ``Point`` instances::

    $myMap->addPolyline(new Polyline(
        points: [
            new Point(48.8566, 2.3522),
            new Point(45.7640, 4.8357),
            new Point(43.2965, 5.3698),
            new Point(44.8378, -0.5792),
        ],
        infoWindow: new InfoWindow(
            content: 'A line passing through Paris, Lyon, Marseille, Bordeaux',
        ),
    ));

Remove elements from Map
~~~~~~~~~~~~~~~~~~~~~~~~

It is possible to remove elements like ``Marker``, ``Polygon`` and ``Polyline`` instances by using ``Map::remove*()`` methods.
It's useful when :ref:`using a Map inside a Live Component <map-live-component>`::

    // Add elements
    $map->addMarker($marker = new Marker(/* ... */));
    $map->addPolygon($polygon = new Polygon(/* ... */));
    $map->addPolyline($polyline = new Polyline(/* ... */));

    // And later, remove those elements
    $map->removeMarker($marker);
    $map->removePolygon($polygon);
    $map->removePolyline($polyline);

If you haven't stored the element instance, you can still remove them by passing the identifier string::

    $map = new Map(/* ... */);
    // Add elements
    $map->addMarker(new Marker(id: 'my-marker', /* ... */));
    $map->addPolygon(new Polygon(id: 'my-polygon', /* ... */));
    $map->addPolyline(new Polyline(id: 'my-marker', /* ... */));

    // And later, remove those elements
    $map->removeMarker('my-marker');
    $map->removePolygon('my-polygon');
    $map->removePolyline('my-marker');

Render a map
------------

To render a map in your Twig template, use the ``ux_map`` Twig function, e.g.:

To be visible, the map must have a defined height:

.. code-block:: twig

    {{ ux_map(my_map, { style: 'height: 300px' }) }}

You can add custom HTML attributes too:

.. code-block:: twig

    {{ ux_map(my_map, { style: 'height: 300px', id: 'events-map', class: 'mb-3' }) }}

Twig Function ``ux_map()``
~~~~~~~~~~~~~~~~~~~~~~~~~~

The ``ux_map()`` Twig function allows you to create and render a map in your Twig
templates. The function accepts the same arguments as the ``Map`` class:

.. code-block:: html+twig

    {{ ux_map(
        center: [51.5074, 0.1278],
        zoom: 3,
        markers: [
            { position: [51.5074, 0.1278], title: 'London' },
            { position: [48.8566, 2.3522], title: 'Paris' },
            {
                position: [40.7128, -74.0060],
                title: 'New York',
                infoWindow: { content: 'Welcome to <b>New York</b>' }
            },
        ],
        attributes: {
            class: 'foo',
            style: 'height: 800px; width: 100%; border: 4px solid red; margin-block: 10vh;',
        }
    ) }}

Twig Component ``<twig:ux:map />``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Alternatively, you can use the ``<twig:ux:map />`` component.

.. code-block:: html+twig

    <twig:ux:map
        center="[51.5074, 0.1278]"
        zoom="3"
        markers='[
            {"position": [51.5074, 0.1278], "title": "London"},
            {"position": [48.8566, 2.3522], "title": "Paris"},
            {
                "position": [40.7128, -74.0060],
                "title": "New York",
                "infoWindow": {"content": "Welcome to <b>New York</b>"}
            }
        ]'
        class="foo"
        style="height: 800px; width: 100%; border: 4px solid red; margin-block: 10vh;"
    />

The ``<twig:ux:map />`` component requires the `Twig Component`_ package.

.. code-block:: terminal

    $ composer require symfony/ux-twig-component

Interact with the map
~~~~~~~~~~~~~~~~~~~~~

Symfony UX Map allows you to extend its default behavior using a custom Stimulus controller:

.. code-block:: javascript

    // assets/controllers/mymap_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.addEventListener('ux:map:pre-connect', this._onPreConnect);
            this.element.addEventListener('ux:map:connect', this._onConnect);
            this.element.addEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
            this.element.addEventListener('ux:map:marker:after-create', this._onMarkerAfterCreate);
            this.element.addEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
            this.element.addEventListener('ux:map:info-window:after-create', this._onInfoWindowAfterCreate);
            this.element.addEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
            this.element.addEventListener('ux:map:polygon:after-create', this._onPolygonAfterCreate);
            this.element.addEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
            this.element.addEventListener('ux:map:polyline:after-create', this._onPolylineAfterCreate);
        }

        disconnect() {
            // You should always remove listeners when the controller is disconnected to avoid side effects
            this.element.removeEventListener('ux:map:pre-connect', this._onPreConnect);
            this.element.removeEventListener('ux:map:connect', this._onConnect);
            this.element.removeEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
            this.element.removeEventListener('ux:map:marker:after-create', this._onMarkerAfterCreate);
            this.element.removeEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
            this.element.removeEventListener('ux:map:info-window:after-create', this._onInfoWindowAfterCreate);
            this.element.removeEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
            this.element.removeEventListener('ux:map:polygon:after-create', this._onPolygonAfterCreate);
            this.element.removeEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
            this.element.removeEventListener('ux:map:polyline:after-create', this._onPolylineAfterCreate);
        }

        /**
         * This event is triggered when the map is not created yet
         * You can use this event to configure the map before it is created
         */
        _onPreConnect(event) {
            console.log(event.detail.options);
        }

        /**
         * This event is triggered when the map and all its elements (markers, info windows, ...) are created.
         * The instances depend on the renderer you are using.
         */
        _onConnect(event) {
            console.log(event.detail.map);
            console.log(event.detail.markers);
            console.log(event.detail.infoWindows);
            console.log(event.detail.polygons);
            console.log(event.detail.polylines);
        }

        /**
         * This event is triggered before creating a marker.
         * You can use this event to fine-tune it before its creation.
         */
        _onMarkerBeforeCreate(event) {
            console.log(event.detail.definition);
            // { title: 'Paris', position: { lat: 48.8566, lng: 2.3522 }, ... }

            // Example: uppercase the marker title
            event.detail.definition.title = event.detail.definition.title.toUpperCase();
        }

        /**
         * This event is triggered after creating a marker.
         * You can access the created marker instance, which depends on the renderer you are using.
         */
        _onMarkerAfterCreate(event) {
            // The marker instance
            console.log(event.detail.marker);
        }

        /**
         * This event is triggered before creating an info window.
         * You can use this event to fine-tune the info window before its creation.
         */
        _onInfoWindowBeforeCreate(event) {
            console.log(event.detail.definition);
            // { headerContent: 'Paris', content: 'The capital of France', ... }
        }

        /**
         * This event is triggered after creating an info window.
         * You can access the created info window instance, which depends on the renderer you are using.
         */
        _onInfoWindowAfterCreate(event) {
            // The info window instance
            console.log(event.detail.infoWindow);

            // The associated element instance is also available, e.g. a marker...
            console.log(event.detail.marker);
            // ... or a polygon
            console.log(event.detail.polygon);
            // ... or a polyline
            console.log(event.detail.polyline);
        }

        /**
         * This event is triggered before creating a polygon.
         * You can use this event to fine-tune it before its creation.
         */
        _onPolygonBeforeCreate(event) {
            console.log(event.detail.definition);
            // { title: 'My polygon', points: [ { lat: 48.8566, lng: 2.3522 }, { lat: 45.7640, lng: 4.8357 }, { lat: 43.2965, lng: 5.3698 }, ... ], ... }
        }

        /**
         * This event is triggered after creating a polygon.
         * You can access the created polygon instance, which depends on the renderer you are using.
         */
        _onPolygonAfterCreate(event) {
            // The polygon instance
            console.log(event.detail.polygon);
        }

        /**
         * This event is triggered before creating a polyline.
         * You can use this event to fine-tune it before its creation.
         */
        _onPolylineBeforeCreate(event) {
            console.log(event.detail.definition);
            // { title: 'My polyline', points: [ { lat: 48.8566, lng: 2.3522 }, { lat: 45.7640, lng: 4.8357 }, { lat: 43.2965, lng: 5.3698 }, ... ], ... }
        }

        /**
         * This event is triggered after creating a polyline.
         * You can access the created polyline instance, which depends on the renderer you are using.
         */
        _onPolylineAfterCreate(event) {
            // The polyline instance
            console.log(event.detail.polyline);
        }
    }

Then, you can use this controller in your template:

.. code-block:: twig

    {{ ux_map(my_map, { 'data-controller': 'mymap', style: 'height: 300px' }) }}

.. tip::

    Read the `Symfony UX Map Leaflet bridge docs`_ and the
    `Symfony UX Map Google Maps brige docs`_ to learn about the exact code
    needed to customize the markers.

Advanced: Low-level options
~~~~~~~~~~~~~~~~~~~~~~~~~~~

UX Map is renderer-agnostic and provides a high-level API to configure
maps and their elements. However, you might occasionally find this
abstraction limiting and need to configure low-level options directly.

Fortunately, you can customize these low-level options through the UX Map
events ``ux:map:*:before-create`` using the special ``rawOptions`` property:

.. code-block:: javascript

    // assets/controllers/mymap_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {
        connect() {
            this.element.addEventListener('ux:map:marker:before-create', this._onMarkerBeforeCreate);
            this.element.addEventListener('ux:map:info-window:before-create', this._onInfoWindowBeforeCreate);
            this.element.addEventListener('ux:map:polygon:before-create', this._onPolygonBeforeCreate);
            this.element.addEventListener('ux:map:polyline:before-create', this._onPolylineBeforeCreate);
        }

        _onMarkerBeforeCreate(event) {
            // When using Google Maps, to configure a `google.maps.AdvancedMarkerElement`
            event.detail.definition.rawOptions = {
                gmpDraggable: true,
                // ...
            };

            // When using Leaflet, to configure a `L.Marker` instance
            event.detail.definition.rawOptions = {
                riseOnHover: true,
                // ...
            };
        }

        _onInfoWindowBeforeCreate(event) {
            // When using Google Maps, to configure a `google.maps.InfoWindow` instance
            event.detail.definition.rawOptions = {
                maxWidth: 200,
                // ...
            };

            // When using Leaflet, to configure a `L.Popup` instance
            event.detail.definition.rawOptions = {
                direction: 'left',
                // ...
            };
        }

        _onPolygonBeforeCreate(event) {
            // When using Google Maps, to configure a `google.maps.Polygon`
            event.detail.definition.rawOptions = {
                strokeColor: 'red',
                // ...
            };

            // When using Leaflet, to configure a `L.Polygon`
            event.detail.definition.rawOptions = {
                color: 'red',
                // ...
            };
        }

        _onPolylineBeforeCreate(event) {
            // When using Google Maps, to configure a `google.maps.Polyline`
            event.detail.definition.rawOptions = {
                strokeColor: 'red',
                // ...
            };

            // When using Leaflet, to configure a `L.Polyline`
            event.detail.definition.rawOptions = {
                color: 'red',
                // ...
            };
        }
    }

Advanced: Passing extra data from PHP to the Stimulus controller
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For greater customization and extensibility, you can pass additional data from PHP
to the Stimulus controller. This can be useful when associating extra information
with a specific marker; for example, indicating the type of location it represents.

These additional data points are defined and used exclusively by you; UX Map
only forwards them to the Stimulus controller.

To pass extra data from PHP to the Stimulus controller, you must use the ``extra`` property
available in ``Marker``, ``InfoWindow``, ``Polygon`` and ``Polyline`` instances::

    $map->addMarker(new Marker(
        position: new Point(48.822248, 2.337338),
        title: 'Paris - Parc Montsouris',
        extra: [
            'type' => 'Park',
            // ...
        ],
    ));

On the JavaScript side, you can access your extra data via the
``event.detail.definition.extra`` object, available in the
``ux:map:*:before-create`` and ``ux:map:*:after-create`` events:

.. code-block:: javascript

    // assets/controllers/mymap_controller.js

    import { Controller } from '@hotwired/stimulus';

    export default class extends Controller {

        // ...

        _onMarkerBeforeCreate(event) {
            console.log(event.detail.definition.extra);
            // { type: 'Park', ...}
        }

        _onMarkerAfterCreate(event) {
            console.log(event.detail.definition.extra);
            // { type: 'Park', ...}
        }

        // ...
    }

.. _map-live-component:

Usage with Live Components
--------------------------

.. versionadded:: 2.22

    The ability to render and interact with a Map inside a Live Component was added in Map 2.22.

To use a Map inside a Live Component, you need to use the ``ComponentWithMapTrait`` trait
and implement the method ``instantiateMap`` to return a ``Map`` instance.

You can interact with the Map by using ``LiveAction`` attribute::

    namespace App\Twig\Components;

    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\Map\InfoWindow;
    use Symfony\UX\Map\Live\ComponentWithMapTrait;
    use Symfony\UX\Map\Map;
    use Symfony\UX\Map\Marker;
    use Symfony\UX\Map\Point;

    #[AsLiveComponent]
    final class MapLivePlayground
    {
        use DefaultActionTrait;
        use ComponentWithMapTrait;

        protected function instantiateMap(): Map
        {
            return (new Map())
                ->center(new Point(48.8566, 2.3522))
                ->zoom(7)
                ->addMarker(new Marker(position: new Point(48.8566, 2.3522), title: 'Paris', infoWindow: new InfoWindow('Paris')))
                ->addMarker(new Marker(position: new Point(45.75, 4.85), title: 'Lyon', infoWindow: new InfoWindow('Lyon')))
            ;
        }
    }

Then, you can render the map with ``ux_map()`` in your component template:

.. code-block:: html+twig

    <div{{ attributes }}>
        {{ ux_map(map, {style: 'height: 300px'}) }}
    </div>

Then, you can define `Live Actions`_ to interact with the map from the client-side.
You can retrieve the map instance using the ``getMap()`` method, and change the map center, zoom, add markers, etc::

        #[LiveAction]
        public function doSomething(): void
        {
            // Change the map center
            $this->getMap()->center(new Point(45.7640, 4.8357));

            // Change the map zoom
            $this->getMap()->zoom(6);

            // Add a new marker
            $this->getMap()->addMarker(new Marker(position: new Point(43.2965, 5.3698), title: 'Marseille', infoWindow: new InfoWindow('Marseille')));

            // Add a new polygon
            $this->getMap()->addPolygon(new Polygon(points: [
                new Point(48.8566, 2.3522),
                new Point(45.7640, 4.8357),
                new Point(43.2965, 5.3698),
                new Point(44.8378, -0.5792),
            ], infoWindow: new InfoWindow('Paris, Lyon, Marseille, Bordeaux')));
        }

.. code-block:: html+twig

    <div{{ attributes.defaults() }}>
        {{ ux_map(map, { style: 'height: 300px' }) }}

        <button
            type="button"
            data-action="live#action"
            data-live-action-param="doSomething"
        >
            Do something with the map
        </button>
    </div>

Backward Compatibility promise
------------------------------

This bundle aims at following the same Backward Compatibility promise as
the Symfony framework:
https://symfony.com/doc/current/contributing/code/bc.html

.. _`the Symfony UX initiative`: https://ux.symfony.com/
.. _`Google Maps`: https://github.com/symfony/ux-google-map
.. _`Leaflet`: https://github.com/symfony/ux-leaflet-map
.. _`Symfony UX Map Google Maps brige docs`: https://github.com/symfony/ux/blob/2.x/src/Map/src/Bridge/Google/README.md
.. _`Symfony UX Map Leaflet bridge docs`: https://github.com/symfony/ux/blob/2.x/src/Map/src/Bridge/Leaflet/README.md
.. _`Twig Component`: https://symfony.com/bundles/ux-twig-component/current/index.html
.. _`Live Actions`: https://symfony.com/bundles/ux-live-component/current/index.html#actions
