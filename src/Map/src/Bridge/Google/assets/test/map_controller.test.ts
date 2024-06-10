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
import GoogleController from '../src/map_controller';

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
    application.register('google', GoogleController);
};

describe('GoogleMapsController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
          <div 
              data-testid="map"
              data-controller="check google" 
              style="height&#x3A;&#x20;700px&#x3B;&#x20;margin&#x3A;&#x20;10px" 
              data-google-provider-options-value="&#x7B;&quot;language&quot;&#x3A;&quot;fr&quot;,&quot;region&quot;&#x3A;&quot;FR&quot;,&quot;retries&quot;&#x3A;10,&quot;version&quot;&#x3A;&quot;weekly&quot;,&quot;apiKey&quot;&#x3A;&quot;&quot;&#x7D;" 
              data-google-view-value="&#x7B;&quot;center&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;zoom&quot;&#x3A;4,&quot;fitBoundsToMarkers&quot;&#x3A;true,&quot;options&quot;&#x3A;&#x7B;&quot;mapId&quot;&#x3A;&quot;YOUR_MAP_ID&quot;,&quot;gestureHandling&quot;&#x3A;&quot;auto&quot;,&quot;backgroundColor&quot;&#x3A;null,&quot;disableDoubleClickZoom&quot;&#x3A;false,&quot;zoomControl&quot;&#x3A;true,&quot;zoomControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;mapTypeControl&quot;&#x3A;true,&quot;mapTypeControlOptions&quot;&#x3A;&#x7B;&quot;mapTypeIds&quot;&#x3A;&#x5B;&#x5D;,&quot;position&quot;&#x3A;14,&quot;style&quot;&#x3A;0&#x7D;,&quot;streetViewControl&quot;&#x3A;true,&quot;streetViewControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;22&#x7D;,&quot;fullscreenControl&quot;&#x3A;true,&quot;fullscreenControlOptions&quot;&#x3A;&#x7B;&quot;position&quot;&#x3A;20&#x7D;&#x7D;,&quot;markers&quot;&#x3A;&#x5B;&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;48.8566,&quot;lng&quot;&#x3A;2.3522&#x7D;,&quot;title&quot;&#x3A;&quot;Paris&quot;,&quot;infoWindow&quot;&#x3A;null&#x7D;,&#x7B;&quot;position&quot;&#x3A;&#x7B;&quot;lat&quot;&#x3A;45.764,&quot;lng&quot;&#x3A;4.8357&#x7D;,&quot;title&quot;&#x3A;&quot;Lyon&quot;,&quot;infoWindow&quot;&#x3A;&#x7B;&quot;headerContent&quot;&#x3A;&quot;&lt;b&gt;Lyon&lt;&#x5C;&#x2F;b&gt;&quot;,&quot;content&quot;&#x3A;&quot;The&#x20;French&#x20;town&#x20;in&#x20;the&#x20;historic&#x20;Rh&#x5C;u00f4ne-Alpes&#x20;region,&#x20;located&#x20;at&#x20;the&#x20;junction&#x20;of&#x20;the&#x20;Rh&#x5C;u00f4ne&#x20;and&#x20;Sa&#x5C;u00f4ne&#x20;rivers.&quot;,&quot;position&quot;&#x3A;null,&quot;opened&quot;&#x3A;false,&quot;autoClose&quot;&#x3A;true&#x7D;&#x7D;&#x5D;&#x7D;"
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
