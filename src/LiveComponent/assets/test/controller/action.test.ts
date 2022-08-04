/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { createTest, initComponent, shutdownTest } from '../tools';
import { getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

describe('LiveController Action Tests', () => {
    afterEach(() => {
        shutdownTest();
    })

    it('sends an action and cancels pending (debounce) re-renders', async () => {
        const test = await createTest({ comment: '', isSaved: false }, (data: any) => `
            <div ${initComponent(data)}>
                <input data-model="comment" value="${data.comment}">

                ${data.isSaved ? 'Comment Saved!' : ''}

                <button data-action="live#action" data-action-name="save">Save</button>
            </div>
        `);

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall('post')
            .expectSentData({
                comment: 'great turtles!',
                isSaved: false
            })
            .expectActionCalled('save')
            .serverWillChangeData((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            })
            .init();

        await userEvent.type(test.queryByDataModel('comment'), 'great turtles!');
        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Comment Saved!'));
    });

    it('Sends action with named args', async () => {
        const test = await createTest({ isSaved: false}, (data: any) => `
           <div ${initComponent(data)}>
               ${data.isSaved ? 'Component Saved!' : ''}

               <button data-action="live#action" data-action-name="sendNamedArgs(a=1, b=2, c=3)">Send named args</button>
           </div>
       `);

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall('post')
            .expectSentData({ isSaved: false })
            .expectActionCalled('sendNamedArgs', {a: 1, b: 2, c: 3})
            .serverWillChangeData((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            })
            .init();

       getByText(test.element, 'Send named args').click();

       await waitFor(() => expect(test.element).toHaveTextContent('Component Saved!'));
    });

    it('sends an action but allows for the model to be updated', async () => {
       const test = await createTest({ food: '' }, (data: any) => `
           <div ${initComponent(data)}>
               <select
                   data-model="on(change)|food"
                   data-action="live#action"
                   data-action-name="changeFood"
               >
                   <option value="" ${data.food === '' ? 'selected' : ''}>Choose a food</option>
                   <option value="pizza" ${data.pizza === '' ? 'selected' : ''}>Pizza</option>
                   <option value="mango" ${data.mango === '' ? 'selected' : ''}>Mango</option>
               </select>

               Food: ${data.food}
           </div>
       `);

        // ONLY a post is sent
        // the re-render GET from "input" of the select should be avoided
        // because an action immediately happens
        test.expectsAjaxCall('post')
           .expectSentData({ food: 'pizza' })
           .expectActionCalled('changeFood')
           .init();

        await userEvent.selectOptions(test.queryByDataModel('food'), 'pizza');

        await waitFor(() => expect(test.element).toHaveTextContent('Food: pizza'));
   });

    it('prevents re-render model updates while action Ajax is pending', async () => {
        const test = await createTest({ comment: 'donut', isSaved: false }, (data: any) => `
            <div ${initComponent(data)}>
                <input data-model="comment" value="${data.comment}">

                ${data.isSaved ? 'Comment Saved!' : ''}

                <span>${data.comment}</span>

                <button data-action="live#action" data-action-name="save">Save</button>
                <button data-action="live#$render">Reload</button>
            </div>
        `);

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectActionCalled('save')
            .delayResponse(1000) // longer than debounce, so updating comment could potentially send a request
            .serverWillChangeData((data: any) => {
                // server marks component as "saved"
                data.isSaved = true;
            })
            .init();

        // save first, then type into the box
        getByText(test.element, 'Save').click();
        await userEvent.type(test.queryByDataModel('comment'), ' holes');

        await waitFor(() => expect(test.element).toHaveTextContent('Comment Saved!'));
        // render has not happened yet
        expect(test.element).not.toHaveTextContent('donut holes');

        // trigger a render, it should now reflect the changed value
        test.expectsAjaxCall('get')
            .expectSentData({comment: 'donut holes', isSaved: true})
            .init();
        getByText(test.element, 'Reload').click();
        await waitFor(() => expect(test.element).toHaveTextContent('donut holes'));

        // now check that model updating works again
        test.expectsAjaxCall('get')
            .expectSentData({comment: 'donut holes are delicious', isSaved: true})
            .init();
        await userEvent.type(test.queryByDataModel('comment'), ' are delicious');
        await waitFor(() => expect(test.element).toHaveTextContent('donut holes are delicious'));
    });
});
