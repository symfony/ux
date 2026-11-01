/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import * as Turbo from '@hotwired/turbo';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
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

    it('closes its EventSource when it is removed from the page', async () => {
        FakeEventSource.instances[0].open();

        element.remove();
        await new Promise((resolve) => setTimeout(resolve));

        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].readyState).toBe(FakeEventSource.CLOSED);
        expect(element).not.toHaveAttribute('connected');
    });

    it('stops delivering messages to the page as soon as it is removed', async () => {
        document.body.insertAdjacentHTML('beforeend', '<ul id="books"></ul>');
        const books = document.getElementById('books') as HTMLElement;
        const message = (title: string) =>
            new MessageEvent('message', {
                data: `<turbo-stream action="append" target="books"><template><li>${title}</li></template></turbo-stream>`,
            });
        FakeEventSource.instances[0].open();

        FakeEventSource.instances[0].dispatchEvent(message('Dune'));
        await vi.waitFor(() => expect(books).toHaveTextContent('Dune'));

        element.remove();
        FakeEventSource.instances[0].dispatchEvent(message('Hyperion'));
        await new Promise((resolve) => setTimeout(resolve, 50));

        expect(books).not.toHaveTextContent('Hyperion');
    });

    it('opens a new EventSource when it is inserted back after it was closed', async () => {
        FakeEventSource.instances[0].open();

        element.remove();
        await new Promise((resolve) => setTimeout(resolve));
        document.body.append(element);

        expect(FakeEventSource.instances).toHaveLength(2);
        expect(FakeEventSource.instances[0].readyState).toBe(FakeEventSource.CLOSED);
        expect(FakeEventSource.instances[1].url).toBe('https://example.com/.well-known/mercure?topic=books');
    });

    it('does not open an EventSource for its new src while it is out of the page', () => {
        FakeEventSource.instances[0].open();

        element.remove();
        element.setAttribute('src', 'https://example.com/.well-known/mercure?topic=authors');

        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].readyState).toBe(FakeEventSource.CLOSED);

        document.body.append(element);

        expect(FakeEventSource.instances).toHaveLength(2);
        expect(FakeEventSource.instances[1].url).toBe('https://example.com/.well-known/mercure?topic=authors');
    });

    it('keeps its EventSource when Turbo carries a permanent element containing it to the next page', async () => {
        const permanentHtml =
            '<div id="books-stream" data-turbo-permanent><turbo-mercure-stream-source src="https://example.com/.well-known/mercure?topic=books"></turbo-mercure-stream-source></div>';
        document.body.innerHTML = permanentHtml;
        element = document.querySelector('turbo-mercure-stream-source') as HTMLElement;
        FakeEventSource.instances[1].open();

        await Turbo.session.view.renderPage(
            Turbo.PageSnapshot.fromHTMLString(`<html><head></head><body>${permanentHtml}<p>Next page</p></body></html>`)
        );
        await new Promise((resolve) => setTimeout(resolve));

        expect(document.body).toHaveTextContent('Next page');
        expect(document.querySelector('turbo-mercure-stream-source')).toBe(element);
        expect(FakeEventSource.instances).toHaveLength(2);
        expect(FakeEventSource.instances[1].readyState).toBe(FakeEventSource.OPEN);
        expect(element).toHaveAttribute('connected');
    });

    it('keeps its EventSource when Turbo carries it to the next page as a permanent element', async () => {
        const permanentHtml =
            '<turbo-mercure-stream-source id="books-stream" data-turbo-permanent src="https://example.com/.well-known/mercure?topic=books"></turbo-mercure-stream-source>';
        document.body.innerHTML = permanentHtml;
        element = document.getElementById('books-stream') as HTMLElement;
        FakeEventSource.instances[1].open();

        await Turbo.session.view.renderPage(
            Turbo.PageSnapshot.fromHTMLString(`<html><head></head><body>${permanentHtml}<p>Next page</p></body></html>`)
        );
        await new Promise((resolve) => setTimeout(resolve));

        expect(document.body).toHaveTextContent('Next page');
        expect(document.getElementById('books-stream')).toBe(element);
        expect(FakeEventSource.instances).toHaveLength(2);
        expect(FakeEventSource.instances[1].readyState).toBe(FakeEventSource.OPEN);
        expect(element).toHaveAttribute('connected');
    });

    it('closes its EventSource when Turbo renders a page that does not have it', async () => {
        FakeEventSource.instances[0].open();

        await Turbo.session.view.renderPage(
            Turbo.PageSnapshot.fromHTMLString('<html><head></head><body><p>Next page</p></body></html>')
        );
        await new Promise((resolve) => setTimeout(resolve));

        expect(document.body).toHaveTextContent('Next page');
        expect(FakeEventSource.instances).toHaveLength(1);
        expect(FakeEventSource.instances[0].readyState).toBe(FakeEventSource.CLOSED);
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
