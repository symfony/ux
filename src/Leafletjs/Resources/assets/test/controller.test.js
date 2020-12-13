/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from 'stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
// import LeafletjsController from '../dist/controller';
import LeafletjsController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('leafletjs:connect', (event) => {
            this.element.classList.add('connected');
            this.element.map = event.detail.map;
            this.element.layer = event.detail.layer;
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('leafletjs', LeafletjsController);
    return application;
};

describe('LeafletjsController', () => {
    let container;

    afterEach(() => {
        clearDOM();
    });

    it('connect without options', async () => {
        container = mountDOM(`
            <div
                data-testid="map"
                data-controller="check leafletjs"
                data-leafletjs-target="placeholder"
                data-leafletjs-zoom="10"
                data-leafletjs-latitude="51.505"
                data-leafletjs-longitude="-0.09"
            ></div>
        `);

        expect(getByTestId(container, 'map')).not.toHaveClass('connected');

        let stimulus = startStimulus();
        await waitFor(() => expect(getByTestId(container, 'map')).toHaveClass('connected'));

        let byTestId = getByTestId(container, 'map');
        expect(byTestId.layer).toBeDefined();
        expect(byTestId.layer._url).toBe('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png');

        expect(byTestId.map).toBeDefined();
        expect(byTestId.map._loaded).toBe(true);
        expect(byTestId.map._zoom).toBe(10);
        expect(byTestId.map._lastCenter.lat).toBe(51.505);
        expect(byTestId.map._lastCenter.lng).toBe(-0.09);

        stimulus.stop();
    });

    it('connect with options', async () => {
        container = mountDOM(`
            <div
                data-testid="map"
                data-controller="check leafletjs"
                data-leafletjs-target="placeholder"
                data-leafletjs-latitude="51.505"
                data-leafletjs-longitude="-0.09"
                data-leafletjs-zoom="10"
                data-leafletjs-map-options="&#x7B;&quot;minZoom&quot;&#x3A;1,&quot;maxZoom&quot;&#x3A;5&#x7D;"
            ></div>
        `);

        expect(getByTestId(container, 'map')).not.toHaveClass('connected');

        let stimulus = startStimulus();
        await waitFor(() => expect(getByTestId(container, 'map')).toHaveClass('connected'));

        let byTestId = getByTestId(container, 'map');
        expect(byTestId.map).toBeDefined();
        expect(byTestId.map.options.minZoom).toBe(1);
        expect(byTestId.map.options.maxZoom).toBe(5);

        stimulus.stop();
    });
});
