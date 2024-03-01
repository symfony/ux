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
import userEvent from '@testing-library/user-event';
import TomSelect from 'tom-select';
import createFetchMock from 'vitest-fetch-mock';
import { vi } from 'vitest';

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

const fetchMocker = createFetchMock(vi);
describe('AutocompleteController', () => {
    beforeAll(() => {
        const application = Application.start();
        application.register('autocomplete', AutocompleteController);

        fetchMocker.enableMocks();
    });

    beforeEach(() => {
        fetchMocker.resetMocks();
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('connect without options', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select
                data-testid="main-element"
                data-controller="autocomplete"
            ></select>
        `);

        expect(tomSelect.input).toBe(getByTestId(container, 'main-element'));
        expect(fetchMock.requests().length).toEqual(0);
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
        fetchMock.mockResponseOnce(
            JSON.stringify({
                results: [
                    {
                        value: 3,
                        text: 'salad'
                    },
                ]
            }),
        );

        fetchMock.mockResponseOnce(
            JSON.stringify({
                results: [
                    {
                        value: 1,
                        text: 'pizza'
                    },
                    {
                        value: 2,
                        text: 'popcorn'
                    }
                ]
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

        expect(fetchMock.requests().length).toEqual(2);
        expect(fetchMock.requests()[0].url).toEqual('/path/to/autocomplete?query=');
        expect(fetchMock.requests()[1].url).toEqual('/path/to/autocomplete?query=foo');
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
        fetchMock.mockResponseOnce(
            JSON.stringify({
                results: [
                    {
                        value: 3,
                        text: 'salad'
                    },
                ],
            }),
        );

        fetchMock.mockResponseOnce(
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

        expect(fetchMock.requests().length).toEqual(2);
        expect(fetchMock.requests()[0].url).toEqual('/path/to/autocomplete?query=');
        expect(fetchMock.requests()[1].url).toEqual('/path/to/autocomplete?query=foo');
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

        expect(fetchMock.requests().length).toEqual(0);
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

    it('default min-characters will always load after first load', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
            ></select>
        `);

        const controlInput = tomSelect.control_input;

        // ajax call from initial focus
        fetchMock.mockResponseOnce(
            JSON.stringify({
                results: [
                    {
                        value: 1,
                        text: 'pizza'
                    },
                ],
            }),
        );
        // wait for the initial Ajax request to finish
        userEvent.click(controlInput);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(1);
        });

        // min length will default to 3, so this is too short
        controlInput.value = 'fo';
        controlInput.dispatchEvent(new Event('input'));
        // should still have just 1 option
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(1);
        });

        // now trigger a load
        fetchMock.mockResponseOnce(
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
        controlInput.value = 'foo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(2);
        });

        // now go below the min characters, but it should still load
        fetchMock.mockResponseOnce(
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
                    {
                        value: 3,
                        text: 'apples'
                    },
                ],
            }),
        );
        controlInput.value = 'fo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(3);
        });

        expect(fetchMock.requests().length).toEqual(3);
        expect(fetchMock.requests()[0].url).toEqual('/path/to/autocomplete?query=');
        expect(fetchMock.requests()[1].url).toEqual('/path/to/autocomplete?query=foo');
        expect(fetchMock.requests()[2].url).toEqual('/path/to/autocomplete?query=fo');
    });

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
        fetchMock.mockResponseOnce(
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

        fetchMock.mockResponseOnce(
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

        expect(fetchMock.requests().length).toEqual(2);
        expect(fetchMock.requests()[0].url).toEqual('/path/to/autocomplete?query=');
        expect(fetchMock.requests()[1].url).toEqual('/path/to/autocomplete?query=&page=2');
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

        // something external sets new HTML, with a different order
        selectElement.innerHTML = `
            <option value="">Select a dog</option>
            <option value="2">dog2</option>
            <option value="3">dog3</option>
            <option value="1">dog1</option>
        `;

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
        // let's imitate an Ajax call reversing that order
        selectElement.innerHTML = `
            <option value="">Select a dog</option>
            <optgroup label="big dogs">
                <option value="4">dog4</option>
                <option value="5">dog5</option>
                <option value="6">dog6</option>
            </optgroup>
            <optgroup label="small dogs">
                <option value="1">dog1</option>
                <option value="2" selected>dog2</option>
                <option value="3">dog3</option>
            </optgroup>
        `;

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

        // select 3 to start
        tomSelect.addItem('3');
        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        expect(selectElement.value).toBe('3');

        // something external changes the set of options, including add a new one
        selectElement.innerHTML = `
            <option value="">Select a dog</option>
            <option value="4">dog4</option>
            <option value="5">dog5</option>
            <option value="6">dog6</option>
            <option value="7">dog7</option>
            <option value="8">dog8</option>
        `;

        let newTomSelect: TomSelect|null = null;
        container.addEventListener('autocomplete:connect', (event: any) => {
            newTomSelect = (event.detail as AutocompleteConnectOptions).tomSelect;
        });

        // wait for the MutationObserver to flush these changes
        await shortDelay(10);

        // the previously selected option is no longer there
        expect(selectElement.value).toBe('');
        userEvent.click(container.querySelector('.ts-control') as HTMLElement);
        await waitFor(() => {
            // make sure all 5 new options are there
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(5);
        });

        if (null === newTomSelect) {
            throw new Error('Missing TomSelect instance');
        }
        // @ts-ignore
        newTomSelect.addItem('7');
        expect(selectElement.value).toBe('7');

        // remove an element, the control should update
        selectElement.removeChild(selectElement.children[1]);
        await shortDelay(10);
        userEvent.click(container.querySelector('.ts-control') as HTMLElement);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(4);
        });

        // change again, but the selected value is still there
        selectElement.innerHTML = `
            <option value="">Select a dog</option>
            <option value="1">dog4</option>
            <option value="2">dog5</option>
            <option value="3">dog6</option>
            <option value="7">dog7</option>
        `;
        await shortDelay(10);
        expect(selectElement.value).toBe('7');
    });

    it('updates properly if options on a multiple select change', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select multiple data-testid='main-element' data-controller='autocomplete'>
                <option value=''>Select dogs</option>
                <option value='1'>dog1</option>
                <option value='2'>dog2</option>
                <option value='3'>dog3</option>
            </select>
        `);

        tomSelect.addItem('3');
        tomSelect.addItem('2');
        const getSelectedValues = () => {
            return Array.from(selectElement.selectedOptions).map((option) => option.value).sort();
        }
        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        expect(getSelectedValues()).toEqual(['2', '3']);

        // something external changes the set of options, including add new ones
        selectElement.innerHTML = `
            <option value=''>Select a dog</option>
            <option value='2'>dog2</option>
            <option value='4'>dog4</option>
            <option value='5'>dog5</option>
            <option value='6'>dog6</option>
            <option value='7'>dog7</option>
        `;

        // wait for the MutationObserver to flush these changes
        await shortDelay(10);

        // only the "2" option from before is still there
        expect(getSelectedValues()).toEqual(['2']);
        userEvent.click(container.querySelector('.ts-control') as HTMLElement);
        await waitFor(() => {
            // make sure that, out of the 5 total options, 4 are still selectable
            // (the "2" option is not selectable because it's already selected)
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

        let newTomSelect: TomSelect|null = null;
        container.addEventListener('autocomplete:connect', (event: any) => {
            newTomSelect = (event.detail as AutocompleteConnectOptions).tomSelect;
        });

        selectElement.innerHTML = `
            <option value="">Select a cat</option>
            <option value="1">dog1</option>
            <option value="2">dog2</option>
            <option value="3">dog3</option>
        `;

        // wait for the MutationObserver
        await shortDelay(10);
        if (null === newTomSelect) {
            throw new Error('Missing TomSelect instance');
        }
        // @ts-ignore
        expect(newTomSelect.control_input.placeholder).toBe('Select a cat');
    });

    it('group related options', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="check autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
            ></select>
        `);

        // initial Ajax request on focus with group_by options
        fetchMock.mockResponseOnce(
            JSON.stringify({
                results: {
                    options: [
                        {
                            group_by: ['Meat'],
                            value: 1,
                            text: 'Beef'
                        },
                        {
                            group_by: ['Meat'],
                            value: 2,
                            text: 'Mutton'
                        },
                        {
                            group_by: ['starchy'],
                            value: 3,
                            text: 'Potatoes'
                        },
                        {
                            group_by: ['starchy', 'Meat'],
                            value: 4,
                            text: 'chili con carne'
                        },
                    ],
                    optgroups: [
                        {
                            value: 'Meat',
                            label: 'Meat'
                        },
                        {
                            value: 'starchy',
                            label: 'starchy'
                        },
                    ]
                },
            }),
        );

        fetchMock.mockResponseOnce(
            JSON.stringify({
                results: {
                    options: [
                        {
                            group_by: ['Meat'],
                            value: 1,
                            text: 'Beef'
                        },
                        {
                            group_by: ['Meat'],
                            value: 2,
                            text: 'Mutton'
                        },
                    ],
                    optgroups: [
                        {
                            value: 'Meat',
                            label: 'Meat'
                        },
                    ]
                }
            }),
        );

        const controlInput = tomSelect.control_input;

        // wait for the initial Ajax request to finish
        userEvent.click(controlInput);
        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(5);
            expect(container.querySelectorAll('.optgroup-header')).toHaveLength(2);
        });

        // typing was not properly triggering, for some reason
        //userEvent.type(controlInput, 'foo');
        controlInput.value = 'foo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(2);
            expect(container.querySelectorAll('.optgroup-header')).toHaveLength(1);
        });

        expect(fetchMock.requests().length).toEqual(2);
        expect(fetchMock.requests()[0].url).toEqual('/path/to/autocomplete?query=');
        expect(fetchMock.requests()[1].url).toEqual('/path/to/autocomplete?query=foo');
    });

    it('preserves the selected value and HTML with disconnect on single select', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <option value="1">dog1</option>
                <option value="2">dog2</option>
                <option value="3">dog3</option>
            </select>
        `);

        tomSelect.addItem('2');

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        // trigger the disconnect
        selectElement.removeAttribute('data-controller');
        await waitFor(() => {
            expect(selectElement.className).not.toContain('tomselected');
        });
        expect(selectElement.value).toBe('2');
    });

    it('preserves the selected value and HTML with disconnect on multiple select', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select multiple data-testid="main-element" data-controller="autocomplete">
                <option value="">Select a dog</option>
                <option value="1">dog1</option>
                <option value="2">dog2</option>
                <option value="3">dog3</option>
            </select>
        `);

        tomSelect.addItem('2');
        tomSelect.addItem('3');

        const getSelectedValues = () => {
            return Array.from(selectElement.selectedOptions).map((option) => option.value).sort();
        }

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        expect(getSelectedValues()).toEqual(['2', '3']);

        // trigger the disconnect
        selectElement.removeAttribute('data-controller');
        await waitFor(() => {
            expect(selectElement.className).not.toContain('tomselected');
        });
        expect(getSelectedValues()).toEqual(['2', '3']);
    });

    it('does not trigger a reset when the style of "multiple" attribute changes', async () => {
        const { container } = await startAutocompleteTest(`
            <select multiple data-testid='main-element' data-controller='autocomplete'>
                <option value=''>Select dogs</option>
                <option value='1'>dog1</option>
                <option value='2'>dog2</option>
                <option value='3'>dog3</option>
            </select>
        `);

        let wasReset = false;
        container.addEventListener('autocomplete:before-reset', () => {
            wasReset = true;
        });

        const selectElement = getByTestId(container, 'main-element') as HTMLSelectElement;
        selectElement.setAttribute('multiple', 'multiple');
        // wait for the mutation observe
        await shortDelay(10);
        expect(wasReset).toBe(false);
    });

    it('does not trigger a reset based on the extra, empty select', async () => {
        const { container, tomSelect } = await startAutocompleteTest(`
            <select data-testid='main-element' data-controller='autocomplete'>
                <option value='1'>dog1</option>
                <option value='2'>dog2</option>
                <option value='3'>dog3</option>
            </select>
        `);

        let wasReset = false;
        container.addEventListener('autocomplete:before-reset', () => {
            wasReset = true;
        });

        tomSelect.addItem('2');
        // wait for the mutation observe
        await shortDelay(10);
        expect(wasReset).toBe(false);
    });
});
