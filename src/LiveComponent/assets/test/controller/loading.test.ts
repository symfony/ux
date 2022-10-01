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
import {getByTestId, getByText, waitFor} from '@testing-library/dom';
import userEvent from "@testing-library/user-event";

describe('LiveController data-loading Tests', () => {
    afterEach(() => {
        shutdownTests();
    })

    it('executes basic loading functionality on an element', async () => {
        const test = await createTest({food: 'pizza'}, (data: any) => `
            <div ${initComponent(data)}>
                <span>I like: ${data.food}</span> 
                <span data-loading="show" data-testid="loading-element">Loading...</span>
                <button data-action="live#$render">Re-Render</button>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data: any) => {
                // to help detect when rendering is done
                data.food = 'popcorn';
            })
            // delay so we can check loading
            .delayResponse(50)
            .init();

        // wait for element to hide itself on start up
        await waitFor(() => expect(getByTestId(test.element, 'loading-element')).not.toBeVisible());

        getByText(test.element, 'Re-Render').click();
        // element should instantly be visible
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();

        // wait for loading to finish
        await waitFor(() => expect(test.element).toHaveTextContent('I like: popcorn'));
        // loading element should now be hidden
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('takes into account the "action" modifier', async () => {
        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}> 
                <span data-loading="action(save)|show" data-testid="loading-element">Loading...</span>

                <button data-action="live#action" data-action-name="save">Save</button>
                <button data-action="live#action" data-action-name="otherAction">Other Action</button>
                <button data-action="live#$render">Re-Render</button>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            // delay so we can check loading
            .delayResponse(50)
            .init();

        getByText(test.element, 'Re-Render').click();
        // it should not be loading yet
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectActionCalled('otherAction')
            // delay so we can check loading
            .delayResponse(50)
            .init();
        getByText(test.element, 'Other Action').click();
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        // it should not be loading yet
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectActionCalled('save')
            // delay so we can check loading
            .delayResponse(50)
            .init();
        getByText(test.element, 'Save').click();
        // wait for the ajax call to start (will be 0ms, but with a timeout, so not *quite* instant)
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        // it SHOULD be loading now
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // now it should be hidden again
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('takes into account the "model" modifier', async () => {
        const test = await createTest({ comments: '', user: { email: '' }}, (data: any) => `
            <div ${initComponent(data)}> 
                <textarea data-model="comments"></textarea>
                <span data-loading="model(comments)|show" data-testid="comments-loading">Comments change loading...</span>

                <textarea data-model="user.email"></textarea>
                <span data-loading="model(user.email)|show" data-testid="email-loading">Checking if email is taken...</span>
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ comments: 'Changing the comments!', user: { email: '' } })
            // delay so we can check loading
            .delayResponse(50)
            .init();

        userEvent.type(test.queryByDataModel('comments'), 'Changing the comments!')
        // it should not be loading yet due to debouncing
        expect(getByTestId(test.element, 'comments-loading')).not.toBeVisible();
        // wait for ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        // NOW it should be loading
        expect(getByTestId(test.element, 'comments-loading')).toBeVisible();
        // but email-loading is not loading
        expect(getByTestId(test.element, 'email-loading')).not.toBeVisible();
        // wait for Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // loading is no longer visible
        expect(getByTestId(test.element, 'comments-loading')).not.toBeVisible();

        // now try the user.email "child property" field
        test.expectsAjaxCall('get')
            .expectSentData({ comments: 'Changing the comments!', user: { email: 'ryan@symfonycasts.com' } })
            // delay so we can check loading
            .delayResponse(50)
            .init();

        userEvent.type(test.queryByDataModel('user.email'), 'ryan@symfonycasts.com');
        // it should not be loading yet due to debouncing
        expect(getByTestId(test.element, 'email-loading')).not.toBeVisible();
        // wait for ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        // NOW it should be loading
        expect(getByTestId(test.element, 'email-loading')).toBeVisible();
        // but comments-loading is not loading
        expect(getByTestId(test.element, 'comments-loading')).not.toBeVisible();
        // wait for Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // loading is no longer visible
        expect(getByTestId(test.element, 'email-loading')).not.toBeVisible();
    });

    it('can handle multiple actions on the same request', async () => {
        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}> 
                <span data-loading="action(otherAction)|show" data-testid="loading-element">Loading...</span>

                <button data-action="live#action" data-action-name="debounce(50)|save">Save</button>
                <button data-action="live#action" data-action-name="otherAction">Other Action</button>
            </div>
        `);

        // 1 ajax request with both actions
        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectActionCalled('save')
            .expectActionCalled('otherAction')
            // delay so we can check loading
            .delayResponse(50)
            .init();

        getByText(test.element, 'Save').click();
        getByText(test.element, 'Other Action').click();
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        // it SHOULD be loading now
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));
        // now it should be hidden again
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('does not trigger loading if request finishes first', async () => {
        const test = await createTest({}, (data: any) => `
           <div ${initComponent(data)}> 
               <span data-loading="action(save)|delay(50)|show" data-testid="loading-element">Loading...</span>

               <button data-action="live#action" data-action-name="save">Save</button>
           </div>
       `);

        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .expectActionCalled('save')
            // delay, but not as long as the loading delay
            .delayResponse(30)
            .init();

        getByText(test.element, 'Save').click();
        // it should NOT be loading: loading is delayed
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        // request took 30ms, action loading is delayed for 50
        // wait 30 more (30+30=60) and verify the element did not switch into loading
        await (new Promise(resolve => setTimeout(resolve, 30)));
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });
});
