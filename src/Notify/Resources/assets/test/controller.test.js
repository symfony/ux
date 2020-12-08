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
import NotifyController from '../dist/controller';

// Controller used to check the actual controller was properly booted
class CheckController extends Controller {
    connect() {
        this.element.addEventListener('notify:connect', () => {
            this.element.classList.add('connected');
        });
    }
    disconnect() {
        this.element.classList.remove('connected');
    }
}

const startStimulus = () => {
    const application = Application.start();
    application.register('check', CheckController);
    application.register('notify', NotifyController);
};

/* eslint-disable no-undef */
describe('NotifyController', () => {
    let container;

    const addEventListenerMock = jest.fn();
    const removeEventListenerMock = jest.fn();
    const closeMock = jest.fn();

    global.EventSource = jest.fn().mockImplementation(() => {
        return {
            addEventListener: addEventListenerMock,
            removeEventListener: removeEventListenerMock,
            close: closeMock,
        };
    });

    global.Notification = {};

    beforeEach(() => {
        container = mountDOM(`
            <div
                data-testid="notify"
                data-controller="check notify"
                data-notify-topics-value="[&quot;https://symfony.com/notifier&quot;]"
                data-notify-hub-value="http://localhost:9090/.well-known/mercure"
            ></div>
        `);
    });

    afterEach(() => {
        clearDOM();
        jest.clearAllMocks();
    });

    it('create and destroy event sources', async () => {
        startStimulus();
        await waitFor(() => expect(getByTestId(container, 'notify')).toHaveClass('connected'));

        expect(global.EventSource).toBeCalledTimes(1);
        expect(global.EventSource).toBeCalledWith(
            'http://localhost:9090/.well-known/mercure?topic=https%3A%2F%2Fsymfony.com%2Fnotifier'
        );
        expect(addEventListenerMock).toBeCalledTimes(1);

        clearDOM();
        await waitFor(() => expect(getByTestId(container, 'notify')).not.toHaveClass('connected'));

        expect(removeEventListenerMock).toBeCalledTimes(1);
        expect(closeMock).toBeCalledTimes(1);
    });
});
