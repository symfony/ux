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
import { getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';
import { htmlToElement } from '../../src/dom_utils';

describe('LiveController rendering Tests', () => {
    afterEach(() => {
        shutdownTests();
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
        expect(test.component.valueStore.all()).toEqual({firstName: 'Kevin'});
    });

    it('conserves the value of model field that was modified after a render request', async () => {
        const test = await createTest({ title: 'greetings', comment: '' }, (data: any) => `
            <div ${initComponent(data, {}, { debounce: 1 })}>
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
        expect(test.component.valueStore.all()).toEqual({
            title: 'greetings!!',
            comment: 'I had a great time'
        });

        // trigger render: the new comment data *will* now be sent
        test.expectsAjaxCall('get')
            // just repeat what we verified from above
            .expectSentData(test.component.valueStore.all())
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
            <div ${initComponent(data, {}, { debounce: 1 })}>
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
        test.element.appendChild(htmlToElement('<div data-live-ignore>I should not be removed</div>'));

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
        expect(test.element.innerHTML).toContain('I should not be removed');
    });

    it('if data-live-id changes, data-live-ignore elements ARE re-rendered', async () => {
        const test = await createTest({ firstName: 'Ryan', containerId: 'original' }, (data: any) => `
            <div ${initComponent(data)}>
                <div data-live-id="${data.containerId}">
                    <div data-live-ignore>Inside Ignore Name: <span>${data.firstName}</span></div>
                </div>
                
                Outside Ignore Name: ${data.firstName}

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // change the data on the server so the template renders differently
                data.firstName = 'Kevin';
                data.containerId = 'updated';
            })
            .init();

        getByText(test.element, 'Reload').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Outside Ignore Name: Kevin'));
        const ignoreElement = test.element.querySelector('div[data-live-ignore]');
        expect(ignoreElement).not.toBeNull();
        // check that even the ignored element re-rendered
        expect(ignoreElement?.outerHTML).toEqual('<div data-live-ignore="">Inside Ignore Name: <span>Kevin</span></div>');
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

        test.component.render();
        // trigger disconnect
        test.element.removeAttribute('data-controller')

        // wait for the fetch to finish
        await fetchMock.flush();

        // the re-render should not have happened
        expect(test.element).not.toHaveTextContent('Hello');
    });

    it('renders if the page is navigating away and back', async () => {
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

        test.component.render();

        // trigger controller disconnect
        test.element.removeAttribute('data-controller')
        // wait for the fetch to finish
        await fetchMock.flush();

        expect(test.element).toHaveTextContent('aloha!')

        // trigger connect
        test.element.setAttribute('data-controller', 'live')
        test.component.render();
        // wait for the fetch to finish
        await fetchMock.flush();

        // the re-render should have happened
        expect(test.element).toHaveTextContent('Hello');
    });

    it('waits for the previous request to finish & batches changes', async () => {
        const test = await createTest({ title: 'greetings', contents: '' }, (data: any) => `
            <div ${initComponent(data, {}, { debounce: 1 })}>
                <input data-model="title" value="${data.title}">
                <textarea data-model="contents">${data.contents}</textarea>

                Title: "${data.title}"

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        // expect the initial Reload request, but delay it
        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .delayResponse(100)
            .init();

        getByText(test.element, 'Reload').click();

        setTimeout(() => {
            // wait 10 ms (long enough for the shortened debounce to finish and the
            // Ajax request to start) and then type into the fields
            userEvent.type(test.queryByDataModel('title'), '!!!');
            userEvent.type(test.queryByDataModel('contents'), 'Welcome to our test!');

            // NOW we're expecting th 2nd request
            test.expectsAjaxCall('get')
                // only 1 request, both new pieces of data sent at once
                .expectSentData({
                    title: 'greetings!!!',
                    contents: 'Welcome to our test!'
                })
                .init();
        }, 10);

        await waitFor(() => expect(test.element).toHaveTextContent('Title: "greetings!!!"'));
    });

    it('batches re-render requests together that occurred during debounce', async () => {
        const test = await createTest({ title: 'greetings', contents: '' }, (data: any) => `
            <div ${initComponent(data, {}, { debounce: 50 })}>
                <input data-model="title" value="${data.title}">
                <textarea data-model="contents">${data.contents}</textarea>

                Title: "${data.title}"

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        // type: 50ms debounce will begin
        userEvent.type(test.queryByDataModel('title'), ' to you');
        setTimeout(() => {
            // wait 40 ms: not long enough for debounce, then modify this field
            userEvent.type(test.queryByDataModel('contents'), 'Welcome to our test!');

            // the ONE request will not start until 50ms (debounce) from now
            // delay 40 ms before we start to expect it
            setTimeout(() => {
                // just one request should be made
                test.expectsAjaxCall('get')
                    .expectSentData({ title: 'greetings to you', contents: 'Welcome to our test!'})
                    .init();
                }, 40)
        }, 40);

        await waitFor(() => expect(test.element).toHaveTextContent('Title: "greetings to you"'));
    });
});
