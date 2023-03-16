/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application } from '@hotwired/stimulus';
import { waitFor } from '@testing-library/dom';
import ChartjsController from '../src/controller';

const startChartTest = async (canvasHtml: string): Promise<{ canvas: HTMLCanvasElement, chart: Chart }> => {
    let chart: Chart | null = null;

    document.body.addEventListener('chartjs:pre-connect', () => {
        document.body.classList.add('pre-connected');
    });

    document.body.addEventListener('chartjs:connect', (event: any) => {
        chart = (event.detail).chart;
        document.body.classList.add('connected');
    });

    document.body.innerHTML = canvasHtml;
    const canvasElement = document.querySelector('canvas');
    if (!canvasElement) {
        throw 'Missing canvas element';
    }

    await waitFor(() => {
        expect(document.body).toHaveClass('pre-connected');
        expect(document.body).toHaveClass('connected');
    });

    if (!chart) {
        throw 'Missing TomSelect instance';
    }

    return { canvas: canvasElement, chart };
}

describe('ChartjsController', () => {
    beforeAll(() => {
        const application = Application.start();
        application.register('chartjs', ChartjsController);
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('connect without options', async () => {
        const { chart } = await startChartTest(`
            <canvas
                data-testid="canvas"
                data-controller="chartjs"
                data-chartjs-view-value="&#x7B;&quot;type&quot;&#x3A;&quot;line&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;labels&quot;&#x3A;&#x5B;&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;&#x5D;,&quot;datasets&quot;&#x3A;&#x5B;&#x7B;&quot;label&quot;&#x3A;&quot;My&#x20;First&#x20;dataset&quot;,&quot;backgroundColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;borderColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;data&quot;&#x3A;&#x5B;0,10,5,2,20,30,45&#x5D;&#x7D;&#x5D;&#x7D;,&quot;options&quot;&#x3A;&#x5B;&#x5D;&#x7D;"
            ></canvas>
        `);

        expect(chart.options.showLines).toBeUndefined();
    });

    it('connect with options', async () => {
        const { chart } = await startChartTest(`
            <canvas
                data-testid="canvas"
                data-controller="check chartjs"
                data-chartjs-view-value="&#x7B;&quot;type&quot;&#x3A;&quot;line&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;labels&quot;&#x3A;&#x5B;&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;&#x5D;,&quot;datasets&quot;&#x3A;&#x5B;&#x7B;&quot;label&quot;&#x3A;&quot;My&#x20;First&#x20;dataset&quot;,&quot;backgroundColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;borderColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;data&quot;&#x3A;&#x5B;0,10,5,2,20,30,45&#x5D;&#x7D;&#x5D;&#x7D;,&quot;options&quot;&#x3A;&#x7B;&quot;showLines&quot;&#x3A;false&#x7D;&#x7D;"
            ></canvas>
        `);

        expect(chart.options.showLines).toBe(false);
    });

    it('will update when the view data changes', async () => {
        const { chart, canvas } = await startChartTest(`
           <canvas
               data-testid='canvas'
               data-controller='check chartjs'
               data-chartjs-view-value="&#x7B;&quot;type&quot;&#x3A;&quot;line&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;labels&quot;&#x3A;&#x5B;&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;&#x5D;,&quot;datasets&quot;&#x3A;&#x5B;&#x7B;&quot;label&quot;&#x3A;&quot;My&#x20;First&#x20;dataset&quot;,&quot;backgroundColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;borderColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;data&quot;&#x3A;&#x5B;0,10,5,2,20,30,45&#x5D;&#x7D;&#x5D;&#x7D;,&quot;options&quot;&#x3A;&#x7B;&quot;showLines&quot;&#x3A;false&#x7D;&#x7D;"
           ></canvas>
       `);

        expect(chart.options.showLines).toBe(false);
        const currentViewValue = JSON.parse((canvas.dataset.chartjsViewValue as string));
        currentViewValue.options.showLines = true;
        canvas.dataset.chartjsViewValue = JSON.stringify(currentViewValue);

        await waitFor(() => {
            expect(chart.options.showLines).toBe(true);
        });
    });
});
