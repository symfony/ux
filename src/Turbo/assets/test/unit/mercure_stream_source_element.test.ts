/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as Turbo from '@hotwired/turbo';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import '../../src/mercure_stream_source_element';

class FakeEventSource extends EventTarget {
    static readonly CONNECTING = 0;
    static readonly OPEN = 1;
    static readonly CLOSED = 2;
    static instances: FakeEventSource[] = [];

    readyState = FakeEventSource.CONNECTING;

    constructor(
        readonly url: string,
        readonly init?: EventSourceInit
    ) {
        super();
        FakeEventSource.instances.push(this);
    }

    open() {
        this.readyState = FakeEventSource.OPEN;
        this.dispatchEvent(new Event('open'));
    }

    fail() {
        this.readyState = FakeEventSource.CONNECTING;
        this.dispatchEvent(new Event('error'));
    }

    close() {
        this.readyState = FakeEventSource.CLOSED;
    }
}

const html =
    '<turbo-mercure-stream-source src="https://example.com/.well-known/mercure?topic=books"></turbo-mercure-stream-source>';

describe('TurboMercureStreamSourceElement', () => {
    let element: HTMLElement;

    beforeEach(() => {
        FakeEventSource.instances = [];
        global.EventSource = FakeEventSource as unknown as typeof EventSource;
        document.body.innerHTML = html;
        element = document.querySelector('turbo-mercure-stream-source') as HTMLElement;
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('is connected while its EventSource is open', () => {
        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].url).toBe('https://example.com/.well-known/mercure?topic=books');
        expect(element).not.toHaveAttribute('connected');

        FakeEventSource.instances[0].open();
        expect(element).toHaveAttribute('connected');

        FakeEventSource.instances[0].fail();
        expect(element).not.toHaveAttribute('connected');
    });

    it('reconnects when its src changes', () => {
        FakeEventSource.instances[0].open();

        element.setAttribute('src', 'https://example.com/.well-known/mercure?topic=authors');

        expect(FakeEventSource.instances).toHaveLength(2);
        expect(FakeEventSource.instances[0].readyState).toBe(FakeEventSource.CLOSED);
        expect(FakeEventSource.instances[1].url).toBe('https://example.com/.well-known/mercure?topic=authors');
        expect(element).not.toHaveAttribute('connected');
    });

    it('stays connected when the page refreshes with morphing', ({ skip }) => {
        // Turbo 7 doesn't morph
        if (!('morphElements' in Turbo)) {
            skip();
        }
        FakeEventSource.instances[0].open();

        const template = document.createElement('template');
        template.innerHTML = html;
        Turbo.morphElements(element, template.content.firstElementChild as Element);

        expect(element).toHaveAttribute('connected');
        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].readyState).toBe(FakeEventSource.OPEN);
    });
});
