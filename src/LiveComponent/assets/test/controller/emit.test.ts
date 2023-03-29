/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import {createTest, initComponent, shutdownTests} from '../tools';
import { getByText, waitFor } from '@testing-library/dom';

describe('LiveController Emit Tests', () => {
    afterEach(() => {
        shutdownTests()
    });

    it('emits event using emit()', async () => {
        const test = await createTest({ renderCount: 0 }, (data: any) => `
            <div ${initComponent(data, {
                name: 'simple-component',
                listeners: [{ event: 'fooEvent', action: 'fooAction' }]
            })}>
                Render Count: ${data.renderCount}
                <button
                    data-action="live#emit"
                    data-event="fooEvent"
                >Emit Simple</button>

                <button
                    data-action="live#emit"
                    data-event="name(simple-component)|fooEvent"
                >Emit Named Matching</button>

                <button
                    data-action="live#emit"
                    data-event="name(other-component)|fooEvent"
                >Emit Named Not Matching</button>
            </div>
        `);

        test.expectsAjaxCall()
            .expectActionCalled('fooAction')
            .serverWillChangeProps((data) => {
                data.renderCount = 1;
            })

        getByText(test.element, 'Emit Simple').click();
        await waitFor(() => expect(test.element).toHaveTextContent('Render Count: 1'));

        test.expectsAjaxCall()
            .expectActionCalled('fooAction')
            .serverWillChangeProps((data) => {
                data.renderCount = 2;
            })

        getByText(test.element, 'Emit Named Matching').click();
        await waitFor(() => expect(test.element).toHaveTextContent('Render Count: 2'));

        // no request here
        getByText(test.element, 'Emit Named Not Matching').click();
        // wait a tiny bit - enough for a request to be sent if it was going to be
        await new Promise((resolve) => setTimeout(resolve, 10));
    });
});
