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
import {getByTestId, waitFor} from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

describe('Component parent -> child data-model binding tests', () => {
    afterEach(() => {
        shutdownTests();
    })

    // updating stops when child is removed, restarts after
    // more complex foo:bar model binding works
    // multiple model bindings work

    it('updates parent model in simple setup', async () => {
        const test = await createTest({ foodName: ''}, (data: any) => `
            <div ${initComponent(data)}>
                Food Name ${data.foodName}
                <div
                    ${initComponent({ value: '' }, {id: 'the-child-id'})}
                    data-model="foodName:value"
                    data-testid="child"
                >
                    <input data-model="norender|value">
                </div>
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ foodName: 'ice cream' })
            // mimic that the data on the child props have not changed, so we
            // render a simple placeholder
            .willReturn((data: any) => `
                <div ${initComponent(data)}>
                    Food Name ${data.foodName}
                    <div data-live-id="the-child-id">
                </div>
            `);

        // type into the child component
        await userEvent.type(test.queryByDataModel('value'), 'ice cream');

        // wait for parent to start/stop loading
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).toHaveTextContent('Food Name ice cream'));
    });

    it('will default to "value" for the model name', async () => {
        const test = await createTest({ foodName: ''}, (data: any) => `
            <div ${initComponent(data)}>
                Food Name ${data.foodName}
                <div
                    ${initComponent({ value: '' }, {id: 'the-child-id'})}
                    data-model="foodName"
                    data-testid="child"
                >
                    <input data-model="norender|value">
                </div>
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ foodName: 'ice cream' })
            // mimic that the data on the child props have not changed, so we
            // render a simple placeholder
            .willReturn((data: any) => `
                <div ${initComponent(data)}>
                    Food Name ${data.foodName}
                    <div data-live-id="the-child-id">
                </div>
            `);

        // type into the child component
        await userEvent.type(test.queryByDataModel('value'), 'ice cream');

        // wait for parent to start/stop loading
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).toHaveTextContent('Food Name ice cream'));
    });

    it('considers modifiers when updating parent model', async () => {
        const test = await createTest({ foodName: ''}, (data: any) => `
            <div ${initComponent(data)}>
                Food Name ${data.foodName}
                <div
                    ${initComponent({ value: '' }, {id: 'the-child-id'})}
                    data-model="norender|foodName"
                    data-testid="child"
                >
                    <input data-model="norender|value">
                </div>
            </div>
        `);

        // type into the child component
        await userEvent.type(test.queryByDataModel('value'), 'ice cream');

        // wait for parent model to be set
        await waitFor(() => expect(test.component.getData('foodName')).toEqual('ice cream'));
        // but it never triggers an Ajax call, because the norender modifier
        expect(test.element).not.toHaveAttribute('busy');
        // wait for a potential Ajax call to start
        await (new Promise(resolve => setTimeout(resolve, 50)));
        expect(test.element).not.toHaveAttribute('busy');
    });

    it('start and stops model binding as child is added/removed', async () => {
        const test = await createTest({ foodName: ''}, (data: any) => `
            <div ${initComponent(data)}>
                Food Name ${data.foodName}
                <div
                    ${initComponent({ value: '' }, {id: 'the-child-id'})}
                    data-model="foodName:value"
                    data-testid="child"
                >
                    <input data-model="norender|value">
                </div>
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ foodName: 'ice cream' })
            // mimic that the data on the child props have not changed, so we
            // render a simple placeholder
            .willReturn((data: any) => `
                <div ${initComponent(data)}>
                    Food Name ${data.foodName}
                    <div data-live-id="the-child-id">
                </div>
            `);

        // type into the child component
        const inputElement = test.queryByDataModel('value');
        await userEvent.type(inputElement, 'ice cream');

        // wait for parent to start/stop loading
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).toHaveTextContent('Food Name ice cream'));

        // remove child component
        const otherContainer = document.createElement('div');
        otherContainer.appendChild(getByTestId(test.element, 'child'));

        // type into the child component
        await userEvent.type(inputElement, ' sandwich');
        // wait for a potential Ajax call to start
        await (new Promise(resolve => setTimeout(resolve, 50)));
        expect(test.element).not.toHaveAttribute('busy');
    });
});
