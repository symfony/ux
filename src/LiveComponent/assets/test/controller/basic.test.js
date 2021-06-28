/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { clearDOM, mountDOM } from '@symfony/stimulus-testing';
import { startStimulus } from '../tools';

describe('LiveController Basic Tests', () => {
    afterEach(() => {
        clearDOM();
    });

    it('dispatches connect event', async () => {
        const container = mountDOM('');

        let eventTriggered = false;
        container.addEventListener('live:connect', () => {
            eventTriggered = true;
        })
        const { element } = await startStimulus(
            '<div data-controller="live"></div>',
            container
        );

        // smoke test
        expect(element).toHaveAttribute('data-controller', 'live');
        expect(eventTriggered).toStrictEqual(true);
    });
});
