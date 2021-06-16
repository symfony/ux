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
import { getByLabelText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController data-model Tests', () => {
    const template = (data) => `
        <div
            data-controller="live"
            data-live-url-value="http://localhost"
        >
            <label>
            Name:
            <input
                data-model="name"
                data-action="live#update"
                value="${data.name}"
            >
            </label>
        </div>
    `;

    afterEach(() => {
        clearDOM();
        fetchMock.reset();
    });

    it('renders correctly with data-model and live#update', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(
            template(data),
            data
        );

        fetchMock.getOnce('http://localhost/?name=Ryan+WEAVER', {
            html: template({ name: 'Ryan Weaver' }),
            data: { name: 'Ryan Weaver' }
        });

        await userEvent.type(getByLabelText(element, 'Name:'), ' WEAVER', {
            // this tests the debounce: characters have a 10ms delay
            // in between, but the debouncing prevents multiple calls
            delay: 10
        });

        await waitFor(() => expect(getByLabelText(element, 'Name:')).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({name: 'Ryan Weaver'});

        // assert all calls were done the correct number of times
        fetchMock.done();

        // assert the input is still focused after rendering
        expect(document.activeElement.dataset.model).toEqual('name');
    });

    it('correctly only uses the most recent render call results', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(
            template(data),
            data
        );

        let renderCount = 0;
        element.addEventListener('live:render', () => {
            renderCount++;
        })

        const requests = [
            ['g', 650],
            ['gu', 250],
            ['guy', 150]
        ];
        requests.forEach(([string, delay]) => {
            fetchMock.getOnce(`http://localhost/?name=Ryan${string}`, {
                // the _ at the end helps us look that the input has changed
                // as a result of a re-render (not just from typing in the input)
                html: template({ name: `Ryan${string}_` }),
                data: { name: `Ryan${string}_` }
            }, { delay });
        });

        await userEvent.type(getByLabelText(element, 'Name:'), 'guy', {
            // This will result in this sequence:
            //   A) "g" starts     200ms
            //   B) "gu" starts    400ms
            //   C) "guy" starts   600ms
            //   D) "gu" finishes  650ms (is ignored)
            //   E) "guy" finishes 750ms (is used)
            //   F) "g" finishes   850ms (is ignored)
            delay: 200
        });

        await waitFor(() => expect(getByLabelText(element, 'Name:')).toHaveValue('Ryanguy_'));
        expect(controller.dataValue).toEqual({name: 'Ryanguy_'});

        // assert all calls were done the correct number of times
        fetchMock.done();

        // only 1 render should have ultimately occurred
        expect(renderCount).toEqual(1);
    });

    it('falls back to using the name attribute when no data-model is present', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(
            template(data),
            data
        );

        // replace data-model with name attribute
        const inputElement = getByLabelText(element, 'Name:');
        delete inputElement.dataset.model;
        inputElement.setAttribute('name', 'name');

        fetchMock.getOnce('http://localhost/?name=Ryan+WEAVER', {
            html: template({ name: 'Ryan Weaver' }),
            data: { name: 'Ryan Weaver' }
        });

        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({name: 'Ryan Weaver'});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('standardizes user[firstName] style models into post.name', async () => {
        const deeperModelTemplate = (data) => `
            <div
                data-controller="live"
                data-live-url-value="http://localhost"
            >
                <label>
                First Name:
                <input
                    data-model="user[firstName]"
                    data-action="live#update"
                    value="${data.user.firstName}"
                >
                </label>
            </div>
        `;
        const data = { user: { firstName: 'Ryan' } };
        const { element, controller } = await startStimulus(
            deeperModelTemplate(data),
            data
        );

        // replace data-model with name attribute
        const inputElement = getByLabelText(element, 'First Name:');

        const newData = { user: { firstName: 'Ryan Weaver' } };
        fetchMock.getOnce('http://localhost/?user%5BfirstName%5D=Ryan+WEAVER', {
            html: deeperModelTemplate(newData),
            data: newData
        });

        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({ user: { firstName: 'Ryan Weaver' } });

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('updates correctly when live#update is on a parent element', async () => {
        const parentUpdateTemplate = (data) => `
            <div
                data-controller="live"
                data-live-url-value="http://localhost"
            >
                <div data-action="input->live#update">
                    <label>
                    Name:
                    <input
                        name="firstName"
                        value="${data.firstName}"
                    >
                    </label>
                </div>
            </div>
        `;

        const data = { firstName: 'Ryan' };
        const { element, controller } = await startStimulus(
            parentUpdateTemplate(data),
            data
        );

        fetchMock.getOnce('http://localhost/?firstName=Ryan+WEAVER', {
            html: parentUpdateTemplate({ firstName: 'Ryan Weaver' }),
            data: { firstName: 'Ryan Weaver' }
        });

        const inputElement = getByLabelText(element, 'Name:');
        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));

        fetchMock.done();

        // assert the input is still focused after rendering
        expect(document.activeElement.getAttribute('name')).toEqual('firstName');
    });

    it('tracks which fields should be modified, sends, without forgetting previous fields', async () => {
        // start with one other field in validatedFields
        const data = { name: 'Ryan', validatedFields: ['otherField'] };
        const { element, controller } = await startStimulus(
            template(data),
            data
        );

        fetchMock.getOnce('http://localhost/?name=Ryan+WEAVER&validatedFields%5B0%5D=otherField&validatedFields%5B1%5D=name', {
            html: template({ name: 'Ryan Weaver' }),
            data: { name: 'Ryan Weaver' }
        });

        const inputElement = getByLabelText(element, 'Name:');
        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    // TODO - test changing debounce
    // TODO - test deferred rendering
});
