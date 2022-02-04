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
import { getByLabelText, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';
import fetchMock from 'fetch-mock-jest';

describe('LiveController data-model Tests', () => {
    const template = (data) => `
        <div
            ${initLiveComponent('/_components/my_component', data)}
        >
            <label>
            Name:
            <input
                data-model="name"
                data-action="live#update"
                value="${data.name}"
            >
            </label>
            <a data-action="live#update" data-model="name" data-value="Jan">Change name to Jan</a>
        </div>
    `;

    afterEach(() => {
        clearDOM();
        if (!fetchMock.done()) {
            throw new Error('Mocked requests did not match');
        }
        fetchMock.reset();
    });

    it('renders correctly with data-model and live#update', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        mockRerender({name: 'Ryan WEAVER'}, template, (data: any) => {
            data.name = 'Ryan Weaver';
        });

        await userEvent.type(getByLabelText(element, 'Name:'), ' WEAVER', {
            // this tests the debounce: characters have a 10ms delay
            // in between, but the debouncing prevents multiple calls
            delay: 10
        });

        await waitFor(() => expect(getByLabelText(element, 'Name:')).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({name: 'Ryan Weaver'});

        // assert the input is still focused after rendering
        expect(document.activeElement.dataset.model).toEqual('name');
    });

    it('renders correctly with data-value and live#update', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        mockRerender({name: 'Jan'}, template);

        userEvent.click(getByText(element, 'Change name to Jan'));

        await waitFor(() => expect(getByLabelText(element, 'Name:')).toHaveValue('Jan'));
        expect(controller.dataValue).toEqual({name: 'Jan'});
    });


    it('correctly only uses the most recent render call results', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

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
            mockRerender({name: `Ryan${string}`}, template, (data: any) => {
                data.name = `Ryan${string}_`;
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

        // only 1 render should have ultimately occurred
        expect(renderCount).toEqual(1);
    });

    it('falls back to using the name attribute when no data-model is present', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        // replace data-model with name attribute
        const inputElement = getByLabelText(element, 'Name:');
        delete inputElement.dataset.model;
        inputElement.setAttribute('name', 'name');

        mockRerender({name: 'Ryan WEAVER'}, template, (data: any) => {
            data.name = 'Ryan Weaver';
        });

        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({name: 'Ryan Weaver'});
    });

    it('uses data-model when both name and data-model is present', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        // give element data-model="name" and name="first_name"
        const inputElement = getByLabelText(element, 'Name:');
        inputElement.setAttribute('name', 'first_name');

        // ?name should be what's sent to the server
        mockRerender({name: 'Ryan WEAVER'}, template, (data) => {
            data.name = 'Ryan Weaver';
        })

        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({name: 'Ryan Weaver'});
    });

    it('uses data-value when both value and data-value is present', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        // give element data-model="name" and name="first_name"
        const inputElement = getByLabelText(element, 'Name:');
        inputElement.dataset.value = 'first_name';

        // ?name should be what's sent to the server
        mockRerender({name: 'first_name'}, template, (data) => {
            data.name = 'first_name';
        })

        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('first_name'));
        expect(controller.dataValue).toEqual({name: 'first_name'});
    });

    it('standardizes user[firstName] style models into post.name', async () => {
        const deeperModelTemplate = (data) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
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
        const { element, controller } = await startStimulus(deeperModelTemplate(data));

        // replace data-model with name attribute
        const inputElement = getByLabelText(element, 'First Name:');

        mockRerender({'user': {firstName: 'Ryan WEAVER'}}, deeperModelTemplate, (data) => {
            data.user.firstName = 'Ryan Weaver';
        });

        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));
        expect(controller.dataValue).toEqual({ user: { firstName: 'Ryan Weaver' } });
    });

    it('sends correct data for checkbox fields', async () => {
        const checkboxTemplate = (data: any) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
                data-action="change->live#update"
            >
                <label>
                    Checkbox 1: <input type="checkbox" name="form[check1]" value="1" ${data.form.check1 ? 'checked' : ''} />
                </label>
                
                <label>
                    Checkbox 2: <input type="checkbox" name="form[check2]" value="1" ${data.form.check2 ? 'checked' : ''} />
                </label>
                
                Checkbox 2 is ${data.form.check2 ? 'checked' : 'unchecked' }
            </div>
        `;
        const data = { form: { check1: false, check2: false} };
        const { element, controller } = await startStimulus(checkboxTemplate(data));

        const check1Element = getByLabelText(element, 'Checkbox 1:');
        const check2Element = getByLabelText(element, 'Checkbox 2:');

        // no mockRerender needed... not sure why. This first Ajax call is likely
        // interrupted by the next immediately starting
        await userEvent.click(check1Element);

        mockRerender({ form: {check1: '1', check2: '1'}}, checkboxTemplate);

        await userEvent.click(check2Element);
        await waitFor(() => expect(element).toHaveTextContent('Checkbox 2 is checked'));

        expect(controller.dataValue).toEqual({form: {check1: '1', check2: '1'}});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('sends correct data for initially checked checkbox fields', async () => {
        const checkboxTemplate = (data: any) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
                data-action="change->live#update"
            >
                <label>
                    Checkbox 1: <input type="checkbox" name="form[check1]" value="1" ${data.form.check1 ? 'checked' : ''} />
                </label>
                
                <label>
                    Checkbox 2: <input type="checkbox" name="form[check2]" value="1" ${data.form.check2 ? 'checked' : ''} />
                </label>
                
                Checkbox 1 is ${data.form.check1 ? 'checked' : 'unchecked' }
            </div>
        `;
        const data = { form: { check1: '1', check2: false} };
        const { element, controller } = await startStimulus(checkboxTemplate(data));

        const check1Element = getByLabelText(element, 'Checkbox 1:');
        const check2Element = getByLabelText(element, 'Checkbox 2:');

        // no mockRerender needed... not sure why. This first Ajax call is likely
        // interrupted by the next immediately starting
        await userEvent.click(check2Element);

        mockRerender({ form: {check1: null, check2: '1'}}, checkboxTemplate);

        await userEvent.click(check1Element);
        await waitFor(() => expect(element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(controller.dataValue).toEqual({form: {check1: null, check2: '1'}});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('sends correct data for array valued checkbox fields', async () => {
        const checkboxTemplate = (data: any) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
                data-action="change->live#update"
            >
                <label>
                    Checkbox 1: <input type="checkbox" name="form[check][]" value="foo" ${data.form.check.indexOf('foo') > -1 ? 'checked' : ''} />
                </label>
                
                <label>
                    Checkbox 2: <input type="checkbox" name="form[check][]" value="bar" ${data.form.check.indexOf('bar') > -1 ? 'checked' : ''} />
                </label>
                
                Checkbox 2 is ${data.form.check.indexOf('bar') > -1 ? 'checked' : 'unchecked' }
            </div>
        `;
        const data = { form: { check: []} };
        const { element, controller } = await startStimulus(checkboxTemplate(data));

        const check1Element = getByLabelText(element, 'Checkbox 1:');
        const check2Element = getByLabelText(element, 'Checkbox 2:');

        // no mockRerender needed... not sure why. This first Ajax call is likely
        // interrupted by the next immediately starting
        await userEvent.click(check1Element);

        mockRerender({ form: {check: ['foo', 'bar']}}, checkboxTemplate);

        await userEvent.click(check2Element);
        await waitFor(() => expect(element).toHaveTextContent('Checkbox 2 is checked'));

        expect(controller.dataValue).toEqual({form: {check: ['foo', 'bar']}});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('sends correct data for array valued checkbox fields with initial data', async () => {
        const checkboxTemplate = (data: any) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
                data-action="change->live#update"
            >
                <label>
                    Checkbox 1: <input type="checkbox" name="form[check][]" value="foo" ${data.form.check.indexOf('foo') > -1 ? 'checked' : ''} />
                </label>
                
                <label>
                    Checkbox 2: <input type="checkbox" name="form[check][]" value="bar" ${data.form.check.indexOf('bar') > -1 ? 'checked' : ''} />
                </label>
                
                Checkbox 1 is ${data.form.check.indexOf('foo') > -1 ? 'checked' : 'unchecked' }
            </div>
        `;
        const data = { form: { check: ['foo']} };
        const { element, controller } = await startStimulus(checkboxTemplate(data));

        const check1Element = getByLabelText(element, 'Checkbox 1:');
        const check2Element = getByLabelText(element, 'Checkbox 2:');

        // no mockRerender needed... not sure why. This first Ajax call is likely
        // interrupted by the next immediately starting
        await userEvent.click(check2Element);

        mockRerender({ form: {check: ['bar']}}, checkboxTemplate);

        await userEvent.click(check1Element);
        await waitFor(() => expect(element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(controller.dataValue).toEqual({form: {check: ['bar']}});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('sends correct data for select multiple field', async () => {
        const checkboxTemplate = (data: any) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
                data-action="change->live#update"
            >
                <label>
                    Select:
                    <select name="form[select][]" multiple>
                        <option value="foo" ${data.form.select.indexOf('foo') > -1 ? 'selected' : ''}>foo</option>
                        <option value="bar" ${data.form.select.indexOf('bar') > -1 ? 'selected' : ''}>bar</option>
                    </select>
                </label>
                
                Option 2 is ${data.form.select.indexOf('bar') > -1 ? 'selected' : 'unselected' }
            </div>
        `;
        const data = { form: { select: []} };
        const { element, controller } = await startStimulus(checkboxTemplate(data));

        const selectElement = getByLabelText(element, 'Select:');

        // no mockRerender needed... not sure why. This first Ajax call is likely
        // interrupted by the next immediately starting
        await userEvent.selectOptions(selectElement, 'foo');

        mockRerender({ form: {select: ['foo', 'bar']}}, checkboxTemplate);

        await userEvent.selectOptions(selectElement, 'bar');

        await waitFor(() => expect(element).toHaveTextContent('Select: foo bar Option 2 is selected'));

        expect(controller.dataValue).toEqual({form: {select: ['foo', 'bar']}});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('sends correct data for select multiple field with initial data', async () => {
        const checkboxTemplate = (data: any) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
                data-action="change->live#update"
            >
                <label>
                    Select:
                    <select name="form[select][]" multiple>
                        <option value="foo" ${data.form.select.indexOf('foo') > -1 ? 'selected' : ''}>foo</option>
                        <option value="bar" ${data.form.select.indexOf('bar') > -1 ? 'selected' : ''}>bar</option>
                    </select>
                </label>
                
                Option 2 is ${data.form.select.indexOf('bar') > -1 ? 'selected' : 'unselected' }
            </div>
        `;
        const data = { form: { select: ['foo']} };
        const { element, controller } = await startStimulus(checkboxTemplate(data));

        const selectElement = getByLabelText(element, 'Select:');

        // no mockRerender needed... not sure why. This first Ajax call is likely
        // interrupted by the next immediately starting
        await userEvent.selectOptions(selectElement, 'bar');

        mockRerender({ form: {select: ['bar']}}, checkboxTemplate);

        await userEvent.deselectOptions(selectElement, 'foo');

        await waitFor(() => expect(element).toHaveTextContent('Select: foo bar Option 2 is selected'));

        mockRerender({ form: {select: null}}, checkboxTemplate);

        await userEvent.deselectOptions(selectElement, 'bar');

        await waitFor(() => expect(element).toHaveTextContent('Select: foo bar Option 2 is deselected'));

        expect(controller.dataValue).toEqual({form: {select: null}});

        // assert all calls were done the correct number of times
        fetchMock.done();
    });

    it('updates correctly when live#update is on a parent element', async () => {
        const parentUpdateTemplate = (data) => `
            <div
                ${initLiveComponent('/_components/my_component', data)}
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
        const { element } = await startStimulus(parentUpdateTemplate(data));

        mockRerender({firstName: 'Ryan WEAVER'}, parentUpdateTemplate, (data) => {
            data.firstName = 'Ryan Weaver';
        });

        const inputElement = getByLabelText(element, 'Name:');
        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));

        // assert the input is still focused after rendering
        expect(document.activeElement.getAttribute('name')).toEqual('firstName');
    });

    it('tracks which fields should be modified, sends, without forgetting previous fields', async () => {
        // start with one other field in validatedFields
        const data = { name: 'Ryan', validatedFields: ['otherField'] };
        const { element } = await startStimulus(template(data));

        mockRerender({name: 'Ryan WEAVER', validatedFields: ['otherField', 'name']}, template, (data) => {
            data.name = 'Ryan Weaver';
        });

        const inputElement = getByLabelText(element, 'Name:');
        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Ryan Weaver'));
    });

    it('data changed on server should be noticed by controller', async () => {
        const data = { name: 'Ryan' };
        const { element, controller } = await startStimulus(template(data));

        mockRerender({name: 'Ryan WEAVER'}, template, (data) => {
            // sneaky server changes the data!
            data.name = 'Kevin Bond';
        });

        const inputElement = getByLabelText(element, 'Name:');
        await userEvent.type(inputElement, ' WEAVER');

        await waitFor(() => expect(inputElement).toHaveValue('Kevin Bond'));
        expect(controller.dataValue).toEqual({name: 'Kevin Bond'});
    });
});
