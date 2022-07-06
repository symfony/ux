/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { shutdownTest, createTest, initComponent } from '../tools';
import { createEvent, fireEvent, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController rendering Tests', () => {
    afterEach(() => {
        shutdownTest();
    })

    it('can re-render via an Ajax call', async () => {
        const test = await createTest({ firstName: 'Ryan' }, (data: any) => `
            <div ${initComponent(data)}>
                <span>Name: ${data.firstName}</span>
                <button data-action="live#$render">Reload</button>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // change the data on the server so the template renders differently
                data.firstName = 'Kevin';
            })
            .init();

        getByText(test.element, 'Reload').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Name: Kevin'));
        // data returned from the server is used for the new "data"
        expect(test.controller.dataValue).toEqual({firstName: 'Kevin'});
    });

    it('conserves the value of model field that was modified after a render request', async () => {
        const test = await createTest({ title: 'greetings', comment: '' }, (data: any) => `
            <div ${initComponent(data, { debounce: 1 })}>
                <input data-model="title" value="${data.title}">
                <!--
                    norender for comment to avoid triggering a 2nd Ajax call.
                    We want the first to finish, to see if it overwrites our value
                -->
                <textarea data-model="norender|comment">${data.comment}</textarea>
                
                Title: "${data.title}"
                Comment: "${data.comment}"

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        test.expectsAjaxCall('get')
            // only the update title will be sent
            .expectSentData({ title: 'greetings!!', comment: '' })
            .delayResponse(100)
            .init();

        userEvent.type(test.queryByDataModel('title'), '!!');

        setTimeout(() => {
            // wait 10 ms (long enough for the shortened debounce to finish and the
            // Ajax request to start) and then type into this field
            userEvent.type(test.queryByDataModel('comment'), 'I had a great time');
        }, 10);

        // title model updated like normal
        await waitFor(() => expect(test.element).toHaveTextContent('Title: "greetings!!"'));

        // no re-render yet since "comment" was modified
        expect(test.element).toHaveTextContent('Comment: ""')

        // the field contains the text that was typed by the user after the first Ajax call triggered
        expect((test.queryByDataModel('comment') as HTMLTextAreaElement).value).toEqual('I had a great time');

        // the server returned comment as ''. However, this model WAS set
        // during the last render, and that has not been taken into account yet.
        // and so, like with the comment textarea, the client-side value is kept
        expect(test.controller.dataValue).toEqual({
            title: 'greetings!!',
            comment: 'I had a great time'
        });

        // trigger render: the new comment data *will* now be sent
        test.expectsAjaxCall('get')
            // just repeat what we verified from above
            .expectSentData(test.controller.dataValue)
            .serverWillChangeData((data: any) => {
                // to be EXTRA complicated, the server will change the comment
                // on the client, we should now recognize that the latest comment
                // model data *was* sent (the <textarea> is no longer out-of-sync)
                // and so it should accept the HTML from the server
                data.comment = data.comment.toUpperCase();
            })
            .init();

        getByText(test.element, 'Reload').click();
        await waitFor(() => expect(test.element).toHaveTextContent('Comment: "I HAD A GREAT TIME"'));
        expect((test.queryByDataModel('comment') as HTMLTextAreaElement).value).toEqual('I HAD A GREAT TIME');
    });

    it('conserves the value of an unmapped field that was modified after a render request', async () => {
        const test = await createTest({ title: 'greetings' }, (data: any) => `
            <div ${initComponent(data, { debounce: 1 })}>
                <input data-model="title" value="${data.title}">
                <!-- An unmapped field -->
                <textarea></textarea>

                Title: "${data.title}"

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ title: 'greetings!!' })
            .delayResponse(100)
            .init();

        userEvent.type(test.queryByDataModel('title'), '!!');

        setTimeout(() => {
            // wait 10 ms (long enough for the shortened debounce to finish and the
            // Ajax request to start) and then type into this field
            userEvent.type(test.element.querySelector('textarea') as HTMLTextAreaElement, 'typing after the request starts');
        }, 10);

        // title model updated like normal
        await waitFor(() => expect(test.element).toHaveTextContent('Title: "greetings!!"'));
        // field *still* contains the text that was typed by the user after the Ajax call started
        expect((test.element.querySelector('textarea') as HTMLTextAreaElement).value).toEqual('typing after the request starts');

        // make a 2nd request
        test.expectsAjaxCall('get')
            .expectSentData({ title: 'greetings!! Yay!' })
            .delayResponse(100)
            .init();

        userEvent.type(test.queryByDataModel('title'), ' Yay!');

        // title model updated like normal
        await waitFor(() => expect(test.element).toHaveTextContent('Title: "greetings!! Yay!"'));
        // field *still* contains modified text
        expect((test.element.querySelector('textarea') as HTMLTextAreaElement).value).toEqual('typing after the request starts');
    });

    it('does not render over elements with data-live-ignore', async () => {
        const test = await createTest({ firstName: 'Ryan' }, (data: any) => `
            <div ${initComponent(data)}>
                <div data-live-ignore>Inside Ignore Name: <span>${data.firstName}</span></div>
                
                Outside Ignore Name: ${data.firstName}

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        // imitate some JavaScript changing this element
        test.element.querySelector('span')?.setAttribute('data-foo', 'bar');

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // change the data on the server so the template renders differently
                data.firstName = 'Kevin';
            })
            .init();

        getByText(test.element, 'Reload').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Outside Ignore Name: Kevin'));
        const ignoreElement = test.element.querySelector('div[data-live-ignore]');
        expect(ignoreElement).not.toBeNull();
        expect(ignoreElement?.outerHTML).toEqual('<div data-live-ignore="">Inside Ignore Name: <span data-foo="bar">Ryan</span></div>');
    });

    it('cancels a re-render if the page is navigating away', async () => {
        const test = await createTest({greeting: 'aloha!'}, (data: any) => `
            <div ${initComponent(data)}>${data.greeting}</div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data) => {
                data.greeting = 'Hello';
            })
            .delayResponse(100)
            .init();

        test.controller.$render();
        // imitate navigating away
        fireEvent(window, createEvent('beforeunload', window));

        // wait for the fetch to finish
        await fetchMock.flush();

        // the re-render should not have happened
        expect(test.element).not.toHaveTextContent('Hello');
    });
});
