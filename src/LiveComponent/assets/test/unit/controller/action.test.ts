/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it } from 'vitest';
import { createTest, initComponent, shutdownTests } from '../../tools';

describe('LiveController Action Tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('sends an action and renders the result', async () => {
        const test = await createTest(
            { comment: 'great turtles!', isSaved: false },
            (data: any) => `
            <div ${initComponent(data)}>
                ${data.isSaved ? 'Comment Saved!' : ''}

                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            });

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Comment Saved!'));
    });

    it('immediately sends an action, includes debouncing model updates and cancels those debounce renders', async () => {
        const test = await createTest(
            { comment: '', isSaved: false },
            (data: any) => `
            <div ${initComponent(data, { debounce: 10 })}>
                <input data-model="comment" value="${data.comment}">

                ${data.isSaved ? 'Comment Saved!' : ''}

                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );

        // JUST the POST request: no other GET requests
        test.expectsAjaxCall()
            .expectUpdatedData({
                comment: 'great tortugas!',
            })
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            });

        await userEvent.type(test.queryByDataModel('comment'), 'great tortugas!');
        // type immediately, still during the model debounce
        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Comment Saved!'));

        // wait long enough for the debounced model update to happen, if it wasn't canceled
        await new Promise((resolve) => setTimeout(resolve, 50));
    });

    it('Sends action with named args', async () => {
        const test = await createTest(
            { isSaved: false },
            (data: any) => `
            <div ${initComponent(data)}>
                ${data.isSaved ? 'Component Saved!' : ''}

                <button
                    data-action="live#action"
                    data-live-action-param="sendNamedArgs"
                    data-live-a-param="1"
                    data-live-b-param="2"
                    data-live-c-param="banana"
                >Send named args</button>
            </div>
       `
        );

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall()
            .expectActionCalled('sendNamedArgs', { a: 1, b: 2, c: 'banana' })
            .serverWillChangeProps((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            });

        getByText(test.element, 'Send named args').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Component Saved!'));
    });

    it('sends an action but allows for the model to be updated', async () => {
        const test = await createTest(
            { food: '' },
            (data: any) => `
           <div ${initComponent(data)}>
               <select
                   data-model="on(change)|food"
                   data-action="live#action"
                   data-live-action-param="changeFood"
               >
                   <option value="" ${data.food === '' ? 'selected' : ''}>Choose a food</option>
                   <option value="pizza" ${data.pizza === '' ? 'selected' : ''}>Pizza</option>
                   <option value="mango" ${data.mango === '' ? 'selected' : ''}>Mango</option>
               </select>

               Food: ${data.food}
           </div>
       `
        );

        // ONLY a post is sent
        // the re-render GET from "input" of the select should be avoided
        // because an action immediately happens
        test.expectsAjaxCall().expectUpdatedData({ food: 'pizza' }).expectActionCalled('changeFood');

        await userEvent.selectOptions(test.queryByDataModel('food'), 'pizza');

        await waitFor(() => expect(test.element).toHaveTextContent('Food: pizza'));
    });

    it('makes model updates wait until action Ajax call finishes', async () => {
        const test = await createTest(
            { comment: 'donut', isSaved: false },
            (data: any) => `
            <div ${initComponent(data, { debounce: 50 })}>
                <input data-model="comment" value="${data.comment}">

                ${data.isSaved ? 'Comment Saved!' : ''}

                <span>${data.comment}</span>

                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall()
            .expectActionCalled('save')
            .delayResponse(100) // longer than debounce, so updating comment could potentially send a request
            .serverWillChangeProps((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            });

        // the model re-render shouldn't happen until after the action ajax finishes,
        // which will take 100ms. So, don't start expecting it until nearly then
        // but after the model debounce
        setTimeout(() => {
            test.expectsAjaxCall().expectUpdatedData({ comment: 'donut holes' });
        }, 75);

        // save first, then type into the box
        getByText(test.element, 'Save').click();
        // slight pause (should allow action request to start), then start typing
        setTimeout(() => {
            userEvent.type(test.queryByDataModel('comment'), ' holes');
        }, 10);

        await waitFor(() => expect(test.element).toHaveTextContent('Comment Saved!'));
        // render has not happened yet
        expect(test.element).not.toHaveTextContent('donut holes');
        // but soon the re-render does happen
        await waitFor(() => expect(test.element).toHaveTextContent('donut holes'));
    });

    it('batches multiple actions together', async () => {
        const test = await createTest(
            { isSaved: false },
            (data: any) => `
            <div ${initComponent(data)}>
                ${data.isSaved ? 'Component Saved!' : ''}
                <button data-action="live#action" data-live-action-param="debounce(10)|save">Save</button>
                <button data-action="live#action" data-live-action-param="debounce(10)|sync" data-live-sync-all-param="1">Sync</button>
            </div>
        `
        );

        // 1 request with all 3 actions
        test.expectsAjaxCall()
            // 3 actions called
            .expectActionCalled('save')
            .expectActionCalled('sync', { syncAll: 1 })
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                data.isSaved = true;
            });

        getByText(test.element, 'Save').click();
        getByText(test.element, 'Sync').click();
        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Component Saved!'));
    });

    it('caps each batch at 50 actions and ships the overflow in a follow-up request', async () => {
        const test = await createTest(
            { count: 0 },
            (data: any) => `<div ${initComponent(data)}>count: ${data.count}</div>`
        );

        // 51 actions queued back-to-back must be split into 50 + 1, never one oversized request.
        // Distinct {i: N} args on every call pin both count AND ordering: a reorder or
        // misplacement across batches fails the matcher (matches() uses ordered isEqual).
        const firstBatch = test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.count = 50;
        });
        for (let i = 0; i < 50; i++) {
            firstBatch.expectActionCalled('save', { i });
        }

        const secondBatch = test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.count = 51;
        });
        secondBatch.expectActionCalled('save', { i: 50 });

        for (let i = 0; i < 51; i++) {
            test.component.action('save', { i });
        }

        await waitFor(() => expect(test.element).toHaveTextContent('count: 51'));
    });

    it('sends a single request for an exact-fit batch of 50 actions', async () => {
        const test = await createTest(
            { count: 0 },
            (data: any) => `<div ${initComponent(data)}>count: ${data.count}</div>`
        );

        const batch = test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.count = 50;
        });
        for (let i = 0; i < 50; i++) {
            batch.expectActionCalled('save', { i });
        }

        for (let i = 0; i < 50; i++) {
            test.component.action('save', { i });
        }

        await waitFor(() => expect(test.element).toHaveTextContent('count: 50'));
    });

    it('caps the follow-up batch when actions queue up while a request is in-flight', async () => {
        const test = await createTest(
            { count: 0 },
            (data: any) => `<div ${initComponent(data)}>count: ${data.count}</div>`
        );

        // First request: a single debounced action, delayed so we can queue more while it's pending.
        const firstBatch = test
            .expectsAjaxCall()
            .expectActionCalled('save', { i: 0 })
            .delayResponse(30)
            .serverWillChangeProps((data: any) => {
                data.count = 1;
            });

        // Second request: indices 1..50 (cap exactly hit).
        const secondBatch = test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.count = 51;
        });
        for (let i = 1; i <= 50; i++) {
            secondBatch.expectActionCalled('save', { i });
        }

        // Third request: indices 51..60 (overflow from the 60 queued during the in-flight request).
        const thirdBatch = test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.count = 61;
        });
        for (let i = 51; i <= 60; i++) {
            thirdBatch.expectActionCalled('save', { i });
        }

        // Kick off the first request (debounce=0 → flushes immediately).
        test.component.action('save', { i: 0 });
        // Wait a tick so the first request is in-flight before we enqueue the rest.
        await new Promise((resolve) => setTimeout(resolve, 5));
        for (let i = 1; i <= 60; i++) {
            test.component.action('save', { i });
        }

        await waitFor(() => expect(test.element).toHaveTextContent('count: 61'));
    });
});
