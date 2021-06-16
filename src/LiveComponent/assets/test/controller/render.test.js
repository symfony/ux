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
import { startStimulus } from '../tools';
import { createEvent, fireEvent, getByLabelText, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController rendering Tests', () => {
    const template = (data, includeLoading = false) => `
        <div
            data-controller="live"
            data-live-url-value="http://localhost/components/my_component"
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
        const { element, controller } = await startStimulus(
            template(data),
            data
        );

        fetchMock.get('http://localhost/components/my_component?name=Ryan', {
            html: '<div>aloha!</div>',
            data: { name: 'Kevin' }
        });
        getByText(element, 'Reload').click();

        await waitFor(() => expect(element).toHaveTextContent('aloha!'));
        expect(controller.dataValue).toEqual({name: 'Kevin'});
    });

    it('conserves values of fields modified after a render request', async () => {
        const data = { name: 'Ryan' };
        const { element } = await startStimulus(
            template(data),
            data
        );

        fetchMock.get('http://localhost/components/my_component?name=Ryan', {
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
            template(data, true),
            data
        );

        fetchMock.get('http://localhost/components/my_component?name=Ryan', {
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

    it('avoids updating a child component', async () => {
        const parentTemplate = (data, childData) => {
            return `
                <div
                    data-controller="live"
                    data-live-url-value="http://localhost/components/parent"
                >
                    Title: ${data.title}

                    <button
                        data-action="live#$render"
                    >Parent Re-render</button>

                    ${template(childData)}
                </div>
            `
        }

        const data = { title: 'Parent component' };
        const childData = { name: 'Ryan' };
        const { element } = await startStimulus(
            // render the parent and child component
            parentTemplate(data, childData),
            data
        );
        // setup the values on the child element
        element.querySelector('[data-controller="live"]').dataset.liveDataValue = JSON.stringify(childData);

        // child re-render: render with new name & an error class
        fetchMock.get('http://localhost/components/my_component?name=Ryan', {
            html: template({ name: 'Kevin', hasError: true }),
            data: { name: 'Kevin', hasError: true }
        });

        // reload the child template
        getByText(element, 'Reload').click();
        await waitFor(() => expect(element).toHaveTextContent('Name: Kevin'));

        // reload the parent template
        fetchMock.get('http://localhost/components/parent?title=Parent+component', {
            html: parentTemplate({ title: 'Changed parent' }, { name: 'changed name'}),
            data: { title: 'Changed parent'}
        });
        getByText(element, 'Parent Re-render').click();
        await waitFor(() => expect(element).toHaveTextContent('Title: Changed parent'));

        // the child component should *not* have updated
        expect(element).toHaveTextContent('Name: Kevin');
    });

    it('cancels a re-render if the page is navigating away', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(
            template(data),
            data
        );

        fetchMock.get('http://localhost/components/my_component?name=Ryan', {
            html: '<div>aloha!</div>',
            data: { name: 'Kevin' }
        }, {
            delay: 100
        });

        getByText(element, 'Reload').click();
        // imitate navigating away
        fireEvent(window, createEvent('beforeunload', window));

        // wait for the fetch to fonish
        await fetchMock.flush();

        // the re-render should not have happened
        expect(element).not.toHaveTextContent('aloha!');
    });
});
