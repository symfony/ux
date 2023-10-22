/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { shutdownTests, createTest, initComponent } from '../tools';
import { waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

describe('LiveController polling Tests', () => {
    afterEach(() => {
        shutdownTests();
    })

    it('starts a poll', async () => {
        const test = await createTest({ renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} data-poll>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            });
        // poll 2
        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.renderCount = 2;
            });

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 2100
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 2100
        });
    });

    it('is controllable via modifiers', async () => {
        const test = await createTest({ renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} data-poll="delay(250)|$render">
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            });
        // poll 2
        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.renderCount = 2;
            });

        // only wait for about 250ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 300
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 300
        });
    });

    it('can also call a live action', async () => {
        const test = await createTest({ renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} data-poll="delay(250)|saveAction">
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall()
            .expectActionCalled('saveAction')
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            });
        // poll 2
        test.expectsAjaxCall()
            .expectActionCalled('saveAction')
            .serverWillChangeProps((data: any) => {
                data.renderCount = 2;
            });

        // only wait for about 250ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 300
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 300
        });
    });

    it('polling should stop if data-poll is removed', async () => {
        const test = await createTest({ keepPolling: true, renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} ${data.keepPolling ? 'data-poll="delay(250)|$render"' : ''}>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            });
        // poll 2
        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.renderCount = 2;
                data.keepPolling = false;
            });

        // only wait for about 250ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 300
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 300
        });
        // wait 500ms more... no more Ajax calls should be made
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => {
                resolve(true);
            }, 500);
        });
        await waitFor(() => timeoutPromise, {
            timeout: 750
        });
    });

    it('stops polling after it disconnects', async () => {
       const test = await createTest({ renderCount: 0 }, (data: any) => `
           <div ${initComponent(data)} data-poll="delay(250)|$render">
               <span>Render count: ${data.renderCount}</span>
           </div>
       `);

       // poll 1
       test.expectsAjaxCall()
           .serverWillChangeProps((data: any) => {
               data.renderCount = 1;
           });

       // only wait for about 250ms this time
       await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
           timeout: 300
       });
       // "remove" our controller from the page
       document.body.innerHTML = '<div>something else</div>';
        // wait 500ms more second... no more Ajax calls should be made
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => {
                resolve(true);
            }, 500);
        });
        await waitFor(() => timeoutPromise, {
            timeout: 750
        });
   });

    it('waits to send the request if request is already happening', async () => {
        const test = await createTest({ renderCount: 0, name: 'Ryan' }, (data: any) => `
            <div ${initComponent(data, { debounce: 1 })} data-poll="delay(50)|$render">
                <input
                    data-model="name"
                    value="${data.name}"
                >

                <span>Name: ${data.name}</span>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // First request, from typing (debouncing is set to 1ms)
        test.expectsAjaxCall()
            .expectUpdatedData({ name: 'Ryan Weaver'})
            .serverWillChangeProps((data: any) => {
                data.renderCount = 1;
            })
            .delayResponse(100);

        await userEvent.type(test.queryByDataModel('name'), ' Weaver');

        setTimeout(() => {
            // first poll, should happen after 50 ms, but needs to wait the full 100
            test.expectsAjaxCall()
                .serverWillChangeProps((data: any) => {
                    data.renderCount = 2;
                });
        }, 75)

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'));
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'));
    });
});
