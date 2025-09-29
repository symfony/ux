/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from '@hotwired/stimulus';
import { getByTestId } from '@testing-library/dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { clearDOM, mountDOM } from '../../../../../test/stimulus-helpers';
import TurboStreamController from '../../src/turbo_stream_controller';

const startStimulus = () => {
    const application = Application.start();
    application.register('symfony--ux-turbo--mercure-turbo-stream', TurboStreamController);
};

describe('TurboStreamController', () => {
    let container: HTMLElement;

    beforeEach(() => {
        global.EventSource = vi.fn(() => ({
            addEventListener: vi.fn(),
            removeEventListener: vi.fn(),
            close: vi.fn(),
        }));

        container = mountDOM(
            '<div data-testid="turbo-stream-mercure" data-controller="symfony--ux-turbo--mercure-turbo-stream" data-symfony--ux-turbo--mercure-turbo-stream-hub-value="https://example.com/.well-known/mercure" data-symfony--ux-turbo--mercure-turbo-stream-topic-value="foo"></div>'
        );
    });

    afterEach(() => {
        clearDOM();
    });

    it('connects', async () => {
        startStimulus();

        // smoke test
        expect(getByTestId(container, 'turbo-stream-mercure')).toHaveAttribute(
            'data-symfony--ux-turbo--mercure-turbo-stream-topic-value',
            'foo'
        );
    });
});
