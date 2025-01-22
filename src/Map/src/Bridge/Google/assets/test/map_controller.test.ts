/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application, Controller } from '@hotwired/stimulus';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import { getByTestId, waitFor } from '@testing-library/dom';
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
    application.register('symfony--ux-google-map--map', GoogleController);
};

describe('GoogleMapsController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
          <div 
              data-testid="map"
              data-controller="check symfony--ux-google-map--map" 
              data-symfony--ux-google-map--map-provider-options-value="{&quot;version&quot;:&quot;weekly&quot;,&quot;libraries&quot;:[&quot;maps&quot;,&quot;marker&quot;],&quot;apiKey&quot;:&quot;&quot;}" 
              data-symfony--ux-google-map--map-center-value="{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522}" 
              data-symfony--ux-google-map--map-zoom-value="7" 
              data-symfony--ux-google-map--map-fit-bounds-to-markers-value="false" 
              data-symfony--ux-google-map--map-options-value="{&quot;mapId&quot;:null,&quot;gestureHandling&quot;:&quot;auto&quot;,&quot;backgroundColor&quot;:null,&quot;maxZoom&quot;:null,&quot;disableDoubleClickZoom&quot;:false,&quot;zoomControlOptions&quot;:{&quot;position&quot;:22},&quot;mapTypeControlOptions&quot;:{&quot;mapTypeIds&quot;:[],&quot;position&quot;:14,&quot;style&quot;:0},&quot;streetViewControlOptions&quot;:{&quot;position&quot;:22},&quot;fullscreenControlOptions&quot;:{&quot;position&quot;:20},&quot;@provider&quot;:&quot;google&quot;}" 
              data-symfony--ux-google-map--map-markers-value="[]" 
              data-symfony--ux-google-map--map-polygons-value="[]" 
              data-symfony--ux-google-map--map-polylines-value="[]" 
              style="height: 600px"
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
