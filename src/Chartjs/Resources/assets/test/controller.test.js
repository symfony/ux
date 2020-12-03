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
import ChartjsController from '../dist/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('chartjs:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('chartjs', ChartjsController);
};

describe('ChartjsController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
            <canvas
                data-testid="canvas"
                data-controller="check chartjs"
                data-view="&#x7B;&quot;type&quot;&#x3A;&quot;line&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;labels&quot;&#x3A;&#x5B;&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;&#x5D;,&quot;datasets&quot;&#x3A;&#x5B;&#x7B;&quot;label&quot;&#x3A;&quot;My&#x20;First&#x20;dataset&quot;,&quot;backgroundColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;borderColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;data&quot;&#x3A;&#x5B;0,10,5,2,20,30,45&#x5D;&#x7D;&#x5D;&#x7D;,&quot;options&quot;&#x3A;&#x5B;&#x5D;&#x7D;"
            ></canvas>
        `);
    });

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        expect(getByTestId(container, 'canvas')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'canvas')).toHaveClass('connected'));
    });
});
