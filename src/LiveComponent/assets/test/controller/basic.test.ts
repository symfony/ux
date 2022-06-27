/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { shutdownTest, startStimulus } from '../tools';
import { htmlToElement } from '../../src/dom_utils';

describe('LiveController Basic Tests', () => {
    afterEach(() => {
        shutdownTest()
    });

    it('dispatches connect event', async () => {
        const container = htmlToElement('<div><div data-controller="live"></div></div>');

        let eventTriggered = false;
        container.addEventListener('live:connect', () => {
            eventTriggered = true;
        })
        const { element } = await startStimulus(container);

        // smoke test
        expect(element).toHaveAttribute('data-controller', 'live');
        expect(eventTriggered).toStrictEqual(true);
    });
});
