/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { getByTestId, getByText, waitFor } from '@testing-library/dom';
import { afterEach, describe, expect, it } from 'vitest';
import type BackendResponse from '../../../src/Backend/BackendResponse';
import { createTest, initComponent, shutdownTests } from '../../tools';

const getErrorElement = (): Element | null => {
    return document.getElementById('live-component-error');
};

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
        test.component.on('response:error', (_backendResponse: BackendResponse, controls) => {
            isHookCalled = true;
            controls.displayError = false;
        });

        await test.component.action('save');

        await waitFor(() => expect(isHookCalled).toBe(true));
        const errorContainer = getErrorElement();
        expect(errorContainer).toBeNull();
    });

    it('recovers when the request fails before a response is received', async () => {
        const test = await createTest(
            { counter: 4 },
            (data: any) => `
            <div ${initComponent(data)} data-loading="addClass(is-loading)">
                Current count: ${data.counter}
            </div>
        `
        );

        test.expectsAjaxCall().requestWillFail().expectActionCalled('save').delayResponse(10);

        const promise = test.component.action('save');
        await waitFor(() => expect(test.element).toHaveClass('is-loading'));
        expect(test.element).toHaveAttribute('aria-busy', 'true');

        await expect(promise).rejects.toThrow('Failed to fetch');
        expect(test.element).not.toHaveClass('is-loading');
        expect(test.element).not.toHaveAttribute('aria-busy');
        expect(getErrorElement()).toBeNull();

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.counter = 10;
        });

        await test.component.render();
        expect(test.element).toHaveTextContent('Current count: 10');
    });

    it('sends the unsaved model changes again after a failed request', async () => {
        const test = await createTest(
            { name: 'Ryan' },
            (data: any) => `
            <div ${initComponent(data)}>
                Name: ${data.name}
            </div>
        `
        );

        test.expectsAjaxCall().expectUpdatedData({ name: 'Kevin' }).requestWillFail();

        await expect(test.component.set('name', 'Kevin', true)).rejects.toThrow('Failed to fetch');

        test.expectsAjaxCall().expectUpdatedData({ name: 'Kevin' });

        await test.component.render();
        expect(test.element).toHaveTextContent('Name: Kevin');
    });

    it('sends a request queued while the previous one fails without a response', async () => {
        const test = await createTest(
            { counter: 4 },
            (data: any) => `
            <div ${initComponent(data)}>
                Current count: ${data.counter}
            </div>
        `
        );

        test.expectsAjaxCall().requestWillFail().expectActionCalled('save').delayResponse(50);

        const failedPromise = test.component.action('save');
        await waitFor(() => expect(test.element).toHaveAttribute('aria-busy', 'true'));

        test.expectsAjaxCall()
            .expectActionCalled('increment')
            .serverWillChangeProps((data: any) => {
                data.counter = 5;
            });

        const queuedPromise = test.component.action('increment');

        await expect(failedPromise).rejects.toThrow('Failed to fetch');
        await queuedPromise;
        expect(test.element).toHaveTextContent('Current count: 5');
    });

    it('does not apply a delayed loading directive after a failed request', async () => {
        const test = await createTest(
            {},
            (data: any) => `
            <div ${initComponent(data)}>
                <span data-loading="delay(20)|addClass(is-loading)" data-testid="loading-element">Loading...</span>
            </div>
        `
        );

        test.expectsAjaxCall().requestWillFail();

        await expect(test.component.render()).rejects.toThrow('Failed to fetch');
        await new Promise((resolve) => setTimeout(resolve, 50));

        expect(getByTestId(test.element, 'loading-element')).not.toHaveClass('is-loading');
    });
});
