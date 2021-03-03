/*
 * This file is part of the Symfony package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Application } from 'stimulus';
import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import TurboStreamController from '../src/turbo_stream_controller.js';

const startStimulus = () => {
    const application = Application.start();
    application.register('turbo-stream', TurboStreamController);
};

describe('TurboStreamController', () => {
    let container;

    beforeEach(() => {
        global.EventSource = jest.fn(() => ({
            addEventListener: jest.fn(),
            removeEventListener: jest.fn(),
            close: jest.fn(),
        }));

        container = mountDOM(
            '<div data-controller="turbo-stream" data-turbo-stream-hub-value="https://example.com/.well-known/mercure" data-turbo-stream-topic-value="foo"></div>'
        );
    });

    afterEach(() => {
        expect(global.EventSource.mock.calls.length).toBe(1);
        expect(global.EventSource.mock.calls[0][0]).toBe('https://example.com/.well-known/mercure?topic=foo');
        clearDOM();
    });

    it('connects', async () => {
        startStimulus();
    });
});
