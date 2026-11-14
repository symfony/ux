import { Application } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { clearDOM, mountDOM } from '../../../../../test/stimulus-helpers';
import AbstractMapController from '../../src/abstract_map_controller.ts';

class MyMapController extends AbstractMapController {
    protected dispatchEvent(name: string, payload: Record<string, unknown> = {}): void {
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: payload,
        });
    }

    doCreateMap({ definition }) {
        return { map: 'map', center: definition.center, zoom: definition.zoom, options: definition.options };
    }

    doCreateMarker({ definition }) {
        const marker = { marker: 'marker', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: marker });
        }

        return marker;
    }

    doCreatePolygon({ definition }) {
        const polygon = { polygon: 'polygon', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: polygon });
        }
        return polygon;
    }

    doCreatePolyline({ definition }) {
        const polyline = { polyline: 'polyline', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: polyline });
        }
        return polyline;
    }

    doCreateCircle({ definition }) {
        const circle = { circle: 'circle', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: circle });
        }
        return circle;
    }

    doCreateRectangle({ definition }) {
        const rectangle = { rectangle: 'rectangle', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: rectangle });
        }
        return rectangle;
    }

    doCreateInfoWindow({ definition, element }) {
        if (element.marker) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, marker: element.title };
        }
        if (element.polygon) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, polygon: element.title };
        }
        if (element.polyline) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, polyline: element.title };
        }
        if (element.circle) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, circle: element.title };
        }
        if (element.rectangle) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, rectangle: element.title };
        }
    }

    doFitBoundsToMarkers() {
        // no-op
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('map', MyMapController);
    return application;
};

const uxIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32"><path d="M1 2"/></svg>';
const inlineIconSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"><path d="M3 4"/></svg>';

describe('AbstractMapController with a shared icon table', () => {
    let container: HTMLElement;
    let application: Application | null = null;
    let listener: ((event: Event) => void) | null = null;

    afterEach(() => {
        if (listener) {
            document.removeEventListener('ux:map:marker:before-create', listener);
            listener = null;
        }
        application?.stop();
        application = null;
        clearDOM();
    });

    const mountMapWith = (markers: Array<unknown>, icons?: Array<string>) => {
        const attributes = [
            'data-testid="map"',
            'data-controller="map"',
            'data-map-provider-options-value="{}"',
            'data-map-options-value="{}"',
            `data-map-markers-value="${JSON.stringify(markers).replaceAll('"', '&quot;')}"`,
        ];
        if (icons) {
            attributes.push(`data-map-icons-value="${JSON.stringify(icons).replaceAll('"', '&quot;')}"`);
        }

        container = mountDOM(`<div ${attributes.join(' ')} style="height: 600px"></div>`);
    };

    const collectIcons = async () => {
        const definitions: Array<unknown> = [];
        listener = (event: Event) => {
            definitions.push((event as CustomEvent).detail.definition.icon);
        };
        document.addEventListener('ux:map:marker:before-create', listener);

        const div = getByTestId(container, 'map');
        application = startStimulus();
        await waitFor(() => expect(application?.getControllerForElementAndIdentifier(div, 'map')).not.toBeNull());

        return definitions;
    };

    it('resolves the icon references before dispatching "marker:before-create"', async () => {
        mountMapWith(
            [
                {
                    position: { lat: 48.8566, lng: 2.3522 },
                    title: 'Paris',
                    icon: { type: 'ux-icon', width: 32, height: 32, name: 'fa:map-marker', _generated_html: 0 },
                    extra: [],
                    '@id': 'marker-paris',
                },
                {
                    position: { lat: 45.75, lng: 4.85 },
                    title: 'Lyon',
                    icon: { type: 'ux-icon', width: 32, height: 32, name: 'fa:map-marker', _generated_html: 0 },
                    extra: [],
                    '@id': 'marker-lyon',
                },
                {
                    position: { lat: 43.6047, lng: 1.4442 },
                    title: 'Toulouse',
                    icon: { type: 'svg', width: 24, height: 24, html: inlineIconSvg },
                    extra: [],
                    '@id': 'marker-toulouse',
                },
            ],
            [uxIconSvg]
        );

        expect(await collectIcons()).toEqual([
            { type: 'ux-icon', width: 32, height: 32, name: 'fa:map-marker', _generated_html: uxIconSvg },
            { type: 'ux-icon', width: 32, height: 32, name: 'fa:map-marker', _generated_html: uxIconSvg },
            { type: 'svg', width: 24, height: 24, html: inlineIconSvg },
        ]);
    });

    it('keeps an inline "_generated_html" untouched', async () => {
        mountMapWith([
            {
                position: { lat: 48.8566, lng: 2.3522 },
                title: 'Paris',
                icon: { type: 'ux-icon', width: 32, height: 32, name: 'fa:map-marker', _generated_html: uxIconSvg },
                extra: [],
                '@id': 'marker-paris',
            },
        ]);

        expect(await collectIcons()).toEqual([
            { type: 'ux-icon', width: 32, height: 32, name: 'fa:map-marker', _generated_html: uxIconSvg },
        ]);
    });
});

