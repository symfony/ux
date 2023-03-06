/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import AutocompleteController, {
    AutocompleteConnectOptions,
    AutocompletePreConnectOptions,
} from '../src/controller';
import fetchMock from 'fetch-mock-jest';
import userEvent from '@testing-library/user-event';
import TomSelect from 'tom-select';

const shortDelay = (ms: number): Promise<void> => new Promise((resolve) => setTimeout(resolve, ms));

const startAutocompleteTest = async (html: string): Promise<{ container: HTMLElement, tomSelect: TomSelect }> => {
    const container = document.createElement('div');
    container.innerHTML = html;

    let tomSelect: TomSelect | null = null;
    container.addEventListener('autocomplete:pre-connect', () => {
        container.classList.add('pre-connected');
    });

    container.addEventListener('autocomplete:connect', (event: any) => {
        tomSelect = (event.detail as AutocompleteConnectOptions).tomSelect;
        container.classList.add('connected');
    });

    document.body.innerHTML = '';
    document.body.appendChild(container);

    await waitFor(() => {
        expect(container).toHaveClass('pre-connected');
        expect(container).toHaveClass('connected');
    });

    if (!tomSelect) {
        throw 'Missing TomSelect instance';
    }

    return { container, tomSelect };
}

describe('AutocompleteController', () => {
    beforeAll(() => {
        const application = Application.start();
        application.register('autocomplete', AutocompleteController);
    });

    afterEach(() => {
        document.body.innerHTML = '';

        if (!fetchMock.done()) {
            throw new Error('Mocked requests did not match');
        }
        fetchMock.reset();
    });

    it('connect without options', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select
                data-testid="main-element"
                data-controller="autocomplete"
            ></select>
        `);

        expect(tomSelect.input).toBe(getByTestId(container, 'main-element'));
    });

    it('connect with ajax URL on a select element', async () => {
        const { container, tomSelect} = await startAutocompleteTest(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
            ></select>
        `);

        // initial Ajax request on focus
        fetchMock.mock(
            '/path/to/autocomplete?query=',
            JSON.stringify({
                results: [
                    {
                        value: 3,
                        text: 'salad'
                    },
                ],
            }),
        );

        fetchMock.mock(
            '/path/to/autocomplete?query=foo',
            JSON.stringify({
                results: [
                    {
                        value: 1,
                        text: 'pizza'
                    },
                    {
                        value: 2,
                        text: 'popcorn'
                    },
                ],
            }),
        );

        const controlInput = tomSelect.control_input;

        // wait for the initial Ajax request to finish
        userEvent.click(controlInput);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(1);
        });

        // typing was not properly triggering, for some reason
        //userEvent.type(controlInput, 'foo');
        controlInput.value = 'foo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(2);
        });
    });

    it('connect with ajax URL on an input element', async () => {
        const { container, tomSelect} = await startAutocompleteTest(`
            <label for="the-input">Items</label>
            <input
                id="the-input"
                data-testid="main-element"
                data-controller="autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
            >
        `);

        // initial Ajax request on focus
        fetchMock.mock(
            '/path/to/autocomplete?query=',
            JSON.stringify({
                results: [
                    {
                        value: 3,
                        text: 'salad'
                    },
                ],
            }),
        );

        fetchMock.mock(
            '/path/to/autocomplete?query=foo',
            JSON.stringify({
                results: [
                    {
                        value: 1,
                        text: 'pizza'
                    },
                    {
                        value: 2,
                        text: 'popcorn'
                    },
                ],
            }),
        );

        const controlInput = tomSelect.control_input;

        // wait for the initial Ajax request to finish
        userEvent.click(controlInput);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(1);
        });

        // typing was not properly triggering, for some reason
        //userEvent.type(controlInput, 'foo');
        controlInput.value = 'foo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(2);
        });
    });

    it('limits updates when min-characters', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
                data-autocomplete-min-characters-value="3"
            ></select>
        `);

        const controlInput = tomSelect.control_input;

        controlInput.value = 'fo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(0);
        });
    });

    it('min-characters can be a falsy value', async () => {
        const { tomSelect } = await startAutocompleteTest(`
            <select
                data-testid="main-element"
                data-controller="autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
                data-autocomplete-min-characters-value="0"
            ></select>
        `);

        expect(tomSelect.settings.shouldLoad('')).toBeTruthy()
    })

    it('loads new pages on scroll', async () => {
        document.addEventListener('autocomplete:pre-connect', (event: any) => {
            const options = (event.detail as AutocompletePreConnectOptions).options;
            // make it so that as soon as we trigger a scroll, tomselect thinks
            // more need to be loaded
            options.shouldLoadMore = () => true;
        });

        const { container, tomSelect } = await startAutocompleteTest(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
            ></select>
        `);

        // initial Ajax request on focus
        fetchMock.mock(
            '/path/to/autocomplete?query=',
            JSON.stringify({
                results: [
                    {value: 1, text: 'dog1'},
                    {value: 2, text: 'dog2'},
                    {value: 3, text: 'dog3'},
                    {value: 4, text: 'dog4'},
                    {value: 5, text: 'dog5'},
                    {value: 6, text: 'dog6'},
                    {value: 7, text: 'dog7'},
                    {value: 8, text: 'dog8'},
                    {value: 9, text: 'dog9'},
                    {value: 10, text: 'dog10'},
                ],
                next_page: '/path/to/autocomplete?query=&page=2'
            }),
        );

        const controlInput = tomSelect.control_input;

        // wait for the initial Ajax request to finish
        userEvent.click(controlInput);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(11); // should be 10, but for some reason dropdown immediately shows "loading more results"
        });
        const dropdownContent = container.querySelector('.ts-dropdown-content');
        if (!dropdownContent) {
            throw new Error('cannot find dropdown content element');
        }

        fetchMock.mock(
            '/path/to/autocomplete?query=&page=2',
            JSON.stringify({
                results: [
                    {value: 11, text: 'dog11'},
                    {value: 12, text: 'dog12'},
                ],
                next_page: null,
            }),
        );

        // trigger a scroll, this will cause TomSelect to check "shouldLoadMore"
        dropdownContent.dispatchEvent(new Event('scroll'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(12);
        });
    });

    it('continues working even if options html rearranges', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <option value="1">dog1</option>
                <option value="2">dog2</option>
                <option value="3">dog3</option>
            </select>
        `);

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;

        // sanity checks
        expect(selectElement.value).toBe('');
        tomSelect.addItem('3');
        expect(selectElement.value).toBe('3');

        // wait for the MutationObserver to be able to flush
        await shortDelay(10);

        // something external mutations the elements into a different order
        selectElement.children[1].setAttribute('value', '2');
        selectElement.children[1].innerHTML = 'dog2';
        selectElement.children[2].setAttribute('value', '3');
        selectElement.children[2].innerHTML = 'dog3';
        selectElement.children[3].setAttribute('value', '1');
        selectElement.children[3].innerHTML = 'dog1';

        // wait for the MutationObserver to flush these changes
        await shortDelay(10);

        tomSelect.addItem('2');
        // due to the rearrangement, this will incorrectly be set to 3
        expect(selectElement.value).toBe('2');
    });

    it('continues working also if optgroups rearrange', async () => {
        // optgroup changing seems to work out-of-the-box, without our MutationObserver
        // test added for safety
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <optgroup label="small dogs">
                    <option value="1">dog1</option>
                    <option value="2">dog2</option>
                    <option value="3">dog3</option>
                </optgroup>
                <optgroup label="big dogs">
                    <option value="4">dog4</option>
                    <option value="5">dog5</option>
                    <option value="6">dog6</option>
                </optgroup>
            </select>
        `);

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;

        // sanity checks
        expect(selectElement.value).toBe('');
        tomSelect.addItem('2');
        expect(selectElement.value).toBe('2');

        await shortDelay(10);

        // TomSelect will move the "2" option out of its optgroup and onto the bottom
        // let's imitate an Ajax call reversing that
        const selectedOption2 = selectElement.children[3];
        if (!(selectedOption2 instanceof HTMLOptionElement)) {
            throw new Error('cannot find option 3');
        }
        const smallDogGroup = selectElement.children[1];
        if (!(smallDogGroup instanceof HTMLOptGroupElement)) {
            throw new Error('cannot find small dog group');
        }

        // add a new element, which is really just the old dog2
        const newOption2 = document.createElement('option');
        newOption2.setAttribute('value', '2');
        newOption2.innerHTML = 'dog2';
        // but the new HTML will correctly mark this as selected
        newOption2.setAttribute('selected', '');
        smallDogGroup.appendChild(newOption2);

        // remove the dog2 element from the bottom
        selectElement.removeChild(selectedOption2);

        // TomSelect will still have the correct value
        expect(tomSelect.getValue()).toEqual('2');

        // wait for the MutationObserver to be able to flush
        await shortDelay(10);

        tomSelect.addItem('4');
        expect(selectElement.value).toBe('4');
    });

    it('updates properly if options change', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <option value="1">dog1</option>
                <option value="2">dog2</option>
                <option value="3">dog3</option>
            </select>
        `);

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;

        // something external changes the set of options, including add a new one
        selectElement.children[1].setAttribute('value', '4');
        selectElement.children[1].innerHTML = 'dog4';
        selectElement.children[2].setAttribute('value', '5');
        selectElement.children[2].innerHTML = 'dog5';
        selectElement.children[3].setAttribute('value', '6');
        selectElement.children[3].innerHTML = 'dog6';
        const newOption7 = document.createElement('option');
        newOption7.setAttribute('value', '7');
        newOption7.innerHTML = 'dog7';
        selectElement.appendChild(newOption7);
        const newOption8 = document.createElement('option');
        newOption8.setAttribute('value', '8');
        newOption8.innerHTML = 'dog8';
        selectElement.appendChild(newOption8);

        // wait for the MutationObserver to flush these changes
        await shortDelay(10);

        const controlInput = tomSelect.control_input;
        userEvent.click(controlInput);
        await waitFor(() => {
            // make sure all 5 new options are there
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(5);
        });

        tomSelect.addItem('7');
        expect(selectElement.value).toBe('7');

        // remove an element, the control should update
        selectElement.removeChild(selectElement.children[1]);
        await shortDelay(10);
        userEvent.click(controlInput);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(4);
        });
    });

    it('toggles correctly between disabled and enabled', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <option value="1">dog1</option>
                <option value="2">dog2</option>
                <option value="3">dog3</option>
            </select>
        `);

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        expect(tomSelect.isDisabled).toBe(false);

        // A) enabled -> disabled
        selectElement.disabled = true;
        // wait for the MutationObserver
        await shortDelay(10);

        expect(tomSelect.isDisabled).toBe(true);
        const tsWrapper = container.querySelector('.ts-wrapper');
        if (!tsWrapper) {
            throw new Error('cannot find ts-wrapper element');
        }
        expect(tsWrapper.classList.contains('disabled')).toBe(true);

        // B) disabled -> enabled
        selectElement.disabled = false;
        // wait for the MutationObserver
        await shortDelay(10);

        expect(tomSelect.isDisabled).toBe(false);
        expect(tsWrapper.classList.contains('disabled')).toBe(false);
    });

    it('updates the placeholder when changed', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <option value="1">dog1</option>
                <option value="2">dog2</option>
                <option value="3">dog3</option>
            </select>
        `);

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        expect(tomSelect.control_input.placeholder).toBe('Select a dog');

        selectElement.children[0].innerHTML = 'Select a cat';
        // wait for the MutationObserver
        await shortDelay(10);
        expect(tomSelect.control_input.placeholder).toBe('Select a cat');

        // a different way to change the placeholder
        selectElement.children[0].childNodes[0].nodeValue = 'Select a kangaroo';
        await shortDelay(10);
        expect(tomSelect.control_input.placeholder).toBe('Select a kangaroo');
    });
});
