/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import {createTest, initComponent, shutdownTest, startStimulus} from '../tools';
import { htmlToElement } from '../../src/dom_utils';
import Component from "../../src/Component";

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

    it('creates the Component object', async () => {
        const test = await createTest({ firstName: 'Ryan' }, (data: any) => `
            <div ${initComponent(data, { debounce: 115, id: 'the-id', fingerprint: 'the-fingerprint' })}></div>
        `);

        expect(test.controller.component).toBeInstanceOf(Component);
        expect(test.controller.component.defaultDebounce).toEqual(115);
        expect(test.controller.component.id).toEqual('the-id');
        expect(test.controller.component.fingerprint).toEqual('the-fingerprint');
    });
});
