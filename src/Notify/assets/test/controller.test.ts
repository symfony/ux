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
import NotifyController from '../src/controller';
import { vi } from 'vitest';

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

const startStimulus = (): Application => {
    const application = Application.start();

    application.register('check', CheckController);
    application.register('notify', NotifyController);

    return application;
};

describe('NotifyController', () => {
    let application;

    afterEach(() => {
        clearDOM();
        application.stop();
        vi.clearAllMocks();
    });

    const addEventListenerMock = vi.fn();
    const removeEventListenerMock = vi.fn();
    const closeMock = vi.fn();

    global.EventSource = vi.fn().mockImplementation(() => {
        return {
            addEventListener: addEventListenerMock,
            removeEventListener: removeEventListenerMock,
            close: closeMock,
        };
    });

    global.Notification = {};

    it('create and destroy event sources', async () => {
        const container = mountDOM(`
            <div
                data-testid="notify"
                data-controller="check notify"
                data-notify-topics-value="&#x5B;&quot;https&#x3A;&#x5C;&#x2F;&#x5C;&#x2F;symfony.com&#x5C;&#x2F;notifier&quot;&#x5D;"
                data-notify-hub-value="http&#x3A;&#x2F;&#x2F;localhost&#x3A;9090&#x2F;.well-known&#x2F;mercure"
            ></div>
        `);

        expect(getByTestId(container, 'notify')).not.toHaveClass('connected');

        application = startStimulus();

        await waitFor(() => expect(getByTestId(container, 'notify')).toHaveClass('connected'));

        const u = new URL('http://localhost:9090/.well-known/mercure');
        u.searchParams.append('topic', 'https://symfony.com/notifier');

        expect(global.EventSource).toBeCalledTimes(1);
        expect(global.EventSource).toBeCalledWith(u);
        expect(addEventListenerMock).toBeCalledTimes(1);

        clearDOM();

        await waitFor(() => expect(getByTestId(container, 'notify')).not.toHaveClass('connected'));

        expect(removeEventListenerMock).toBeCalledTimes(1);
        expect(closeMock).toBeCalledTimes(1);
    });
});
