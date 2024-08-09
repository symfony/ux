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
            this.createInfoWindow({ definition: definition.infoWindow, marker });
        }

        return marker;
    }

    doCreateInfoWindow({ definition, marker }) {
        return { infoWindow: 'infoWindow', headerContent: definition.headerContent, marker: marker.title };
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
              style="height&#x3A;&#x20;700px&#x3B;&#x20;margin&#x3A;&#x20;10px" 
              data-map-provider-options-value="&#x7B;&#x7D;" 
              data-map-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;4,&quot;fitBoundsToMarkers&quot;&#x3A;true,&quot;options&quot;&#x3A;&#x7B;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Paris&quot;,&quot;infoWindow&quot;&#x3A;null&#x7D;,&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;45.764,&quot;lng&quot;&#x3A;4.8357&#x7D;,&quot;title&quot;&#x3A;&quot;Lyon&quot;,&quot;infoWindow&quot;&#x3A;&#x7B;&quot;headerContent&quot;&#x3A;&quot;&lt;b&gt;Lyon&lt;&#x5C;&#x2F;b&gt;&quot;,&quot;content&quot;&#x3A;&quot;The&#x20;French&#x20;town&#x20;in&#x20;the&#x20;historic&#x20;Rh&#x5C;u00f4ne-Alpes&#x20;region,&#x20;located&#x20;at&#x20;the&#x20;junction&#x20;of&#x20;the&#x20;Rh&#x5C;u00f4ne&#x20;and&#x20;Sa&#x5C;u00f4ne&#x20;rivers.&quot;,&quot;position&quot;&#x3A;null,&quot;opened&quot;&#x3A;false,&quot;autoClose&quot;&#x3A;true&#x7D;&#x7D;&#x5D;&#x7D;"
          ></div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect and create map, marker and info window', async () => {
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
        expect(controller.infoWindows).toEqual([
            {
                headerContent: '<b>Lyon</b>',
                infoWindow: 'infoWindow',
                marker: 'Lyon',
            },
        ]);
    });
});
