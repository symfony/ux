import { Application } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import AbstractMapController from '../src/abstract_map_controller.ts';
import * as L from 'leaflet';

class MyMapController extends AbstractMapController {
    protected dispatchEvent(name: string, payload: Record<string, unknown> = {}): void {
        this.dispatch(name, {
            prefix: 'ux:map',
            detail: payload,
        });
    }

    doCreateMap({ center, zoom, options }) {
        return { map: 'map', center, zoom, options };
    }

    doCreateMarker(definition) {
        const marker = { marker: 'marker', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: marker });
        }

        return marker;
    }

    doCreatePolygon(definition) {
        const polygon = { polygon: 'polygon', title: definition.title };

        if (definition.infoWindow) {
            this.createInfoWindow({ definition: definition.infoWindow, element: polygon });
        }
        return polygon;
    }

    doCreateInfoWindow({ definition, element }) {
        if (element.marker) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, marker: element.title };
        }
        if (element.polygon) {
            return { infoWindow: 'infoWindow', headerContent: definition.headerContent, polygon: element.title };
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

describe('AbstractMapController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
          <div 
            data-testid="map" 
            data-controller="map" 
            style="height: 700px; margin: 10px;" 
            data-map-provider-options-value="{}" 
            data-map-view-value='{
                "center": { "lat": 48.8566, "lng": 2.3522 },
                "zoom": 4,
                "fitBoundsToMarkers": true,
                "options": {},
                "markers": [
                    {
                        "position": { "lat": 48.8566, "lng": 2.3522 },
                        "title": "Paris",
                        "infoWindow": null
                    },
                    {
                        "position": { "lat": 45.764, "lng": 4.8357 },
                        "title": "Lyon",
                        "infoWindow": {
                            "headerContent": "<b>Lyon</b>",
                            "content": "The French town in the historic Rhône-Alpes region, located at the junction of the Rhône and Saône rivers.",
                            "position": null,
                            "opened": false,
                            "autoClose": true
                        }
                    }
                ],
                "polygons": [
                    {
                        "coordinates": [
                            { "lat": 48.858844, "lng": 2.294351 },
                            { "lat": 48.853, "lng": 2.3499 },
                            { "lat": 48.8566, "lng": 2.3522 }
                        ],
                        "title": "Polygon 1",
                        "infoWindow": null
                    },
                    {
                        "coordinates": [
                            { "lat": 45.764043, "lng": 4.835659 },
                            { "lat": 45.750000, "lng": 4.850000 },
                            { "lat": 45.770000, "lng": 4.820000 }
                        ],
                        "title": "Polygon 2",
                        "infoWindow": {
                            "headerContent": "<b>Polygon 2</b>",
                            "content": "A polygon around Lyon with some additional info.",
                            "position": null,
                            "opened": false,
                            "autoClose": true
                        }
                    }
                ]
            }'>
        </div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect and create map, marker, polygon and info window', async () => {
        const div = getByTestId(container, 'map');
        expect(div).not.toHaveClass('connected');

        const application = startStimulus();
        await waitFor(() => expect(application.getControllerForElementAndIdentifier(div, 'map')).not.toBeNull());

        const controller = application.getControllerForElementAndIdentifier(div, 'map');
        expect(controller.map).toEqual({ map: 'map', center: { lat: 48.8566, lng: 2.3522 }, zoom: 4, options: {} });
        expect(controller.markers).toEqual([
            { marker: 'marker', title: 'Paris' },
            { marker: 'marker', title: 'Lyon' },
        ]);
        expect(controller.polygons).toEqual([
            { polygon: 'polygon', title: 'Polygon 1' },
            { polygon: 'polygon', title: 'Polygon 2' },
        ]);
        expect(controller.infoWindows).toEqual([
            {
                headerContent: '<b>Lyon</b>',
                infoWindow: 'infoWindow',
                marker: 'Lyon',
            },
            {
                headerContent: '<b>Polygon 2</b>',
                infoWindow: 'infoWindow',
                polygon: 'Polygon 2',
            },
        ]);
    });
});
