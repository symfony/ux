/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { waitFor } from '@testing-library/dom';
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

    it('rebuilds the Component when its element reconnects with new props', async () => {
        const test = await createTest({ greeting: 'aloha' }, (data: any) => `<div ${initComponent(data)}></div>`);
        const controller = findLiveController(test.element);
        const originalComponent = test.component;

        await reconnectLiveController(test.element, { greeting: 'hello' });
        const reconnectedController = findLiveController(test.element);

        expect(reconnectedController).toBe(controller);
        expect(reconnectedController.component).not.toBe(originalComponent);
        expect(reconnectedController.component.valueStore.getOriginalProps()).toEqual({ greeting: 'hello' });
    });

    it('keeps the Component when its element reconnects with the same props', async () => {
        const test = await createTest({ greeting: 'aloha' }, (data: any) => `<div ${initComponent(data)}></div>`);
        const originalComponent = test.component;

        await reconnectLiveController(test.element, { greeting: 'aloha' });

        expect(findLiveController(test.element).component).toBe(originalComponent);
    });
});

function findLiveController(element: HTMLElement): any {
    return getStimulusApplication().getControllerForElementAndIdentifier(element, 'live');
}

async function reconnectLiveController(element: HTMLElement, props: object): Promise<void> {
    element.removeAttribute('data-controller');
    await waitFor(() => expect(findLiveController(element)).toBeNull());

    element.setAttribute('data-live-props-value', JSON.stringify(props));
    element.setAttribute('data-controller', 'live');
    await waitFor(() => expect(findLiveController(element)).not.toBeNull());
}
