/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { clearDOM } from '@symfony/stimulus-testing';
import { initLiveComponent, mockRerender, startStimulus } from '../tools';
import { createEvent, fireEvent, getByLabelText, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController rendering Tests', () => {
    const template = (data, includeLoading = false) => `
        <div
            ${initLiveComponent('/_components/my_component', data)}
        >
            <!-- form field not mapped with data-model -->
            <label>
                Comments:
                <input
                    name="comments"
                    value="i like pizza"
                    ${includeLoading ? 'data-loading="addAttribute(foobar) addClass(newClass)"' : ''}
                >
            </label>

            <div${data.hasError ? ' class="has-error"' : ''}>Name: ${data.name}</div>

            <button
                data-action="live#$render"
            >Reload</button>
        </div>
    `;

    afterEach(() => {
        clearDOM();
        if (!fetchMock.done()) {
            throw new Error('Mocked requests did not match');
        }
        fetchMock.reset();
    });

    it('renders from the AJAX endpoint & updates data', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        mockRerender({ name: 'Ryan' }, template, (data) => {
            // change the data on the server
            data.name = 'Kevin';
        });
        getByText(element, 'Reload').click();

        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));
        expect(controller.dataValue).toEqual({name: 'Kevin'});
    });

    it('renders over local value in input', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(template(data));

        mockRerender({name: 'Ryan'}, template, (data: any) => {
            data.name = 'Kevin';
        }, { delay: 100 });
        // type into the input that is not bound to a model
        userEvent.type(getByLabelText(element, 'Comments:'), '!!');
        getByText(element, 'Reload').click();

        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));
        // value if unmapped input is reset
        expect(getByLabelText(element, 'Comments:')).toHaveValue('i like pizza');
        expect(document.activeElement.name).toEqual('comments');
    });

    it('conserves values of fields modified after a render request IF data-live-ignore', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(template(data));

        // name=Ryan is sent to the server
        mockRerender({name: 'Ryan'}, template, (data: any) => {
            data.name = 'Kevin';
        }, { delay: 100 });

        // type into the input that is not bound to a model
        const input = getByLabelText(element, 'Comments:');
        input.setAttribute('data-live-ignore', '');
        userEvent.type(input, '!!');
        getByText(element, 'Reload').click();

        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));
        // value of unmapped input is NOT reset because of data-live-ignore
        expect(getByLabelText(element, 'Comments:')).toHaveValue('i like pizza!!');
        expect(document.activeElement.name).toEqual('comments');
    });

    it('conserves values of fields modified after render but with loading behavior', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(
            // "true" gives the comment input a loading behavior
            // this could make the input.isEqualNode() be false when comparing
            // that's exactly what we want test for
            template(data, true)
        );

        mockRerender(
            { name: 'Ryan' },
            // re-render but passing true as the second arg
            (data: any) => template(data, true),
            (data: any) => { data.name = 'Kevin'; },
            { delay: 100 }
        );

        const input = getByLabelText(element, 'Comments:');
        input.setAttribute('data-live-ignore', '');
        userEvent.type(input, '!!');
        getByText(element, 'Reload').click();

        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));
        expect(getByLabelText(element, 'Comments:')).toHaveValue('i like pizza!!');
        expect(document.activeElement.name).toEqual('comments');
    });

    it('cancels a re-render if the page is navigating away', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(template(data));

        mockRerender(
            { name: 'Ryan' },
            () => '<div>aloha!</div>',
            () => { },
            { delay: 100 }
        );

        getByText(element, 'Reload').click();
        // imitate navigating away
        fireEvent(window, createEvent('beforeunload', window));

        // wait for the fetch to finish
        await fetchMock.flush();

        // the re-render should not have happened
        expect(element).not.toHaveTextContent('aloha!');
    });
});
