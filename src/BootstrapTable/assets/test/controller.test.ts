/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import { Application, Controller } from '@hotwired/stimulus';
import BootstrapTable from '../src/controller';
import { getByTestId, waitFor } from '@testing-library/dom';

class CheckController extends Controller {
    connect() {
        this.element.addEventListener('bootstrap-table:pre-connect', () => {
            this.element.classList.add('pre-connected');
        });

        this.element.addEventListener('bootstrap-table:post-connect', (event) => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('table', BootstrapTable);

    return application;
}

describe('BootstrapTableController', () => {
    let application;

    afterEach(() => {
        clearDOM();
        application.stop();
    });

    it('connect controller', async () => {
        const container = mountDOM(`
            <table
                data-testid="tableTest"
                data-controller='table check'
                data-table-rows-value='&#x5B;&#x7B;&quot;id&quot;&#x3A;1,&quot;pseudo&quot;&#x3A;&quot;Bob&quot;,&quot;age&quot;&#x3A;23,&quot;gender&quot;&#x3A;&quot;M&quot;&#x7D;,&#x7B;&quot;id&quot;&#x3A;2,&quot;pseudo&quot;&#x3A;&quot;Kitty&quot;,&quot;age&quot;&#x3A;36,&quot;gender&quot;&#x3A;&quot;F&quot;&#x7D;,&#x7B;&quot;id&quot;&#x3A;3,&quot;pseudo&quot;&#x3A;&quot;Spongy&quot;,&quot;age&quot;&#x3A;34,&quot;gender&quot;&#x3A;&quot;M&quot;&#x7D;,&#x7B;&quot;id&quot;&#x3A;4,&quot;pseudo&quot;&#x3A;&quot;Slurp&quot;,&quot;age&quot;&#x3A;20,&quot;gender&quot;&#x3A;&quot;F&quot;&#x7D;,&#x7B;&quot;id&quot;&#x3A;5,&quot;pseudo&quot;&#x3A;&quot;Zoom&quot;,&quot;age&quot;&#x3A;29,&quot;gender&quot;&#x3A;&quot;M&quot;&#x7D;&#x5D;'
                data-table-columns-value='&#x5B;&#x7B;&quot;title&quot;&#x3A;&quot;ID&quot;,&quot;field&quot;&#x3A;&quot;id&quot;&#x7D;,&#x7B;&quot;title&quot;&#x3A;&quot;Pseudo&quot;,&quot;field&quot;&#x3A;&quot;pseudo&quot;&#x7D;,&#x7B;&quot;title&quot;&#x3A;&quot;Age&quot;,&quot;field&quot;&#x3A;&quot;age&quot;&#x7D;,&#x7B;&quot;title&quot;&#x3A;&quot;Gender&quot;,&quot;field&quot;&#x3A;&quot;gender&quot;&#x7D;&#x5D;'
                data-table-options-value='&#x7B;&quot;pagination&quot;&#x3A;&quot;true&quot;,&quot;search&quot;&#x3A;&quot;true&quot;&#x7D;'
            ></table>
        `)

        expect(getByTestId(container, 'tableTest')).not.toHaveClass('table');

        application = startStimulus();

        await waitFor(() => {
            expect(getByTestId(container, 'tableTest')).toHaveClass('pre-connected');
            expect(getByTestId(container, 'tableTest')).toHaveClass('connected');
        })
    })
})
