/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { createTest, initComponent, shutdownTests } from '../tools';
import { getByText, waitFor } from '@testing-library/dom';
import BackendResponse from '../../src/BackendResponse';

const getErrorElement = (): Element|null => {
    return document.getElementById('live-component-error');
};

describe('LiveController Error Handling', () => {
    afterEach(() => {
        shutdownTests();
    })

    it('displays an error modal on 500 errors', async () => {
        const test = await createTest({ }, (data: any) => `
            <div ${initComponent(data)}>
                Original component text
                <button data-action="live#action" data-action-name="save">Save</button>
            </div>
        `);

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .serverWillReturnCustomResponse(500, `
                <html><head><title>Error!</title></head><body><h1>An error occurred</h1></body></html>
            `)
            .expectActionCalled('save')
            .init();

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(document.getElementById('live-component-error')).not.toBeNull());
        // the component did not change or re-render
        expect(test.element).toHaveTextContent('Original component text');
        const errorContainer = getErrorElement();
        if (!errorContainer) {
            throw new Error('containing missing');
        }
        expect(errorContainer.querySelector('iframe')).not.toBeNull();
    });

    it('displays a modal on any non-component response', async () => {
        const test = await createTest({ }, (data: any) => `
            <div ${initComponent(data)}>
                Original component text
                <button data-action="live#action" data-action-name="save">Save</button>
            </div>
        `);

        // ONLY a post is sent, not a re-render GET
        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .serverWillReturnCustomResponse(200, `
                <html><head><title>Hi!</title></head><body><h1>I'm a whole page, not a component!</h1></body></html>
            `)
            .expectActionCalled('save')
            .init();

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(document.getElementById('live-component-error')).not.toBeNull());
        // the component did not change or re-render
        expect(test.element).toHaveTextContent('Original component text');
    });

    it('triggers response:error hook', async () => {
        const test = await createTest({ }, (data: any) => `
            <div ${initComponent(data)}>
                component text
            </div>
        `);

        test.expectsAjaxCall('post')
            .expectSentData(test.initialData)
            .serverWillReturnCustomResponse(200, `
                <html><head><title>Hi!</title></head><body><h1>I'm a whole page, not a component!</h1></body></html>
            `)
            .expectActionCalled('save')
            .init();

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
});
