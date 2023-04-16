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

describe('LiveController Event Dispatching Tests', () => {
    afterEach(() => {
        shutdownTests()
    });

    it('dispatches events sent from an AJAX request', async () => {
        const test = await createTest({ }, (data: any) => `
            <div ${initComponent(data, {
                name: 'simple-component',
            })}>Simple Component!</div>
        `);

        let eventCalled = false;
        test.element.addEventListener('fooEvent', (event: any) => {
            eventCalled = true;
            expect(event.detail).toEqual({ foo: 'bar' });
        });

        test.expectsAjaxCall()
            .willReturn(() => `
                <div ${initComponent({}, { browserDispatch: [
                        { event: 'fooEvent', payload: { foo: 'bar' } }
                    ]}
                )}>Simple Component!</div>
            `);

        await test.component.render();
        expect(eventCalled).toBe(true);
    });
});
