/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application, Controller } from 'stimulus';
import { getByTestId, waitFor } from '@testing-library/dom';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import CoreController from '../dist/core-controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.createSourceCalled = false;
        this.element.disconnectCalled = false;

        this.element.addEventListener('turbo:pre-connect', (event) => {
            this.element.classList.add('pre-connected');

            event.detail.createSource = () => {
                this.element.createSourceCalled = true;

                return null;
            };

            event.detail.disconnect = () => {
                this.element.disconnectCalled = true;

                return null;
            };
        });

        this.element.addEventListener('turbo:connect', () => {
            this.element.classList.add('connected');
        });
    }

    disconnect() {
        this.element.classList.add('disconnected');
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('turbo', CoreController);
};

describe('TurboController', () => {
    let container;

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        container = mountDOM(`<div data-testid="turbo" data-controller="check turbo"></div>`);

        expect(getByTestId(container, 'turbo')).not.toHaveClass('pre-connected');
        expect(getByTestId(container, 'turbo')).not.toHaveClass('connected');

        startStimulus();
        await waitFor(() => {
            expect(getByTestId(container, 'turbo')).toHaveClass('pre-connected');
            expect(getByTestId(container, 'turbo')).toHaveClass('connected');
        });

        const element = getByTestId(container, 'turbo');
        expect(element.createSourceCalled).toBe(true);
        expect(element.disconnectCalled).toBe(false);

        element.parentNode.removeChild(element);
        await waitFor(() => {
            expect(element).toHaveClass('disconnected');
        });
        expect(element.disconnectCalled).toBe(true);
    });
});
