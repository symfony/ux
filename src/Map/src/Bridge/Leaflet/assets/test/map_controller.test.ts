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
import { clearDOM, mountDOM } from '../../../../../../../test/stimulus-helpers';
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
    application.register('symfony--ux-leaflet-map--map', LeafletController);
};

describe('LeafletController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        container = mountDOM(`
          <div
              data-testid="map"
              data-controller="check symfony--ux-leaflet-map--map"
              data-symfony--ux-leaflet-map--map-provider-options-value="{}"
              data-symfony--ux-leaflet-map--map-center-value="{&quot;lat&quot;:48.8566,&quot;lng&quot;:2.3522}"
              data-symfony--ux-leaflet-map--map-zoom-value="7"
              data-symfony--ux-leaflet-map--map-fit-bounds-to-markers-value="false"
              data-symfony--ux-leaflet-map--map-options-value="{&quot;tileLayer&quot;:{&quot;url&quot;:&quot;https:\\/\\/tile.openstreetmap.org\\/{z}\\/{x}\\/{y}.png&quot;,&quot;attribution&quot;:&quot;\u00a9 &lt;a href=\\&quot;https:\\/\\/www.openstreetmap.org\\/copyright\\&quot;&gt;OpenStreetMap&lt;\\/a&gt;&quot;,&quot;options&quot;:[]},&quot;@provider&quot;:&quot;leaflet&quot;}"
              data-symfony--ux-leaflet-map--map-markers-value="[]"
              data-symfony--ux-leaflet-map--map-polygons-value="[]"
              data-symfony--ux-leaflet-map--map-polylines-value="[]"
              data-symfony--ux-leaflet-map--map-circles-value="[]"
              data-symfony--ux-leaflet-map--map-rectangles-value="[]"
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
