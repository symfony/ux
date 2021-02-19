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
import MercureController from '../../dist/adapters/mercure-controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        let factory = { createSource: null };
        this._dispatchEvent('turbo:pre-connect', factory);
        this.element.createSource = factory.createSource;

        this.element.classList.add('connected');
    }

    _dispatchEvent(name, payload = null, canBubble = false, cancelable = false) {
        const userEvent = document.createEvent('CustomEvent');
        userEvent.initCustomEvent(name, canBubble, cancelable, payload);

        this.element.dispatchEvent(userEvent);
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('mercure', MercureController);
};

describe('MercureController', () => {
    let container;

    afterEach(() => {
        clearDOM();
    });

    it('connect', async () => {
        container = mountDOM(`
            <div data-testid="mercure"
                 data-controller="mercure check" 
                 data-mercure-hub-value="http://localhost/.well-known/mercure"
                 data-mercure-topic-value="atopic"
            ></div>
        `);

        startStimulus();
        await waitFor(() => {
            expect(getByTestId(container, 'mercure')).toHaveClass('connected');
        });

        const createSource = getByTestId(container, 'mercure').createSource;
        expect(typeof createSource).toBe('function');

        const eventSource = createSource();
        expect(eventSource.url + '').toEqual('http://localhost/.well-known/mercure?topic=atopic');
    });
});