describe('AbstractMapController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
            <div
                data-testid="map"
                data-controller="map"
                data-map-provider-options-value="{}"
                data-map-center-value="{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522}"
                data-map-zoom-value="4"
                data-map-fit-bounds-to-markers-value="false"
                data-map-options-value="{}"
                data-map-markers-value="[{&quot;position&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;title&quot;:&quot;Paris&quot;,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Paris&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:[]},&quot;extra&quot;:[],&quot;@id&quot;:&quot;a69f13edd2e571f3&quot;},{&quot;position&quot;:{&quot;lat&quot;:45.75,&quot;lng&quot;:4.85},&quot;title&quot;:&quot;Lyon&quot;,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Lyon&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:[]},&quot;extra&quot;:[],&quot;@id&quot;:&quot;cb9c1a30d562694b&quot;},{&quot;position&quot;:{&quot;lat&quot;:43.6047,&quot;lng&quot;:1.4442},&quot;title&quot;:&quot;Toulouse&quot;,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Toulouse&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:[]},&quot;extra&quot;:[],&quot;@id&quot;:&quot;e6b3acef1325fb52&quot;}]"
                data-map-polygons-value="[{&quot;points&quot;:[{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},{&quot;lat&quot;:45.75,&quot;lng&quot;:4.85},{&quot;lat&quot;:43.6047,&quot;lng&quot;:1.4442}],&quot;title&quot;:null,&quot;infoWindow&quot;:null,&quot;extra&quot;:[],&quot;@id&quot;:&quot;228ae6f5c1b17cfd&quot;},{&quot;points&quot;:[{&quot;lat&quot;:1.4442,&quot;lng&quot;:43.6047},{&quot;lat&quot;:4.85,&quot;lng&quot;:45.75},{&quot;lat&quot;:2.3522,&quot;lng&quot;:48.8566}],&quot;title&quot;:null,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Polygon&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:{&quot;foo&quot;:&quot;bar&quot;}},&quot;extra&quot;:{&quot;fillColor&quot;:&quot;#ff0000&quot;},&quot;@id&quot;:&quot;9874334e4e8caa16&quot;}]"
                data-map-polylines-value="[{&quot;points&quot;:[{&quot;lat&quot;:48.1173,&quot;lng&quot;:-1.6778},{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},{&quot;lat&quot;:48.2082,&quot;lng&quot;:16.3738}],&quot;title&quot;:null,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Polyline&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:{&quot;foo&quot;:&quot;bar&quot;}},&quot;extra&quot;:{&quot;strokeColor&quot;:&quot;#ff0000&quot;},&quot;@id&quot;:&quot;0fa955da866c7720&quot;}]"
                data-map-circles-value="[{&quot;center&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;title&quot;:null,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Circle&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:{&quot;foo&quot;:&quot;bar&quot;}},&quot;extra&quot;:{&quot;color&quot;:&quot;#ff0000&quot;, &quot;radii&quot;:1000},&quot;@id&quot;:&quot;7c3e1a9b5f2d4e81&quot;}]"
                data-map-rectangles-value="[{&quot;bounds&quot;:{&quot;northEast&quot;:{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522},&quot;southWest&quot;:{&quot;lat&quot;:45.75,&quot;lng&quot;:4.85}},&quot;title&quot;:null,&quot;infoWindow&quot;:{&quot;headerContent&quot;:&quot;Rectangle&quot;,&quot;content&quot;:null,&quot;position&quot;:null,&quot;opened&quot;:false,&quot;autoClose&quot;:true,&quot;extra&quot;:{&quot;foo&quot;:&quot;bar&quot;}},&quot;extra&quot;:{&quot;color&quot;:&quot;#ff0000&quot;, &quot;width&quot;:1000},&quot;@id&quot;:&quot;e6b3acef1325fb52&quot;}]"
                style="height: 600px"
            ></div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect and create map, marker, polygon, polyline, circle, rectangle and info window', async () => {
        const div = getByTestId(container, 'map');
        expect(div).not.toHaveClass('connected');

        const application = startStimulus();
        await waitFor(() => expect(application.getControllerForElementAndIdentifier(div, 'map')).not.toBeNull());

        const controller = application.getControllerForElementAndIdentifier(div, 'map');
        expect(controller.map).toEqual({ map: 'map', center: { lat: 48.8566, lng: 2.3522 }, zoom: 4, options: {} });
        expect(controller.markers).toEqual(
            new Map([
                ['a69f13edd2e571f3', { marker: 'marker', title: 'Paris' }],
                ['cb9c1a30d562694b', { marker: 'marker', title: 'Lyon' }],
                ['e6b3acef1325fb52', { marker: 'marker', title: 'Toulouse' }],
            ])
        );
        expect(controller.polygons).toEqual(
            new Map([
                ['228ae6f5c1b17cfd', { polygon: 'polygon', title: null }],
                ['9874334e4e8caa16', { polygon: 'polygon', title: null }],
            ])
        );
        expect(controller.polylines).toEqual(new Map([['0fa955da866c7720', { polyline: 'polyline', title: null }]]));
        expect(controller.circles).toEqual(new Map([['7c3e1a9b5f2d4e81', { circle: 'circle', title: null }]]));
        expect(controller.rectangles).toEqual(new Map([['e6b3acef1325fb52', { rectangle: 'rectangle', title: null }]]));
        expect(controller.infoWindows).toEqual([
            {
                headerContent: 'Paris',
                infoWindow: 'infoWindow',
                marker: 'Paris',
            },
            {
                headerContent: 'Lyon',
                infoWindow: 'infoWindow',
                marker: 'Lyon',
            },
            {
                headerContent: 'Toulouse',
                infoWindow: 'infoWindow',
                marker: 'Toulouse',
            },
            {
                headerContent: 'Polygon',
                infoWindow: 'infoWindow',
                polygon: null,
            },
            {
                headerContent: 'Polyline',
                infoWindow: 'infoWindow',
                polyline: null,
            },
            {
                headerContent: 'Circle',
                infoWindow: 'infoWindow',
                circle: null,
            },
            {
                headerContent: 'Rectangle',
                infoWindow: 'infoWindow',
                rectangle: null,
            },
        ]);
    });
});
