/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { waitFor } from '@testing-library/dom';
import { afterEach, describe, expect, it } from 'vitest';
import { createTest, initComponent, shutdownTests } from '../../tools';

describe('LiveController LiveListener condition Tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('only calls the action when the condition passes', async () => {
        const test = await createTest(
            { renderCount: 0, product: 42 },
            (data: any) => `
            <div ${initComponent(data, {
                name: 'simple-component',
                listeners: [{ event: 'product_updated', action: 'fooAction', condition: 'event.id == props.product' }],
            })}>
                Render Count: ${data.renderCount}
            </div>
        `
        );

        // condition does not match: no Ajax call should be made
        test.component.emitSelf('product_updated', { id: 99 });
        // wait a tiny bit - enough for a request to be sent if it was going to be
        await new Promise((resolve) => setTimeout(resolve, 10));

        test.expectsAjaxCall()
            .expectActionCalled('fooAction', { id: 42 })
            .serverWillChangeProps((data) => {
                data.renderCount = 1;
            });

        // condition matches: the action is called
        test.component.emitSelf('product_updated', { id: 42 });
        await waitFor(() => expect(test.element).toHaveTextContent('Render Count: 1'));
    });

    it('evaluates a condition using props changed locally', async () => {
        const test = await createTest(
            { renderCount: 0, product: 42 },
            (data: any) => `
            <div ${initComponent(data, {
                name: 'simple-component',
                listeners: [{ event: 'product_updated', action: 'fooAction', condition: 'event.id == props.product' }],
            })}>
                Render Count: ${data.renderCount}
            </div>
        `
        );

        // change the "product" prop locally, without triggering a re-render
        test.component.set('product', 99);

        // the event now matches the *new*, local "product" prop value
        test.expectsAjaxCall()
            .expectActionCalled('fooAction', { id: 99 })
            .expectUpdatedData({ product: 99 })
            .serverWillChangeProps((data) => {
                data.renderCount = 1;
            });

        test.component.emitSelf('product_updated', { id: 99 });
        await waitFor(() => expect(test.element).toHaveTextContent('Render Count: 1'));
    });

    it('does not call the action when the condition is malformed', async () => {
        const test = await createTest(
            { renderCount: 0, product: 42 },
            (data: any) => `
            <div ${initComponent(data, {
                name: 'simple-component',
                listeners: [{ event: 'product_updated', action: 'fooAction', condition: 'event.id ===' }],
            })}>
                Render Count: ${data.renderCount}
            </div>
        `
        );

        test.component.emitSelf('product_updated', { id: 42 });
        // wait a tiny bit - enough for a request to be sent if it was going to be
        await new Promise((resolve) => setTimeout(resolve, 10));
    });
});
