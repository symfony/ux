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
import { afterEach, describe, expect, it, vi } from 'vitest';
import { createTest, initComponent, shutdownTests } from '../../tools';

describe('LiveController skipped render Tests', () => {
    afterEach(() => {
        shutdownTests();
        vi.restoreAllMocks();
    });

    const template = (data: any) => `
        <div ${initComponent(data)}>
            Rendered count: ${data.count}
            <input data-model="comment" value="${data.comment}">
        </div>
    `;

    it('keeps the content and takes the new props', async () => {
        const test = await createTest({ count: 0, comment: 'old' }, template);
        const renderFinished = vi.fn();
        test.component.on('render:finished', renderFinished);

        test.expectsAjaxCall()
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                data.count = 1;
                data.comment = 'from server';
            })
            .serverWillSkipRender();

        await test.component.action('save');

        expect(test.element).toHaveTextContent('Rendered count: 0');
        expect(test.component.getData('count')).toBe(1);
        expect(test.queryByDataModel('comment')).toHaveValue('from server');
        expect(test.element).not.toHaveAttribute('aria-busy');
        expect(renderFinished).toHaveBeenCalledOnce();

        const makeRequest = vi.spyOn(test.mockedBackend, 'makeRequest');
        test.expectsAjaxCall().expectActionCalled('save');
        await test.component.action('save');

        expect(makeRequest.mock.calls[0][0]).toEqual({ count: 1, comment: 'from server' });
    });

    it('lets a later render replace the props it took', async () => {
        const test = await createTest({ count: 0, comment: '' }, template);

        test.expectsAjaxCall()
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                data.count = 1;
            })
            .serverWillSkipRender();
        await test.component.action('save');
        expect(test.element).toHaveTextContent('Rendered count: 0');

        test.expectsAjaxCall().serverWillChangeProps((data: any) => {
            data.count = 2;
        });
        await test.component.render();

        expect(test.element).toHaveTextContent('Rendered count: 2');
        expect(test.component.getData('count')).toBe(2);
    });

    it('emits and dispatches the events of the response only once', async () => {
        const test = await createTest(
            { count: 0 },
            (data: any) => `
            <div ${initComponent(data, {
                listeners: [{ event: 'saved', action: 'reload' }],
                eventEmit: 1 === data.count ? [{ event: 'saved', data: {}, target: 'self' }] : undefined,
                browserDispatch: 1 === data.count ? [{ event: 'saved:browser', payload: { id: 1 } }] : undefined,
            })}>
                Rendered count: ${data.count}
            </div>
        `
        );
        const dispatched: Array<any> = [];
        test.element.addEventListener('saved:browser', (event) => {
            dispatched.push((event as CustomEvent).detail);
        });

        test.expectsAjaxCall()
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                data.count = 1;
            })
            .serverWillSkipRender();
        test.expectsAjaxCall()
            .expectActionCalled('reload')
            .serverWillChangeProps((data: any) => {
                data.count = 2;
            })
            .serverWillSkipRender();

        await test.component.action('save');
        await waitFor(() => expect(test.component.getData('count')).toBe(2));
        await new Promise((resolve) => setTimeout(resolve, 20));

        expect(dispatched).toEqual([{ id: 1 }]);
        expect(test.element).toHaveTextContent('Rendered count: 0');
    });

    it('renders afterwards when a model update shared the request', async () => {
        const test = await createTest(
            { comment: '' },
            (data: any) => `
            <div ${initComponent(data, { debounce: 10 })}>
                <input data-model="comment" value="${data.comment}">
                Rendered comment: ${data.comment}
                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );

        test.expectsAjaxCall().expectUpdatedData({ comment: 'hi' }).expectActionCalled('save').serverWillSkipRender();
        test.expectsAjaxCall();

        await userEvent.type(test.queryByDataModel('comment'), 'hi');
        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Rendered comment: hi'));
    });

    it('renders afterwards when another action was queued during the request', async () => {
        const test = await createTest(
            { comment: '' },
            (data: any) => `
            <div ${initComponent(data, { debounce: 10 })}>
                <input data-model="comment" value="${data.comment}">
                Rendered comment: ${data.comment}
                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );

        test.expectsAjaxCall()
            .expectUpdatedData({ comment: 'hi' })
            .expectActionCalled('save')
            .serverWillSkipRender()
            .delayResponse(50);
        test.expectsAjaxCall().expectActionCalled('save').serverWillSkipRender();
        test.expectsAjaxCall();

        await userEvent.type(test.queryByDataModel('comment'), 'hi');
        getByText(test.element, 'Save').click();
        await new Promise((resolve) => setTimeout(resolve, 20));
        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Rendered comment: hi'));
    });

    it('does not render afterwards for an action alone', async () => {
        const test = await createTest({ count: 0, comment: '' }, template);
        const makeRequest = vi.spyOn(test.mockedBackend, 'makeRequest');

        test.expectsAjaxCall()
            .expectActionCalled('save')
            .serverWillChangeProps((data: any) => {
                data.count = 1;
            })
            .serverWillSkipRender();

        await test.component.action('save');
        await new Promise((resolve) => setTimeout(resolve, 20));

        expect(makeRequest).toHaveBeenCalledOnce();
        expect(test.element).toHaveTextContent('Rendered count: 0');
    });

    it('still renders the model update after a failed request', async () => {
        const test = await createTest(
            { comment: '' },
            (data: any) => `
            <div ${initComponent(data, { debounce: 10 })}>
                <input data-model="comment" value="${data.comment}">
                Rendered comment: ${data.comment}
                <button data-action="live#action" data-live-action-param="save">Save</button>
            </div>
        `
        );
        let hasFailed = false;
        test.component.on('response:error', (_backendResponse: any, controls: { displayError: boolean }) => {
            controls.displayError = false;
            hasFailed = true;
        });

        test.expectsAjaxCall()
            .expectUpdatedData({ comment: 'hi' })
            .expectActionCalled('save')
            .serverWillReturnCustomResponse(500, 'Server error');

        await userEvent.type(test.queryByDataModel('comment'), 'hi');
        getByText(test.element, 'Save').click();
        await waitFor(() => expect(hasFailed).toBe(true));

        test.expectsAjaxCall().expectUpdatedData({ comment: 'hi' }).expectActionCalled('save').serverWillSkipRender();
        test.expectsAjaxCall();

        getByText(test.element, 'Save').click();

        await waitFor(() => expect(test.element).toHaveTextContent('Rendered comment: hi'));
    });
});
