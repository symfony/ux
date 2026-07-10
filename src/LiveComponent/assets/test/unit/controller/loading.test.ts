/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { getByTestId, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import { afterEach, describe, expect, it } from 'vitest';
import { createTest, initComponent, shutdownTests } from '../../tools';

describe('LiveController data-loading Tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('executes basic loading functionality on an element', async () => {
        const test = await createTest(
            { food: 'pizza' },
            (data: any) => `
            <div ${initComponent(data)}>
                <span>I like: ${data.food}</span>
                <span data-loading="show" data-testid="loading-element">Loading...</span>
            </div>
        `
        );

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                // to help detect when rendering is done
                data.food = 'popcorn';
            })
            // delay so we can check loading
            .delayResponse(50);

        // wait for element to hide itself on start up
        await waitFor(() => expect(getByTestId(test.element, 'loading-element')).not.toBeVisible());

        test.component.render();
        // element should instantly be visible
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();

        // wait for loading to finish
        await waitFor(() => expect(test.element).toHaveTextContent('I like: popcorn'));
        // loading element should now be hidden
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('executes basic loading functionality on root element', async () => {
        const test = await createTest(
            { food: 'pizza' },
            (data: any) => `
            <div ${initComponent(data)} data-loading="addClass(opacity-20)">
                <span>I like: ${data.food}</span>
                <button data-action="live#$render">Re-Render</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                // to help detect when rendering is done
                data.food = 'popcorn';
            })
            // delay so we can check loading
            .delayResponse(50);

        // wait for element to hide itself on start up
        await waitFor(() => expect(test.element).not.toHaveClass('opacity-20'));

        getByText(test.element, 'Re-Render').click();
        // element should instantly be visible
        expect(test.element).toHaveClass('opacity-20');

        // wait for loading to finish
        await waitFor(() => expect(test.element).toHaveTextContent('I like: popcorn'));
        // loading element should now be hidden
        expect(test.element).not.toHaveClass('opacity-20');
    });

    it('takes into account the "action" modifier', async () => {
        const test = await createTest(
            {},
            (data: any) => `
            <div ${initComponent(data)}>
                <span data-loading="action(save)|show" data-testid="loading-element">Loading...</span>

                <button data-action="live#action" data-live-action-param="save">Save</button>
                <button data-action="live#action" data-live-action-param="otherAction">Other Action</button>
                <button data-action="live#$render">Re-Render</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            // delay so we can check loading
            .delayResponse(50);

        getByText(test.element, 'Re-Render').click();
        // it should not be loading yet
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));

        test.expectsAjaxCall()
            .expectActionCalled('otherAction')
            // delay so we can check loading
            .delayResponse(50);
        getByText(test.element, 'Other Action').click();
        await waitFor(() => expect(test.element).toHaveAttribute('aria-busy', 'true'));
        // it should not be loading yet
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));

        test.expectsAjaxCall()
            .expectActionCalled('save')
            // delay so we can check loading
            .delayResponse(50);
        getByText(test.element, 'Save').click();
        // wait for the ajax call to start (will be 0ms, but with a timeout, so not *quite* instant)
        await waitFor(() => expect(test.element).toHaveAttribute('aria-busy', 'true'));
        // it SHOULD be loading now
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));
        // now it should be hidden again
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('takes into account the "model" modifier', async () => {
        const test = await createTest(
            { comments: '', user: { email: '' } },
            (data: any) => `
            <div ${initComponent(data)}>
                <textarea data-model="comments"></textarea>
                <span data-loading="model(comments)|show" data-testid="comments-loading">Comments change loading...</span>

                <textarea data-model="user.email"></textarea>
                <span data-loading="model(user.email)|show" data-testid="email-loading">Checking if email is taken...</span>
            </div>
        `
        );

        test.expectsAjaxCall()
            .expectUpdatedData({ comments: 'Changing the comments!' })
            // delay so we can check loading
            .delayResponse(50);

        userEvent.type(test.queryByDataModel('comments'), 'Changing the comments!');
        // it should not be loading yet due to debouncing
        expect(getByTestId(test.element, 'comments-loading')).not.toBeVisible();
        // wait for ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('aria-busy', 'true'));
        // NOW it should be loading
        expect(getByTestId(test.element, 'comments-loading')).toBeVisible();
        // but email-loading is not loading
        expect(getByTestId(test.element, 'email-loading')).not.toBeVisible();
        // wait for Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));
        // loading is no longer visible
        expect(getByTestId(test.element, 'comments-loading')).not.toBeVisible();

        // now try the user.email "child property" field
        test.expectsAjaxCall()
            .expectUpdatedData({ 'user.email': 'ryan@symfonycasts.com' })
            // delay so we can check loading
            .delayResponse(50);

        await userEvent.type(test.queryByDataModel('user.email'), 'ryan@symfonycasts.com');
        // it should not be loading yet due to debouncing
        expect(getByTestId(test.element, 'email-loading')).not.toBeVisible();
        // wait for ajax call to start
        await waitFor(() => expect(test.element).toHaveAttribute('aria-busy', 'true'));
        // NOW it should be loading
        expect(getByTestId(test.element, 'email-loading')).toBeVisible();
        // but comments-loading is not loading
        expect(getByTestId(test.element, 'comments-loading')).not.toBeVisible();
        // wait for Ajax call to finish
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));
        // loading is no longer visible
        expect(getByTestId(test.element, 'email-loading')).not.toBeVisible();
    });

    it('can handle multiple actions on the same request', async () => {
        const test = await createTest(
            {},
            (data: any) => `
            <div ${initComponent(data)}>
                <span data-loading="action(otherAction)|show" data-testid="loading-element">Loading...</span>

                <button data-action="live#action" data-live-action-param="debounce(50)|save">Save</button>
                <button data-action="live#action" data-live-action-param="otherAction">Other Action</button>
            </div>
        `
        );

        // 1 ajax request with both actions
        test.expectsAjaxCall()
            .expectActionCalled('save')
            .expectActionCalled('otherAction')
            // delay so we can check loading
            .delayResponse(50);

        getByText(test.element, 'Save').click();
        getByText(test.element, 'Other Action').click();
        await waitFor(() => expect(test.element).toHaveAttribute('aria-busy', 'true'));
        // it SHOULD be loading now
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));
        // now it should be hidden again
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('does not trigger loading if request finishes first', async () => {
        const test = await createTest(
            {},
            (data: any) => `
           <div ${initComponent(data)}>
               <span data-loading="action(save)|delay(50)|show" data-testid="loading-element">Loading...</span>

               <button data-action="live#action" data-live-action-param="save">Save</button>
           </div>
       `
        );

        test.expectsAjaxCall()
            .expectActionCalled('save')
            // delay, but not as long as the loading delay
            .delayResponse(30);

        getByText(test.element, 'Save').click();
        // it should NOT be loading: loading is delayed
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
        await waitFor(() => expect(test.element).not.toHaveAttribute('aria-busy'));

        // request took 30ms, action loading is delayed for 50
        // wait 30 more (30+30=60) and verify the element did not switch into loading
        await new Promise((resolve) => setTimeout(resolve, 30));
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });

    it('does not trigger loading inside component children', async () => {
        const childTemplate = (data: any) => `
            <div
                ${initComponent(data)}
                id="child-id"
                data-testid="child"
                ${data.renderChild ? '' : 'data-live-preserve'}
            >
                <span data-loading="show" data-testid="child-loading-element-showing">Loading...</span>
                <span data-loading="hide" data-testid="child-loading-element-hiding">Loading...</span>
            </div>
        `;

        const test = await createTest(
            { renderChild: true },
            (data: any) => `
            <div ${initComponent(data, { id: 'parent-id' })} data-testid="parent">
                <span data-loading="show" data-testid="parent-loading-element-showing">Loading...</span>
                <span data-loading="hide" data-testid="parent-loading-element-hiding">Loading...</span>
                ${childTemplate({ renderChild: data.renderChild })}
                <button data-action="live#$render">Render</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            // delay so we can check loading
            .serverWillChangeProps((data: any) => {
                data.renderChild = false;
            })
            .delayResponse(20);

        // All showing elements should be hidden / hiding elements should be visible
        await waitFor(() => expect(getByTestId(test.element, 'parent-loading-element-showing')).not.toBeVisible());
        await waitFor(() => expect(getByTestId(test.element, 'parent-loading-element-hiding')).toBeVisible());
        await waitFor(() => expect(getByTestId(test.element, 'child-loading-element-showing')).not.toBeVisible());
        await waitFor(() => expect(getByTestId(test.element, 'child-loading-element-hiding')).toBeVisible());

        getByText(test.element, 'Render').click();

        // Parent: showing elements should be visible / hiding elements should be hidden
        expect(getByTestId(test.element, 'parent-loading-element-showing')).toBeVisible();
        expect(getByTestId(test.element, 'parent-loading-element-hiding')).not.toBeVisible();
        // Child: no change
        expect(getByTestId(test.element, 'child-loading-element-showing')).not.toBeVisible();
        expect(getByTestId(test.element, 'child-loading-element-hiding')).toBeVisible();

        // wait for loading to finish
        await new Promise((resolve) => setTimeout(resolve, 30));

        // Parent: back to original state
        expect(getByTestId(test.element, 'parent-loading-element-showing')).not.toBeVisible();
        expect(getByTestId(test.element, 'parent-loading-element-hiding')).toBeVisible();
        // Child: no change
        expect(getByTestId(test.element, 'child-loading-element-showing')).not.toBeVisible();
        expect(getByTestId(test.element, 'child-loading-element-hiding')).toBeVisible();
    });

    it('ignores "data-loading" inside a "data-live-ignore" subtree', async () => {
        const test = await createTest(
            { food: 'pizza' },
            (data: any) => `
            <div ${initComponent(data)}>
                <span>I like: ${data.food}</span>
                <span data-loading="show" data-testid="loading-element">Loading...</span>

                <div data-live-ignore data-testid="ignored">
                    <!-- third-party widget managing its own, non-directive "data-loading" -->
                    <span data-loading="false" data-testid="ignored-loading-element">widget internals</span>
                </div>
            </div>
        `
        );

        test.expectsAjaxCall()
            .serverWillChangeProps((data: any) => {
                data.food = 'popcorn';
            })
            // delay so we can check loading
            .delayResponse(50);

        // the component initializes without throwing on the "false" value
        // held by the "data-loading" attribute inside the "data-live-ignore" subtree
        await waitFor(() => expect(getByTestId(test.element, 'loading-element')).not.toBeVisible());

        test.component.render();
        // the real loading element still behaves as usual
        expect(getByTestId(test.element, 'loading-element')).toBeVisible();
        // the ignored element is never picked up by the loading scanner
        expect(getByTestId(test.element, 'ignored-loading-element')).not.toHaveAttribute('data-live-is-loading');

        // wait for loading to finish
        await waitFor(() => expect(test.element).toHaveTextContent('I like: popcorn'));
        expect(getByTestId(test.element, 'loading-element')).not.toBeVisible();
    });
});
