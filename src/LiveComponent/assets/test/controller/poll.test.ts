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
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.renderCount = 1;
            })
            .init();
        // poll 2
        test.expectsAjaxCall('get')
            .expectSentData({renderCount: 1})
            .serverWillChangeData((data: any) => {
                data.renderCount = 2;
            })
            .init();

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 2100
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 2100
        });
    });

    it('is controllable via modifiers', async () => {
        const test = await createTest({ renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} data-poll="delay(500)|$render">
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.renderCount = 1;
            })
            .init();
        // poll 2
        test.expectsAjaxCall('get')
            .expectSentData({renderCount: 1})
            .serverWillChangeData((data: any) => {
                data.renderCount = 2;
            })
            .init();

        // only wait for about 500ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 600
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 600
        });
    });

    it('can also call a live action', async () => {
        const test = await createTest({ renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} data-poll="delay(500)|saveAction">
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectActionCalled('saveAction')
            .serverWillChangeData((data: any) => {
                data.renderCount = 1;
            })
            .init();
        // poll 2
        test.expectsAjaxCall('post')
            .expectSentData({renderCount: 1})
            .expectActionCalled('saveAction')
            .serverWillChangeData((data: any) => {
                data.renderCount = 2;
            })
            .init();

        // only wait for about 500ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 600
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 600
        });
    });

    it('polling should stop if data-poll is removed', async () => {
        const test = await createTest({ keepPolling: true, renderCount: 0 }, (data: any) => `
            <div ${initComponent(data)} ${data.keepPolling ? 'data-poll="delay(500)|$render"' : ''}>
                <span>Render count: ${data.renderCount}</span>
            </div>
        `);

        // poll 1
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                data.renderCount = 1;
            })
            .init();
        // poll 2
        test.expectsAjaxCall('get')
            .expectSentData({keepPolling: true, renderCount: 1})
            .serverWillChangeData((data: any) => {
                data.renderCount = 2;
                data.keepPolling = false;
            })
            .init();

        // only wait for about 500ms this time
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
            timeout: 600
        });
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'), {
            timeout: 600
        });
        // wait 1 more second... no more Ajax calls should be made
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => {
                resolve(true);
            }, 1000);
        });
        await waitFor(() => timeoutPromise, {
            timeout: 1500
        });
    });

    it('stops polling after it disconnects', async () => {
       const test = await createTest({ renderCount: 0 }, (data: any) => `
           <div ${initComponent(data)} data-poll="delay(500)|$render">
               <span>Render count: ${data.renderCount}</span>
           </div>
       `);

       // poll 1
       test.expectsAjaxCall('get')
           .expectSentData(test.initialData)
           .serverWillChangeData((data: any) => {
               data.renderCount = 1;
           })
           .init();

       // only wait for about 500ms this time
       await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'), {
           timeout: 600
       });
       // "remove" our controller from the page
       document.body.innerHTML = '<div>something else</div>';
        // wait 1 more second... no more Ajax calls should be made
        const timeoutPromise = new Promise((resolve) => {
            setTimeout(() => {
                resolve(true);
            }, 1000);
        });
        await waitFor(() => timeoutPromise, {
            timeout: 1500
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
        test.expectsAjaxCall('get')
            .expectSentData({ renderCount: 0, name: 'Ryan Weaver'})
            .serverWillChangeData((data: any) => {
                data.renderCount = 1;
            })
            .delayResponse(100)
            .init();

        await userEvent.type(test.queryByDataModel('name'), ' Weaver');

        setTimeout(() => {
            // first poll, should happen after 50 ms, but needs to wait the full 100
            test.expectsAjaxCall('get')
                .expectSentData({renderCount: 1, name: 'Ryan Weaver'})
                .serverWillChangeData((data: any) => {
                    data.renderCount = 2;
                })
                .init();
        }, 75)

        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 1'));
        await waitFor(() => expect(test.element).toHaveTextContent('Render count: 2'));
    });
});
