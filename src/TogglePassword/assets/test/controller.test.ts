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
import { getByTestId, waitFor, getByText } from '@testing-library/dom';
import user from '@testing-library/user-event';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import TogglePasswordController from '../src/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('toggle-password:connect', () => {
            this.element.classList.add('connected');
        });
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('toggle-password', TogglePasswordController);
}

describe('TogglePasswordController', () => {
    let container;

    beforeEach(() => {
        container = mountDOM(`
        <div class="toggle-password-container"> 
                <input type="password"
                data-testid="input"
                data-controller="check toggle-password"
                data-toggle-password-hidden-label-value="Hide"
                data-toggle-password-visible-label-value="Show" />
            </div>
        `);
        startStimulus();
    });

    afterEach(() => {
        clearDOM();
    });

    it('should toggle the input type', async () => {
        const input = getByTestId(container, 'input');
        const button = getByText(container, 'Show');

        expect(input.type).toBe('password');

        user.click(button);

        await waitFor(() => {
            expect(input.type).toBe('text');
        });

        user.click(button);

        await waitFor(() => {
            expect(input.type).toBe('password');
        });
    });
});
