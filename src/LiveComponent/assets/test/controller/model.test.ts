/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { createTest, initComponent, shutdownTest } from '../tools';
import { getByLabelText, getByTestId, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

describe('LiveController data-model Tests', () => {
    afterEach(() => {
        shutdownTest();
    })

    it('sends data and re-renders correctly when data-model element is changed', async () => {
        const test = await createTest({ name: 'Ryan' }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="name"
                    value="${data.name}"
                >
                
                Name is: ${data.name}
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ name: 'Ryan Weaver' })
            .init();

        await userEvent.type(test.queryByDataModel('name'), ' Weaver', {
            // this tests the debounce: characters have a 10ms delay
            // in between, but the debouncing prevents multiple calls
            delay: 10
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Name is: Ryan Weaver'));
        expect(test.component.valueStore.all()).toEqual({name: 'Ryan Weaver'});

        // assert the input is still focused after rendering
        expect(document.activeElement).toBeInstanceOf(HTMLElement);
        expect((document.activeElement as HTMLElement).dataset.model).toEqual('name');
    });

    it('updates the data without re-rendering if "norender" is used', async () => {
        const test = await createTest({ name: 'Ryan' }, (data: any) => `
            <div ${initComponent(data, { debounce: 1 })}>
                <input
                    data-model="norender|name"
                    value="${data.name}"
                >
                
                Name is: ${data.name}
            </div>
        `);

        await userEvent.type(test.queryByDataModel('name'), ' Weaver', {
            // debounce is only 1, so this "would" send MANY Ajax requests
            // if "norender" were NOT used
            delay: 10
        });

        // component never re-rendered
        expect(test.element).toHaveTextContent('Name is: Ryan');
        // but the value was updated
        expect(test.component.valueStore.all()).toEqual({name: 'Ryan Weaver'});
    });

    it('waits to update data and rerender until change event with on(change)', async () => {
        const test = await createTest({ name: 'Ryan' }, (data: any) => `
            <div ${initComponent(data, { debounce: 1 })}>
                <input
                    data-model="on(change)|name"
                    value="${data.name}"
                >
                
                Name is: ${data.name}
                <button>Do nothing</button>
            </div>
        `);

        await userEvent.type(test.queryByDataModel('name'), ' Weaver', {
            // debounce is only 1, so this "would" send MANY Ajax requests
            // if on(change) were NOT used (each character only triggers an "input" event)
            delay: 10
        });

        // component has not *yet* re-rendered
        expect(test.element).toHaveTextContent('Name is: Ryan');
        // the value has not *yet* been updated
        expect(test.component.valueStore.all()).toEqual({name: 'Ryan'});

        // NOW we expect the render
        test.expectsAjaxCall('get')
            .expectSentData({ name: 'Ryan Weaver' })
            .init();

        // this will cause the input to "blur" and trigger the change event
        userEvent.click(getByText(test.element, 'Do nothing'));

        await waitFor(() => expect(test.element).toHaveTextContent('Name is: Ryan Weaver'));
        expect(test.component.valueStore.all()).toEqual({name: 'Ryan Weaver'});
    });

    it('renders correctly with data-value and live#update on a non-input', async () => {
        const test = await createTest({ name: 'Ryan' }, (data: any) => `
            <div ${initComponent(data)}>
                <a data-action="live#update" data-model="name" data-value="Jan">Change name to Jan</a>
                
                Name is: ${data.name}
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ name: 'Jan' })
            .init();

        userEvent.click(getByText(test.element, 'Change name to Jan'));

        await waitFor(() => expect(test.element).toHaveTextContent('Name is: Jan'));
        expect(test.component.valueStore.all()).toEqual({name: 'Jan'});
    });

    it('falls back to using the name attribute when no data-model is present and <form data-model> is ancestor', async () => {
        const test = await createTest({ color: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model>
                    <input
                        name="color"
                        value="${data.color}"
                    >
                </form>
                
                Favorite color: ${data.color}
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ color: `orange` })
            .init();

        await userEvent.type(test.queryByNameAttribute('color'), 'orange');

        await waitFor(() => expect(test.element).toHaveTextContent('Favorite color: orange'));
        expect(test.component.valueStore.all()).toEqual({ color: 'orange' });
    });

    it('uses data-model when both name and data-model is present', async () => {
        const test = await createTest({ name: '', firstName: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <input
                        name="name"
                        data-model="firstName"
                        value="${data.firstName}"
                    >
                </form>
                
                First name: ${data.firstName}
            </div>
        `);

        test.expectsAjaxCall('get')
            // firstName is the model that is matched and used
            .expectSentData({ name: '', firstName: 'Ryan' })
            .init();

        await userEvent.type(test.queryByDataModel('firstName'), 'Ryan');

        await waitFor(() => expect(test.element).toHaveTextContent('First name: Ryan'));
        expect(test.component.valueStore.all()).toEqual({ firstName: 'Ryan', name: '' });
    });

    it('uses data-value when both value and data-value is present', async () => {
        const test = await createTest({ sport: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="sport"
                    value="${data.sport}"
                    data-value="cross country"
                >
                
                Sport: ${data.sport}
            </div>
        `);

        test.expectsAjaxCall('get')
            // "cross country" takes precedence over real value
            .expectSentData({ sport: 'cross country' })
            .init();

        await userEvent.type(test.queryByDataModel('sport'), 'steeple chase');

        await waitFor(() => expect(test.element).toHaveTextContent('Sport: cross country'));
    });

    it('standardizes user[name] style models into user.name', async () => {
        const test = await createTest({ user: { name: 'Ryan' } }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="user[name]"
                    value="${data.user.name}"
                >
                
                Name: ${data.user.name}
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ user: { name: 'Ryan Weaver' } })
            .init();

        await userEvent.type(test.queryByDataModel('user[name]'), ' Weaver');

        await waitFor(() => expect(test.element).toHaveTextContent('Name: Ryan Weaver'));
        expect(test.component.valueStore.all()).toEqual({ user: { name: 'Ryan Weaver' } });
    });

    it('sends correct data for checkbox fields', async () => {
        const test = await createTest({ form: {
            check1: false,
            check2: false
        } }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Checkbox 1: <input type="checkbox" name="form[check1]" value="1" ${data.form.check1 ? 'checked' : ''} />
                    </label>

                    <label>
                        Checkbox 2: <input type="checkbox" name="form[check2]" value="1" ${data.form.check2 ? 'checked' : ''} />
                    </label>
                </form>
                
                Checkbox 2 is ${data.form.check2 ? 'checked' : 'unchecked' }
            </div>
        `);

        const check1Element = getByLabelText(test.element, 'Checkbox 1:');
        const check2Element = getByLabelText(test.element, 'Checkbox 2:');

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall('get')
            .expectSentData({ form: {check1: '1', check2: '1'} })
            .init();

        await userEvent.click(check2Element);
        await userEvent.click(check1Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 2 is checked'));

        expect(test.component.valueStore.all()).toEqual({form: {check1: '1', check2: '1'}});
    });

    it('sends correct data for initially checked checkbox fields', async () => {
        const test = await createTest({ form: {
            check1: '1',
            check2: false
        } }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Checkbox 1: <input type="checkbox" name="form[check1]" value="1" ${data.form.check1 ? 'checked' : ''} />
                    </label>

                    <label>
                        Checkbox 2: <input type="checkbox" name="form[check2]" value="1" ${data.form.check2 ? 'checked' : ''} />
                    </label>
                </form>
                
                Checkbox 1 is ${data.form.check1 ? 'checked' : 'unchecked' }
            </div>
        `);

        const check1Element = getByLabelText(test.element, 'Checkbox 1:');
        const check2Element = getByLabelText(test.element, 'Checkbox 2:');

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall('get')
            .expectSentData({ form: {check1: null, check2: '1'} })
            .init();

        await userEvent.click(check2Element);
        await userEvent.click(check1Element);
        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(test.component.valueStore.all()).toEqual({form: {check1: null, check2: '1'}});
    });

    it('sends correct data for array valued checkbox fields', async () => {
        const test = await createTest({ form: { check: [] } }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Checkbox 1: <input type="checkbox" name="form[check][]" value="foo" ${data.form.check.indexOf('foo') > -1 ? 'checked' : ''} />
                    </label>

                    <label>
                        Checkbox 2: <input type="checkbox" name="form[check][]" value="bar" ${data.form.check.indexOf('bar') > -1 ? 'checked' : ''} />
                    </label>
                </form>
                
                Checkbox 2 is ${data.form.check.indexOf('bar') > -1 ? 'checked' : 'unchecked' }
            </div>
        `);

        const check1Element = getByLabelText(test.element, 'Checkbox 1:');
        const check2Element = getByLabelText(test.element, 'Checkbox 2:');

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall('get')
            .expectSentData({ form: {check: ['foo', 'bar']} })
            .init();

        await userEvent.click(check1Element);
        await userEvent.click(check2Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 2 is checked'));

        expect(test.component.valueStore.all()).toEqual({form: {check: ['foo', 'bar']}});
    });

    it('sends correct data for array valued checkbox fields with initial data', async () => {
        const test = await createTest({ form: { check: ['foo']} }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Checkbox 1: <input type="checkbox" name="form[check][]" value="foo" ${data.form.check.indexOf('foo') > -1 ? 'checked' : ''} />
                    </label>

                    <label>
                        Checkbox 2: <input type="checkbox" name="form[check][]" value="bar" ${data.form.check.indexOf('bar') > -1 ? 'checked' : ''} />
                    </label>
                </form>
                
                Checkbox 1 is ${data.form.check.indexOf('foo') > -1 ? 'checked' : 'unchecked' }
            </div>
        `);

        const check1Element = getByLabelText(test.element, 'Checkbox 1:');
        const check2Element = getByLabelText(test.element, 'Checkbox 2:');

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall('get')
            .expectSentData({ form: { check: ['bar'] } })
            .init();

        await userEvent.click(check2Element);
        await userEvent.click(check1Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(test.component.valueStore.all()).toEqual({form: {check: ['bar']}});
    });

    it('sends correct data for select multiple field', async () => {
        const test = await createTest({ form: { select: []} }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Select:
                        <select name="form[select][]" multiple>
                            <option value="foo" ${data.form.select?.indexOf('foo') > -1 ? 'selected' : ''}>foo</option>
                            <option value="bar" ${data.form.select?.indexOf('bar') > -1 ? 'selected' : ''}>bar</option>
                        </select>
                    </label>
                </form>
                
                Option 2 is ${data.form.select?.indexOf('bar') > -1 ? 'selected' : 'unselected' }
            </div>
        `);

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall('get')
            .expectSentData({ form: { select: ['foo', 'bar'] } })
            .init();

        const selectElement = getByLabelText(test.element, 'Select:');
        await userEvent.selectOptions(selectElement, 'foo');
        await userEvent.selectOptions(selectElement, 'bar');

        await waitFor(() => expect(test.element).toHaveTextContent('Select: foo bar Option 2 is selected'));

        expect(test.component.valueStore.all()).toEqual({form: {select: ['foo', 'bar']}});
    });

    it('sends correct data for select multiple field with initial data', async () => {
        const test = await createTest({ form: { select: ['foo']} }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Select:
                        <select name="form[select][]" multiple>
                            <option value="foo" ${data.form.select?.indexOf('foo') > -1 ? 'selected' : ''}>foo</option>
                            <option value="bar" ${data.form.select?.indexOf('bar') > -1 ? 'selected' : ''}>bar</option>
                        </select>
                    </label>
                </form>
                
                Option 2 is ${data.form.select?.indexOf('bar') > -1 ? 'selected' : 'unselected' }
            </div>
        `);

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall('get')
            .expectSentData({ form: { select: ['bar'] } })
            .init();

        const selectElement = getByLabelText(test.element, 'Select:');
        await userEvent.selectOptions(selectElement, 'bar');
        await userEvent.deselectOptions(selectElement, 'foo');

        await waitFor(() => expect(test.element).toHaveTextContent('Select: foo bar Option 2 is selected'));

        test.expectsAjaxCall('get')
            .expectSentData({ form: { select: [] } })
            .init();
        await userEvent.deselectOptions(selectElement, 'bar');

        await waitFor(() => expect(test.element).toHaveTextContent('Select: foo bar Option 2 is unselected'));
        expect(test.component.valueStore.all()).toEqual({form: {select: []}});
    });

    it('tracks which fields should be validated and sends, without forgetting previous fields', async () => {
        // start with one field in validatedFields
        const test = await createTest({ treat: '', validatedFields: ['otherField'] }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="treat"
                    value="${data.treat}"
                >

                Treat: ${data.treat}
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({
                treat: 'ice cream',
                validatedFields: ['otherField', 'treat']
            })
            .init();

        await userEvent.type(test.queryByDataModel('treat'), 'ice cream');

        await waitFor(() => expect(test.element).toHaveTextContent('Treat: ice cream'));
    });

    it('data changed on server should be noticed by controller and used in dataValue', async () => {
        const test = await createTest({ pizzaTopping: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="pizzaTopping"
                    value="${data.pizzaTopping}"
                >

                Mmmm ${data.pizzaTopping} pizza
            </div>
        `);

        test.expectsAjaxCall('get')
            .expectSentData({ pizzaTopping: 'mushroom' })
            .serverWillChangeData((data) => {
                // sneaky server changes the data!
                data.pizzaTopping = 'pineapple';
            })
            .init();

        await userEvent.type(test.queryByDataModel('pizzaTopping'), 'mushroom');

        await waitFor(() => expect(test.element).toHaveTextContent('Mmmm pineapple pizza'));
        // the controller sees the new data and adopts it
        expect(test.component.valueStore.all()).toEqual({ pizzaTopping: 'pineapple' });
    });

    it('sends a render request without debounce for change events', async () => {
        const test = await createTest({ firstName: '', lastName: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <input data-model="on(change)|firstName" value="${data.firstName}">
                <input data-model="on(change)|lastName" value="${data.lastName}">

                <button>Do nothing</button>

                First Name: ${data.firstName}
                Last Name: ${data.lastName}
            </div>
        `);

        // TWO requests because debouncing doesn't prevent the 2nd
        test.expectsAjaxCall('get')
            .expectSentData({ firstName: 'Ryan', lastName: '' })
            .init();
        test.expectsAjaxCall('get')
            .expectSentData({ firstName: 'Ryan', lastName: 'Weaver' })
            .init();

        await userEvent.type(test.queryByDataModel('firstName'), 'Ryan');
        await userEvent.type(test.queryByDataModel('lastName'), 'Weaver', {
            // delay *slightly* to give the previous Ajax request time to start
            // but this is still far less than the debounce. So if debouncing
            // WERE happening, this would still be fast enough to prevent the
            // first request
            delay: 1,
        });
        // triggers the "change" event on the 2nd field
        userEvent.click(getByText(test.element, 'Do nothing'));

        await waitFor(() => expect(test.element).toHaveTextContent('First Name: Ryan'));
        await waitFor(() => expect(test.element).toHaveTextContent('Last Name: Weaver'));
    });

    it('notices the "real" value of a select without an empty value', async () => {
        const test = await createTest({ food: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <select data-model="food">
                    <option value="carrot">🥕</option>
                    <option value="brocolli">🥦</option>
                </select>

                Food: ${data.food}

                <button data-action="live#$render">Reload</button>
            </div>
        `);

        // "carrot" is sent because, in practice, that's what's selected
        test.expectsAjaxCall('get')
            .expectSentData({ food: 'carrot' })
            .init();

        getByText(test.element, 'Reload').click();
        await waitFor(() => expect(test.element).toHaveTextContent('Food: carrot'));
    });

    it('allows model fields to be set manually and rolled into a single request', async () => {
        const test = await createTest({ food: '', dessert: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <!-- using "change" because it has 0 debounce, which is the more -->
                <!-- complex case for trying to get both model updates into 1 request -->
                <select data-model="on(change)|food" data-testid="food-select">
                    <option value="carrot">🥕</option>
                    <option value="brocolli">🥦</option>
                </select>

                <select data-model="on(change)|dessert" data-testid="dessert-select">
                    <option value="">choose a dessert</option>
                    <option value="carrot_cake">carrot cake</option>
                    <option value="ice_cream">ice creamoption>
                </select>

                Food: ${data.food}
                Dessert: ${data.dessert}
            </div>
        `);

        // just 1 request with both model changes
        test.expectsAjaxCall('get')
            .expectSentData({ food: 'carrot', dessert: 'carrot_cake' })
            .init();

        const foodSelect = getByTestId(test.element, 'food-select');

        // when food changes, manually change the dessert
        foodSelect.addEventListener('change', () => {
            const dessertSelect = getByTestId(test.element, 'dessert-select');
            if (!(dessertSelect instanceof HTMLSelectElement)) {
                throw new Error('wrong element');
            }
            dessertSelect.value = 'carrot_cake';
            dessertSelect.dispatchEvent(new Event('change', { bubbles: true }));
        });

        await userEvent.selectOptions(foodSelect, 'carrot');

        await waitFor(() => expect(test.element).toHaveTextContent('Food: carrot'));
        expect(test.element).toHaveTextContent('Dessert: carrot_cake');
    });

    it('sets the value of data-model elements initially and after render', async () => {
        const test = await createTest({ food: 'carrot', comment: 'mmmm' }, (data: any) => `
            <div ${initComponent(data)}>
                <select data-model="food">
                    <option value="">choose a food</option>
                    <option value="carrot">🥕</option>
                    <option value="brocolli" selected>🥦</option>
                </select>

                <textarea data-model="comment"></textarea>

                Comment: ${data.comment}
            </div>
        `);

        const foodSelect = test.queryByDataModel('food');
        if (!(foodSelect instanceof HTMLSelectElement)) {
            throw new Error('wrong type');
        }
        expect(foodSelect.selectedOptions.length).toEqual(1);
        expect(foodSelect.selectedOptions[0].value).toEqual('carrot');

        const commentField = test.queryByDataModel('comment');
        if (!(commentField instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        expect(commentField.value).toEqual('mmmm');

        // NOW we will re-render
        test.expectsAjaxCall('get')
            .expectSentData({ food: 'carrot', comment: 'mmmm so good' })
            // change the data to be extra tricky
            .serverWillChangeData((data) => {
                data.food = 'brocolli';
                data.comment = data.comment.toUpperCase();
            })
            .init();

        await userEvent.type(commentField, ' so good');
        await waitFor(() => expect(test.element).toHaveTextContent('Comment: MMMM SO GOOD'));

        expect(foodSelect.selectedOptions.length).toEqual(1);
        expect(foodSelect.selectedOptions[0].value).toEqual('brocolli');
        expect(commentField.value).toEqual('MMMM SO GOOD');
    });

    it('keeps the unsynced value of an input on re-render, but accepts other changes to the field', async () => {
        const test = await createTest({
            comment: 'Live components',
            unmappedTextareaValue: 'no data-model',
            fieldClass: 'initial-class'
        }, (data: any) => `
           <div ${initComponent(data)}>
               <textarea data-model="on(change)|comment" class="${data.fieldClass}"></textarea>

               <textarea class="${data.fieldClass}" data-testid="unmappedTextarea">${data.unmappedTextareaValue}</textarea>

               FieldClass: ${data.fieldClass}

              <button data-action="live#$render">Reload</button>
           </div>
       `);

        test.expectsAjaxCall('get')
            .expectSentData(test.initialData)
            .serverWillChangeData((data) => {
                data.fieldClass = 'changed-class';
            })
            // delay slightly so we can type in the textarea
            .delayResponse(10)
            .init();

        getByText(test.element, 'Reload').click();
        const commentField = test.queryByDataModel('comment');
        if (!(commentField instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        // mimic changing the field, but without (yet) triggering the change event
        commentField.value = commentField.value + ' ftw!';
        commentField.dispatchEvent(new Event('input', { bubbles: true }));

        // also type into the unmapped field - but no worry about the model sync'ing this time
        userEvent.type(getByTestId(test.element, 'unmappedTextarea'), ' here!')

        await waitFor(() => expect(test.element).toHaveTextContent('FieldClass: changed-class'));
        // re-find in case the element itself has changed by morphdom
        const commentFieldAfterRender = test.queryByDataModel('comment');
        if (!(commentFieldAfterRender instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        // the newly-typed characters have been kept
        expect(commentFieldAfterRender.value).toEqual('Live components ftw!');
        // double-check that the model hasn't been updated yet (else bug in test)
        expect(test.component.valueStore.get('comment')).toEqual('Live components');
        expect(commentFieldAfterRender.getAttribute('class')).toEqual('changed-class');

        const unmappedTextarea = getByTestId(test.element, 'unmappedTextarea');
        if (!(unmappedTextarea instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        // the newly-typed characters have been kept
        expect(unmappedTextarea.value).toEqual('no data-model here!');
        expect(unmappedTextarea.getAttribute('class')).toEqual('changed-class');
    });
});
