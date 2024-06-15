/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { createTest, initComponent, shutdownTests } from '../tools';
import { getAllByTestId, getByTestId, getByText, waitFor } from '@testing-library/dom';
import BackendResponse from '../../src/Backend/BackendResponse';
import userEvent from '@testing-library/user-event';

const getErrorElement = (): Element | null => {
    return document.getElementById('live-component-error');
};

const removeErrorModal = async () => {
    let errorElement: Element | null = null;

    await waitFor(() => {
        errorElement = getErrorElement();

        return expect(errorElement).not.toBeNull();
    });

    if (errorElement) {
        userEvent.click(errorElement);
    }
}

describe('LiveController Error Handling', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('displays an error modal on 500 errors', async () => {
        const test = await createTest(
            { counter: 4 },
            (data: any) => `
            <div ${initComponent(data)}>
                Current count: ${data.counter}
                <button data-action="live#action" data-live-action-param="save">Save</button>
                <button data-action="live#$render">Render</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            .serverWillReturnCustomResponse(
                500,
                `
                <html><head><title>Error!</title></head><body><h1>An error occurred</h1></body></html>
            `
            )
            .expectActionCalled('save');

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(document.getElementById('live-component-error')).not.toBeNull());
        // the component did not change or re-render
        expect(test.element).toHaveTextContent('Current count: 4');
        const errorContainer = getErrorElement();
        if (!errorContainer) {
            throw new Error('containing missing');
        }
        expect(errorContainer.querySelector('iframe')).not.toBeNull();

        // make sure future requests can still be sent
        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.counter = 10;
        });

        getByText(test.element, 'Render').click();
        await waitFor(() => expect(test.element).toHaveTextContent('Current count: 10'));
    });

    it('displays a modal on any non-component response', async () => {
        const test = await createTest(
            {},
            (data: any) => `
            <div ${initComponent(data)}>
                Original component text
                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            .serverWillReturnCustomResponse(
                200,
                `
                <html><head><title>Hi!</title></head><body><h1>I'm a whole page, not a component!</h1></body></html>
            `
            )
            .expectActionCalled('save');

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(document.getElementById('live-component-error')).not.toBeNull());
        // the component did not change or re-render
        expect(test.element).toHaveTextContent('Original component text');
    });

    it('triggers response:error hook', async () => {
        const test = await createTest(
            {},
            (data: any) => `
            <div ${initComponent(data)}>
                component text
            </div>
        `
        );

        test.expectsAjaxCall()
            .serverWillReturnCustomResponse(
                200,
                `
                <html><head><title>Hi!</title></head><body><h1>I'm a whole page, not a component!</h1></body></html>
            `
            )
            .expectActionCalled('save');

        let isHookCalled = false;
        test.component.on('response:error', (backendResponse: BackendResponse, controls) => {
            isHookCalled = true;
            controls.displayError = false;
        });

        await test.component.action('save');

        await waitFor(() => expect(isHookCalled).toBe(true));
        const errorContainer = getErrorElement();
        expect(errorContainer).toBeNull();
    });

    it('handles data-loading elements even if the component is in an error state', async () => {
        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}>
                <button data-action="live#action" data-live-action-param="save" data-loading="addAttribute(disabled)">Save</button>
            </div>
        `);

        getByText(test.element, 'Save').click();


        test.expectsAjaxCall()
            .serverWillReturnCustomResponse(500, `
                <html><head><title>Error!</title></head><body><h1>An error occurred</h1></body></html>
            `)
            .expectActionCalled('save')
            // delay so we can check loading
            .delayResponse(50);

        // The data-loading attribute should be set
        await waitFor(() => expect(getByText(test.element, 'Save')).toHaveAttribute('disabled'));

        // The data-loading attribute should be cleared once the request is done, even if it failed
        await waitFor(() => expect(getByText(test.element, 'Save')).not.toHaveAttribute('disabled'));
    });

    it('handles basic data-live-error elements correctly and the component still works', async () => {
        const test = await createTest({ counter: 0 }, (data: { counter: number }) => `
            <div ${initComponent(data)}>
                <p data-testid="visible-element">Current count: ${data.counter}</p>
                <p data-live-error data-testid="error-element">This element should only be visible on error</p>
                <p data-live-error="show" data-testid="error-element">This element should also only be visible on error</p>
                <button data-action="live#action" data-live-action-param="save">Save</button>
                <button data-action="live#$render">Render</button>
            </div>
        `);

        // non-error elements should be visible by default
        await waitFor(() => expect(getByTestId(test.element, 'visible-element')).toBeVisible());
        // data-live-error elements should be hidden by default
        getAllByTestId(test.element, 'error-element').forEach((element) => {
            expect(element).not.toBeVisible();
        });

        test.expectsAjaxCall()
            .serverWillReturnCustomResponse(500, `
                <html><head><title>Error!</title></head><body><h1>An error occurred</h1></body></html>
            `)
            .expectActionCalled('save');

        getByText(test.element, 'Save').click();

        // wait for the error modal and remove it
        await removeErrorModal();

        // non-error elements should still be visible
        await waitFor(() => expect(getByTestId(test.element, 'visible-element')).toBeVisible());
        // data-live-error elements should now be visible
        getAllByTestId(test.element, 'error-element').forEach((element) => {
            expect(element).toBeVisible();
        });

        // make sure future requests can still be sent
        test.expectsAjaxCall()
            .serverWillChangeProps((data: { counter: number }) => {
                data.counter = 10;
            });

        getByText(test.element, 'Render').click();

        // error elements should be instantly hidden
        getAllByTestId(test.element, 'error-element').forEach((element) => {
            expect(element).not.toBeVisible();
        });
        // counter should have been updated
        await waitFor(() => expect(getByTestId(test.element, 'visible-element')).toHaveTextContent('Current count: 10'));
    });

    it('handles all actions', async () => {
        const test = await createTest({}, (data: any) => `
            <div ${initComponent(data)}>
                <p data-live-error="hide" data-testid="hide">This component is fine</p>
                <p data-live-error="addClass(error)" data-testid="add-class">This text will gain the error class on error</p>
                <p class="success" data-live-error="removeClass(success)" data-testid="remove-class">This text will lose the success class on error</p>
                <button data-live-error="addAttribute(disabled)" type="button" data-testid="add-attribute">Important action that requires a success state</button>
                <button disabled data-live-error="removeAttribute(disabled)" type="button" data-testid="remove-attribute">Send a "this is not working !" email</button>
                <button data-action="live#action" data-live-action-param="save">Save</button>
                <button data-action="live#$render">Render</button>
            </div>
        `);

        // Elements should be in their initial state
        expect(getByTestId(test.element, 'hide')).toBeVisible();
        expect(getByTestId(test.element, 'add-class')).not.toHaveClass('error');
        expect(getByTestId(test.element, 'remove-class')).toHaveClass('success');
        expect(getByTestId(test.element, 'add-attribute')).not.toHaveAttribute('disabled');
        expect(getByTestId(test.element, 'remove-attribute')).toHaveAttribute('disabled');

        test.expectsAjaxCall()
            .serverWillReturnCustomResponse(500, `
                <html><head><title>Error!</title></head><body><h1>An error occurred</h1></body></html>
            `)
            .expectActionCalled('save');

        getByText(test.element, 'Save').click();

        // wait for the error modal and remove it
        await removeErrorModal();

        // Elements should have been updated
        expect(getByTestId(test.element, 'hide')).not.toBeVisible();
        expect(getByTestId(test.element, 'add-class')).toHaveClass('error');
        expect(getByTestId(test.element, 'remove-class')).not.toHaveClass('success');
        expect(getByTestId(test.element, 'add-attribute')).toHaveAttribute('disabled');
        expect(getByTestId(test.element, 'remove-attribute')).not.toHaveAttribute('disabled');

        // make sure future requests can still be sent
        test.expectsAjaxCall();

        getByText(test.element, 'Render').click();

        // Elements should have been updated
        expect(getByTestId(test.element, 'hide')).toBeVisible();
        expect(getByTestId(test.element, 'add-class')).not.toHaveClass('error');
        expect(getByTestId(test.element, 'remove-class')).toHaveClass('success');
        expect(getByTestId(test.element, 'add-attribute')).not.toHaveAttribute('disabled');
        expect(getByTestId(test.element, 'remove-attribute')).toHaveAttribute('disabled');
    });
});
