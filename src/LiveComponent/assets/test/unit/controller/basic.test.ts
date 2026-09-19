/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { afterEach, describe, expect, it } from 'vitest';
import Component from '../../../src/Component';
import { findComponents } from '../../../src/ComponentRegistry';
import { htmlToElement } from '../../../src/dom_utils';
import { getComponent } from '../../../src/live_controller';
import { createTest, getStimulusApplication, initComponent, shutdownTests, startStimulus } from '../../tools';

describe('LiveController Basic Tests', () => {
    afterEach(() => {
        shutdownTests();
    });

    it('dispatches connect event', async () => {
        const container = htmlToElement('<div><div data-controller="live"></div></div>');

        let eventTriggered = false;
        container.addEventListener('live:connect', () => {
            eventTriggered = true;
        });
        const { element } = await startStimulus(container);

        // smoke test
        expect(element).toHaveAttribute('data-controller', 'live');
        expect(eventTriggered).toStrictEqual(true);
    });

    it('creates the Component object', async () => {
        const test = await createTest(
            { firstName: 'Ryan' },
            (data: any) => `
            <div ${initComponent(data, { debounce: 115, id: 'the-id', fingerprint: 'the-fingerprint' })}></div>
        `
        );

        expect(test.component).toBeInstanceOf(Component);
        expect(test.component.defaultDebounce).toEqual(115);
        expect(test.component.id).toEqual('the-id');
        await expect(getComponent(test.element)).resolves.toBe(test.component);
        expect(findComponents(test.component, false, null)[0]).toBe(test.component);

        // check that it disconnects
        document.body.innerHTML = '';
        await expect(getComponent(test.element)).rejects.toThrow('Component not found for element');
        expect(findComponents(test.component, false, null)).toEqual([]);
    });

    it('rebuilds the Component on reconnect when props changed in between', async () => {
        const test = await createTest({ greeting: 'aloha' }, (data: any) => `<div ${initComponent(data)}></div>`);

        const controller = getStimulusApplication().getControllerForElementAndIdentifier(test.element, 'live') as any;
        const originalComponent = controller.component;
        expect(originalComponent.valueStore.getOriginalProps()).toEqual({ greeting: 'aloha' });

        // simulate a parent morph: same controller instance, fresh props from the server
        controller.disconnect();
        controller.propsValue = { greeting: 'hello' };
        controller.connect();

        expect(controller.component).not.toBe(originalComponent);
        expect(controller.component.valueStore.getOriginalProps()).toEqual({ greeting: 'hello' });
    });

    it('keeps the existing Component on reconnect when props are unchanged', async () => {
        const test = await createTest({ greeting: 'aloha' }, (data: any) => `<div ${initComponent(data)}></div>`);

        const controller = getStimulusApplication().getControllerForElementAndIdentifier(test.element, 'live') as any;
        const originalComponent = controller.component;

        controller.disconnect();
        controller.connect();

        expect(controller.component).toBe(originalComponent);
    });
});
