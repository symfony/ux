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

    it('conserves values of fields modified after a render request', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(template(data));

        fetchMock.get('http://localhost/_components/my_component?name=Ryan', {
            html: template({ name: 'Kevin' }),
            data: { name: 'Kevin' }
        }, {
            delay: 100
        });
        getByText(element, 'Reload').click();
        userEvent.type(getByLabelText(element, 'Comments:'), '!!');

        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));
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

        fetchMock.get('http://localhost/_components/my_component?name=Ryan', {
            html: template({ name: 'Kevin' }, true),
            data: { name: 'Kevin' }
        }, {
            delay: 100
        });
        getByText(element, 'Reload').click();
        userEvent.type(getByLabelText(element, 'Comments:'), '!!');

        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));
        expect(getByLabelText(element, 'Comments:')).toHaveValue('i like pizza!!');
        expect(document.activeElement.name).toEqual('comments');
    });

    it('cancels a re-render if the page is navigating away', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(template(data));

        fetchMock.get('end:?name=Ryan', {
            html: '<div>aloha!</div>',
            data: { name: 'Kevin' }
        }, {
            delay: 100
        });

        getByText(element, 'Reload').click();
        // imitate navigating away
        fireEvent(window, createEvent('beforeunload', window));

        // wait for the fetch to finish
        await fetchMock.flush();

        // the re-render should not have happened
        expect(element).not.toHaveTextContent('aloha!');
    });
});
