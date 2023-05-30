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
import { getByLabelText, getByTestId, getByText, waitFor } from '@testing-library/dom';
import userEvent from '@testing-library/user-event';

describe('LiveController data-model Tests', () => {
    afterEach(() => {
        shutdownTests();
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

        test.expectsAjaxCall()
            .expectUpdatedData({ name: 'Ryan Weaver' });

        await userEvent.type(test.queryByDataModel('name'), ' Weaver', {
            // this tests the debounce: characters have a 10ms delay
            // in between, but the debouncing prevents multiple calls
            delay: 10
        });

        await waitFor(() => expect(test.element).toHaveTextContent('Name is: Ryan Weaver'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({name: 'Ryan Weaver'});

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
        expect(test.component.valueStore.get('name')).toEqual('Ryan Weaver');
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
        // the read-only props have not *yet* been updated
        expect(test.component.valueStore.getOriginalProps()).toEqual({name: 'Ryan'});

        // NOW we expect the render
        test.expectsAjaxCall()
            .expectUpdatedData({ name: 'Ryan Weaver' });

        // this will cause the input to "blur" and trigger the change event
        userEvent.click(getByText(test.element, 'Do nothing'));

        await waitFor(() => expect(test.element).toHaveTextContent('Name is: Ryan Weaver'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({name: 'Ryan Weaver'});
    });

    it('renders correctly with data-value and live#update on a non-input', async () => {
        const test = await createTest({ name: 'Ryan' }, (data: any) => `
            <div ${initComponent(data)}>
                <a data-action="live#update" data-model="name" data-value="Jan">Change name to Jan</a>
                
                Name is: ${data.name}
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ name: 'Jan' });

        userEvent.click(getByText(test.element, 'Change name to Jan'));

        await waitFor(() => expect(test.element).toHaveTextContent('Name is: Jan'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({name: 'Jan'});
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

        test.expectsAjaxCall()
            .expectUpdatedData({ color: 'orange' });

        await userEvent.type(test.queryByNameAttribute('color'), 'orange');

        await waitFor(() => expect(test.element).toHaveTextContent('Favorite color: orange'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({ color: 'orange' });
    });

    it('uses data-model when both name and data-model is present', async () => {
        const test = await createTest({ name: '', firstName: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <input
                        name="name"
                        data-model="firstName"
                    >
                </form>
                
                First name: ${data.firstName}
            </div>
        `);

        test.expectsAjaxCall()
            // firstName is the model that is matched and updated
            .expectUpdatedData({ firstName: 'Ryan' });

        await userEvent.type(test.queryByDataModel('firstName'), 'Ryan');

        await waitFor(() => expect(test.element).toHaveTextContent('First name: Ryan'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({ firstName: 'Ryan', name: '' });
    });

    it('uses data-value when both value and data-value is present', async () => {
        const test = await createTest({ sport: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="sport"
                    data-value="cross country"
                >
                
                Sport: ${data.sport}
            </div>
        `);

        test.expectsAjaxCall()
            // "cross country" takes precedence over real value
            .expectUpdatedData({ sport: 'cross country' });

        await userEvent.type(test.queryByDataModel('sport'), 'steeple chase');

        await waitFor(() => expect(test.element).toHaveTextContent('Sport: cross country'));
    });

    it('standardizes user[name] style models into user.name', async () => {
        const test = await createTest({ user: { name: 'Ryan' } }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="user[name]"
                >
                
                Name: ${data.user.name}
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ 'user.name': 'Ryan Weaver' });

        await userEvent.type(test.queryByDataModel('user[name]'), ' Weaver');

        await waitFor(() => expect(test.element).toHaveTextContent('Name: Ryan Weaver'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({ user: { name: 'Ryan Weaver' } });
    });

    it('can use models from nested props', async () => {
        const test = await createTest(
            { user: 5, 'user.name': 'Ryan' },
            (props: any) => `
                <div ${initComponent(props)}>
                    <input
                        data-model="user.name"
                    >

                    Name: ${props['user.name']}
                </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ 'user.name': 'Ryan Weaver' });

        await userEvent.type(test.queryByDataModel('user.name'), ' Weaver');

        await waitFor(() => expect(test.element).toHaveTextContent('Name: Ryan Weaver'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({ user: 5, 'user.name': 'Ryan Weaver' });
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
        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.check1': '1', 'form.check2': '1' });

        await userEvent.click(check2Element);
        await userEvent.click(check1Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 2 is checked'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({form: {check1: '1', check2: '1'}});
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
        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.check1': null, 'form.check2': '1' });

        await userEvent.click(check2Element);
        await userEvent.click(check1Element);
        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({form: {check1: null, check2: '1'}});
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
        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.check': ['foo', 'bar'] });

        await userEvent.click(check1Element);
        await userEvent.click(check2Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 2 is checked'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({form: {check: ['foo', 'bar']}});
    });

    it('sends correct data for array valued checkbox fields with non-form object', async () => {
        const test = await createTest({ check: [] }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model="*">
                    <label>
                        Checkbox 1: <input type="checkbox" name="check[]" value="foo" ${data.check.indexOf('foo') > -1 ? 'checked' : ''} />
                    </label>

                    <label>
                        Checkbox 2: <input type="checkbox" name="check[]" value="bar" ${data.check.indexOf('bar') > -1 ? 'checked' : ''} />
                    </label>
                </form>
                
                Checkbox 2 is ${data.check.indexOf('bar') > -1 ? 'checked' : 'unchecked' }
            </div>
        `);

        const check1Element = getByLabelText(test.element, 'Checkbox 1:');
        const check2Element = getByLabelText(test.element, 'Checkbox 2:');

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall()
            .expectUpdatedData({ 'check': ['foo', 'bar'] });

        await userEvent.click(check1Element);
        await userEvent.click(check2Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 2 is checked'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({check: ['foo', 'bar']});
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
        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.check': ['bar'] });

        await userEvent.click(check2Element);
        await userEvent.click(check1Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({form: {check: ['bar']}});
    });

    it('sends correct data for array valued checkbox fields with non-form object and with initial data', async () => {
        const test = await createTest({ check: ['foo'] }, (data: any) => `
            <div ${initComponent(data)}>
                <label>
                    Checkbox 1: <input type="checkbox" data-model="check[]" value="foo" ${data.check.indexOf('foo') > -1 ? 'checked' : ''} />
                </label>

                <label>
                    Checkbox 2: <input type="checkbox" data-model="check[]" value="bar" ${data.check.indexOf('bar') > -1 ? 'checked' : ''} />
                </label>
                
                Checkbox 1 is ${data.check.indexOf('foo') > -1 ? 'checked' : 'unchecked' }
            </div>
        `);

        const check1Element = getByLabelText(test.element, 'Checkbox 1:');
        const check2Element = getByLabelText(test.element, 'Checkbox 2:');

        // only 1 Ajax call will be made thanks to debouncing
        test.expectsAjaxCall()
            .expectUpdatedData({ 'check': ['bar'] });

        await userEvent.click(check1Element);
        await userEvent.click(check2Element);

        await waitFor(() => expect(test.element).toHaveTextContent('Checkbox 1 is unchecked'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({check: ['bar']});
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
        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.select': ['foo', 'bar'] });

        const selectElement = getByLabelText(test.element, 'Select:');
        await userEvent.selectOptions(selectElement, 'foo');
        await userEvent.selectOptions(selectElement, 'bar');

        await waitFor(() => expect(test.element).toHaveTextContent('Select: foo bar Option 2 is selected'));

        expect(test.component.valueStore.getOriginalProps()).toEqual({form: {select: ['foo', 'bar']}});
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
        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.select': ['bar'] });

        const selectElement = getByLabelText(test.element, 'Select:');
        await userEvent.selectOptions(selectElement, 'bar');
        await userEvent.deselectOptions(selectElement, 'foo');

        await waitFor(() => expect(test.element).toHaveTextContent('Select: foo bar Option 2 is selected'));

        test.expectsAjaxCall()
            .expectUpdatedData({ 'form.select': [] });
        await userEvent.deselectOptions(selectElement, 'bar');

        await waitFor(() => expect(test.element).toHaveTextContent('Select: foo bar Option 2 is unselected'));
        expect(test.component.valueStore.getOriginalProps()).toEqual({form: {select: []}});
    });

    it('tracks which fields should be validated and sends, without forgetting previous fields', async () => {
        // start with one field in validatedFields
        const test = await createTest({ treat: '', validatedFields: ['otherField'] }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="treat"
                >

                Treat: ${data.treat}
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({
                treat: 'ice cream',
                validatedFields: ['otherField', 'treat']
            });

        await userEvent.type(test.queryByDataModel('treat'), 'ice cream');

        await waitFor(() => expect(test.element).toHaveTextContent('Treat: ice cream'));
    });

    it('data changed on server should be noticed by controller and used in dataValue', async () => {
        const test = await createTest({ pizzaTopping: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <input
                    data-model="pizzaTopping"
                >

                Mmmm ${data.pizzaTopping} pizza
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ pizzaTopping: 'mushroom' })
            .serverWillChangeProps((data) => {
                // sneaky server changes the data!
                data.pizzaTopping = 'pineapple';
            });

        await userEvent.type(test.queryByDataModel('pizzaTopping'), 'mushroom');

        await waitFor(() => expect(test.element).toHaveTextContent('Mmmm pineapple pizza'));
        // the controller sees the new data and adopts it
        expect(test.component.valueStore.getOriginalProps()).toEqual({ pizzaTopping: 'pineapple' });
    });

    it('sends a render request without debounce for change events', async () => {
        const test = await createTest({ firstName: '', lastName: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <input data-model="on(change)|firstName">
                <input data-model="on(change)|lastName">

                <button>Do nothing</button>

                First Name: ${data.firstName}
                Last Name: ${data.lastName}
            </div>
        `);

        // TWO requests because debouncing doesn't prevent the 2nd
        test.expectsAjaxCall()
            .expectUpdatedData({ firstName: 'Ryan' });
        test.expectsAjaxCall()
            .expectUpdatedData({ lastName: 'Weaver' });

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
        test.expectsAjaxCall()
            .expectUpdatedData({ food: 'carrot' });

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
        test.expectsAjaxCall()
            .expectUpdatedData({ food: 'carrot', dessert: 'carrot_cake' });

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
        test.expectsAjaxCall()
            .expectUpdatedData({ comment: 'mmmm so good' })
            // change the data to be extra tricky
            .serverWillChangeProps((data) => {
                data.food = 'brocolli';
                data.comment = data.comment.toUpperCase();
            });

        await userEvent.type(commentField, ' so good');
        await waitFor(() => expect(test.element).toHaveTextContent('Comment: MMMM SO GOOD'));

        expect(foodSelect.selectedOptions.length).toEqual(1);
        expect(foodSelect.selectedOptions[0].value).toEqual('brocolli');
        expect(commentField.value).toEqual('MMMM SO GOOD');
    });

    it('does not try to set the value of inputs inside a child component', async () => {
        const test = await createTest({ comment: 'cookie', childComment: 'mmmm' }, (data: any) => `
            <div ${initComponent(data)}>
                <textarea data-model="comment" id="parent-comment"></textarea>

                <div ${initComponent({ comment: data.childComment }, {id: 'the-child-id'})}>
                    <textarea data-model="comment" id="child-comment"></textarea>
                </div>
            </div>
        `);

        const commentField = test.element.querySelector('#parent-comment');
        if (!(commentField instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        expect(commentField.value).toEqual('cookie');

        const childCommentField = test.element.querySelector('#child-comment');
        if (!(childCommentField instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        expect(childCommentField.value).toEqual('mmmm');

        // NOW we will re-render
        test.expectsAjaxCall()
            // change the data to be extra tricky
            .serverWillChangeProps((data) => {
                data.comment = 'i like apples';
            });

        await test.component.render();

        expect(commentField.value).toEqual('i like apples');
        // child component should not have been re-rendered
        expect(childCommentField.value).toEqual('mmmm');
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

        test.expectsAjaxCall()
            .serverWillChangeProps((data) => {
                data.fieldClass = 'changed-class';
            })
            // delay slightly so we can type in the textarea
            .delayResponse(10);

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

    it('keeps the unsynced value of a model field mapped via a form', async () => {
        const test = await createTest({
            comment: 'Live components',
        }, (data: any) => `
            <div ${initComponent(data)}>
                <form data-model>
                    <textarea name="comment" data-testid="comment">${data.comment}</textarea>
               </form>

               <button data-action="live#$render">Reload</button>
           </div>
       `);

        test.expectsAjaxCall()
            .serverWillChangeProps((data) => {
                data.comment = 'server tries to change comment, but it will be modified client side';
            })
            // delay slightly so we can type in the textarea
            .delayResponse(10);

        getByText(test.element, 'Reload').click();
        // mimic changing the field, but without (yet) triggering the change event
        const commentField = getByTestId(test.element, 'comment');
        if (!(commentField instanceof HTMLTextAreaElement)) {
            throw new Error('wrong type');
        }
        userEvent.type(commentField, ' ftw!');

        // wait for loading start and end
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        expect(commentField).toHaveValue('Live components ftw!');

        // refresh again, the value should now be in sync and accept the changed
        // value from the server
        test.expectsAjaxCall()
            .expectUpdatedData({ comment: 'Live components ftw!' })
            .serverWillChangeProps((data) => {
                data.comment = 'server changed comment';
            });

        getByText(test.element, 'Reload').click();
        // wait for loading start and end
        await waitFor(() => expect(test.element).toHaveAttribute('busy'));
        await waitFor(() => expect(test.element).not.toHaveAttribute('busy'));

        expect(commentField).toHaveValue('server changed comment');
    });

    it('allows model fields to be manually set as long as change event is dispatched', async () => {
        const test = await createTest({ food: '' }, (data: any) => `
            <div ${initComponent(data)}>
                <!-- specifically using on(input) then we will trigger a "change" event -->
                <select data-model="on(input)|food" data-testid="food-select">
                    <option value="">choose a food</option>
                    <option value="carrot">🥕</option>
                    <option value="brocolli">🥦</option>
                </select>

                Food: ${data.food}
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ food: 'carrot' });

        const foodSelect = getByTestId(test.element, 'food-select');
        if (!(foodSelect instanceof HTMLSelectElement)) {
            throw new Error('wrong type');
        }

        foodSelect.value = 'carrot';
        foodSelect.dispatchEvent(new Event('change', { bubbles: true }));

        await waitFor(() => expect(test.element).toHaveTextContent('Food: carrot'));
    });

    it('keeps dirty data if an initial request fails', async () => {
        const test = await createTest({ food: '', rating: 0 }, (data: any) => `
            <div ${initComponent(data)}>
                Food is: ${data.food}
                Rating is: ${data.rating}
            </div>
        `);

        test.expectsAjaxCall()
            .expectUpdatedData({ food: 'Popcorn' })
            // delay so we can set another prop
            .delayResponse(10)
            .serverWillReturnCustomResponse(500, `
                <html><head><title>Error!</title></head><body><h1>Now is a good time to panic!</h1></body></html>
            `)
        ;

        test.component.set('food', 'Popcorn');
        // start the failed request
        const promise =  test.component.render();
        test.component.set('rating', 5);
        // while the request is happening, set another model
        await promise;

        // value stays & is dirty
        expect(test.component.getData('food')).toEqual('Popcorn');
        expect(test.component.valueStore.getDirtyProps()).toEqual({ food: 'Popcorn', rating: 5 });

        test.expectsAjaxCall()
            .expectUpdatedData({ food: 'Popcorn', rating: 5 })
        ;
        await test.component.render();
        expect(test.element).toHaveTextContent('Food is: Popcorn');
        expect(test.element).toHaveTextContent('Rating is: 5');
    });
});
