/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { createTest, initComponent, shutdownTests } from '../tools';
import { getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

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
});
