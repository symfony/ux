/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


import { Application } from '@hotwired/stimulus';
import { waitFor } from '@testing-library/dom';
import ChartjsController from '../src/controller';

// Kept track of globally, but just used in one test.
// This is because, by the time that test has run, it is likely that the
// chartjs:init event has already been dispatched. So, we capture it out here.
let initCallCount = 0;

const startChartTest = async (canvasHtml: string): Promise<{ canvas: HTMLCanvasElement, chart: Chart }> => {
    let chart: Chart | null = null;

    document.body.addEventListener('chartjs:init', () => {
        initCallCount++;
    });

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
        throw 'Missing ChartJS instance';
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

    it('will update when the view data changes without options', async () => {
        let viewValueChangeCallCount = 0;

        document.body.addEventListener('chartjs:view-value-change', (event: any) => {
            viewValueChangeCallCount++;
            event.detail.options.showLines = true;
        });

        const { chart, canvas } = await startChartTest(`
           <canvas
               data-testid='canvas'
               data-controller='check chartjs'
               data-chartjs-view-value="&#x7B;&quot;type&quot;&#x3A;&quot;line&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;labels&quot;&#x3A;&#x5B;&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;&#x5D;,&quot;datasets&quot;&#x3A;&#x5B;&#x7B;&quot;label&quot;&#x3A;&quot;My&#x20;First&#x20;dataset&quot;,&quot;backgroundColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;borderColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;data&quot;&#x3A;&#x5B;0,10,5,2,20,30,45&#x5D;&#x7D;&#x5D;&#x7D;,&quot;options&quot;&#x3A;&#x5B;&#x5D;&#x7D;"
           ></canvas>
       `);

        expect(chart.options.showLines).toBeUndefined();
        // change label: January -> NewDataJanuary
        const currentViewValue = JSON.parse('{"type":"line","data":{"labels":["NewDataJanuary","February","March","April","May","June","July"],"datasets":[{"label":"My First dataset","backgroundColor":"rgb(255, 99, 132)","borderColor":"rgb(255, 99, 132)","data":[0,10,5,2,20,30,45]}]},"options":[]}');
        canvas.dataset.chartjsViewValue = JSON.stringify(currentViewValue);

        await waitFor(() => {
            expect(chart.options.showLines).toBe(true);
            expect(viewValueChangeCallCount).toBe(1);
        });
    });

    it('dispatches the events correctly', async () => {
        let preConnectCallCount = 0;
        let preConnectDetail: any = null;

        document.body.addEventListener('chartjs:pre-connect', (event: any) => {
            preConnectCallCount++;
            preConnectDetail = event.detail;
        });

        await startChartTest(`
            <canvas
                data-testid="canvas"
                data-controller="chartjs"
                data-chartjs-view-value="&#x7B;&quot;type&quot;&#x3A;&quot;line&quot;,&quot;data&quot;&#x3A;&#x7B;&quot;labels&quot;&#x3A;&#x5B;&quot;January&quot;,&quot;February&quot;,&quot;March&quot;,&quot;April&quot;,&quot;May&quot;,&quot;June&quot;,&quot;July&quot;&#x5D;,&quot;datasets&quot;&#x3A;&#x5B;&#x7B;&quot;label&quot;&#x3A;&quot;My&#x20;First&#x20;dataset&quot;,&quot;backgroundColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;borderColor&quot;&#x3A;&quot;rgb&#x28;255,&#x20;99,&#x20;132&#x29;&quot;,&quot;data&quot;&#x3A;&#x5B;0,10,5,2,20,30,45&#x5D;&#x7D;&#x5D;&#x7D;,&quot;options&quot;&#x3A;&#x7B;&quot;showLines&quot;&#x3A;false&#x7D;&#x7D;"
            ></canvas>
        `);
        expect(initCallCount).toBe(1);
        expect(preConnectCallCount).toBe(1);
        expect(preConnectDetail.options.showLines).toBe(false);
        expect(preConnectDetail.config.type).toBe('line');
        expect(preConnectDetail.config.data.datasets[0].data).toEqual([0, 10, 5, 2, 20, 30, 45]);

        // add a second chart!
        const canvas = document.createElement('canvas');
        canvas.dataset.controller = 'chartjs';
        canvas.dataset.chartjsViewValue = JSON.stringify({
            type: 'line',
            data: {
                labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July'],
                datasets: [{
                    label: 'My First dataset',
                    backgroundColor: 'rgb(255, 99, 132)',
                    borderColor: 'rgb(255, 99, 132)',
                    data: [0, 10, 5, 2, 20, 30, 45],
                }],
            },
            options: {
                showLines: false,
            },
        });
        document.body.appendChild(canvas);

        await waitFor(() => expect(preConnectCallCount).toBe(2));
        // still only initialized once
        expect(initCallCount).toBe(1);
    });
});
