/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from '@hotwired/stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import AutocompleteController from '../src/controller';
import fetchMock from 'fetch-mock-jest';
import userEvent from '@testing-library/user-event';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('autocomplete:pre-connect', () => {
            this.element.classList.add('pre-connected');
        });

        this.element.addEventListener('autocomplete:connect', (event: any) => {
            this.element.classList.add('connected');
            this.element.tomSelect = event.detail.tomSelect;
        });
    }
}

const startStimulus = (): Application => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('autocomplete', AutocompleteController);

    return application;
};

describe('AutocompleteController', () => {
    let application: Application;

    afterEach(() => {
        clearDOM();
        application.stop();

        if (!fetchMock.done()) {
            throw new Error('Mocked requests did not match');
        }
        fetchMock.reset();
    });

    it('connect without options', async () => {
        const container = mountDOM(`
            <select
                data-testid="main-element"
                data-controller="check autocomplete"
            ></select>
        `);

        expect(getByTestId(container, 'main-element')).not.toHaveClass('pre-connected');
        expect(getByTestId(container, 'main-element')).not.toHaveClass('connected');

        application = startStimulus();

        await waitFor(() => {
            expect(getByTestId(container, 'main-element')).toHaveClass('pre-connected');
            expect(getByTestId(container, 'main-element')).toHaveClass('connected');
        });

        const tomSelect = getByTestId(container, 'main-element').tomSelect;
        expect(tomSelect.input).toBe(getByTestId(container, 'main-element'));
    });

    it('connect with ajax URL', async () => {
        const container = mountDOM(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="check autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
            ></select>
        `);

        application = startStimulus();

        await waitFor(() => {
            expect(getByTestId(container, 'main-element')).toHaveClass('connected');
        });

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

        const tomSelect = getByTestId(container, 'main-element').tomSelect;
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
        const container = mountDOM(`
            <label for="the-select">Items</label>
            <select
                id="the-select"
                data-testid="main-element"
                data-controller="check autocomplete"
                data-autocomplete-url-value="/path/to/autocomplete"
                data-autocomplete-min-characters-value="3"
            ></select>
        `);

        application = startStimulus();

        await waitFor(() => {
            expect(getByTestId(container, 'main-element')).toHaveClass('connected');
        });

        const tomSelect = getByTestId(container, 'main-element').tomSelect;
        const controlInput = tomSelect.control_input;

        controlInput.value = 'fo';
        controlInput.dispatchEvent(new Event('input'));

        await waitFor(() => {
            expect(container.querySelectorAll('.option[data-selectable]')).toHaveLength(0);
        });
    });

    it('adds live-component support', async () => {
        const container = mountDOM(`
            <div>
                <label for="the-select" data-testid="main-element-label">Select something</label>
                <select
                    id="the-select"
                    data-testid="main-element"
                    data-controller="check autocomplete"
                ></select>
            </div>
        `);

        application = startStimulus();

        await waitFor(() => {
            expect(getByTestId(container, 'main-element')).toHaveClass('connected');
        });

        expect(getByTestId(container, 'main-element')).toHaveAttribute('data-live-ignore');
        expect(getByTestId(container, 'main-element-label')).toHaveAttribute('data-live-ignore');
        const tsDropdown = container.querySelector('.ts-wrapper');

        await waitFor(() => {
            expect(tsDropdown).not.toBeNull();
        });
        expect(tsDropdown).toHaveAttribute('data-live-ignore');
    });
});
