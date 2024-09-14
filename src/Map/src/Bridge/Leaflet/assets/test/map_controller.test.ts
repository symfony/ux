/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import LeafletController from '../src/map_controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('ux:map:pre-connect', (event) => {
            this.element.classList.add('pre-connected');
        });

        this.element.addEventListener('ux:map:connect', (event) => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('leaflet', LeafletController);
};

describe('LeafletController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
           <div 
              data-testid="map" 
              data-controller="check leaflet" 
              style="height&#x3A;&#x20;700px&#x3B;&#x20;margin&#x3A;&#x20;10px" 
              data-leaflet-provider-options-value="&#x7B;&#x7D;" 
              data-leaflet-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;4,&quot;fitBoundsToMarkers&quot;&#x3A;true,&quot;options&quot;&#x3A;&#x7B;&quot;tileLayer&quot;&#x3A;&#x7B;&quot;url&quot;&#x3A;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;tile.openstreetmap.org&#x5C;&#x2F;&#x7B;z&#x7D;&#x5C;&#x2F;&#x7B;x&#x7D;&#x5C;&#x2F;&#x7B;y&#x7D;.png&quot;,&quot;attribution&quot;&#x3A;&quot;&#x5C;u00a9&#x20;&lt;a&#x20;href&#x3D;&#x5C;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;www.openstreetmap.org&#x5C;&#x2F;copyright&#x5C;&quot;&gt;OpenStreetMap&lt;&#x5C;&#x2F;a&gt;&quot;,&quot;options&quot;&#x3A;&#x7B;&#x7D;&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Paris&quot;,&quot;infoWindow&quot;&#x3A;null&#x7D;,&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;45.764,&quot;lng&quot;&#x3A;4.8357&#x7D;,&quot;title&quot;&#x3A;&quot;Lyon&quot;,&quot;infoWindow&quot;&#x3A;&#x7B;&quot;headerContent&quot;&#x3A;&quot;&lt;b&gt;Lyon&lt;&#x5C;&#x2F;b&gt;&quot;,&quot;content&quot;&#x3A;&quot;The&#x20;French&#x20;town&#x20;in&#x20;the&#x20;historic&#x20;Rh&#x5C;u00f4ne-Alpes&#x20;region,&#x20;located&#x20;at&#x20;the&#x20;junction&#x20;of&#x20;the&#x20;Rh&#x5C;u00f4ne&#x20;and&#x20;Sa&#x5C;u00f4ne&#x20;rivers.&quot;,&quot;position&quot;&#x3A;null,&quot;opened&quot;&#x3A;false,&quot;autoClose&quot;&#x3A;true&#x7D;&#x7D;&#x5D;,&quot;polygons&quot;&#x3A;[]&#x7D;"
          ></div>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        const div = getByTestId(container, 'map');
        expect(div).not.toHaveClass('pre-connected');
        expect(div).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(div).toHaveClass('pre-connected'));
        await waitFor(() => expect(div).toHaveClass('connected'));
    });
});
