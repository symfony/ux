/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import {createTest, initComponent, shutdownTests, startStimulus} from '../tools';
import { htmlToElement } from '../../src/dom_utils';
import Component from '../../src/Component';
import LiveControllerDefault, { getComponent } from '../../src/live_controller';

describe('LiveController Basic Tests', () => {
    afterEach(() => {
        shutdownTests()
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

        expect(test.component).toBeInstanceOf(Component);
        expect(test.component.defaultDebounce).toEqual(115);
        expect(test.component.id).toEqual('the-id');
        expect(test.component.fingerprint).toEqual('the-fingerprint');
        await expect(getComponent(test.element)).resolves.toBe(test.component);
        expect(LiveControllerDefault.componentRegistry.findComponents(test.component, false, null)[0]).toBe(test.component);

        // check that it disconnects
        document.body.innerHTML = '';
        await expect(getComponent(test.element)).rejects.toThrow('Component not found for element');
        expect(LiveControllerDefault.componentRegistry.findComponents(test.component, false, null)).toEqual([]);
    });
});
