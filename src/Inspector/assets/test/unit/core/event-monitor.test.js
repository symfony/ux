import { vi, describe, it, expect, beforeEach, afterEach } from 'vitest';
import { EventMonitor } from '../../../src/core/event-monitor';

/** Helper: register the standard framework events (equivalent to what plugins declare). */
function registerDefaultEvents(monitor) {
    monitor.monitorEvents(
        [
            'turbo:load',
            'turbo:visit',
            'turbo:render',
            'turbo:before-visit',
            'turbo:before-render',
            'turbo:frame-load',
            'turbo:frame-render',
            'turbo:before-frame-render',
            'turbo:submit-start',
            'turbo:submit-end',
            'turbo:before-stream-render',
        ],
        'turbo'
    );
    monitor.monitorEvents(['stimulus:connect', 'stimulus:disconnect'], 'stimulus');
    monitor.monitorEvents(
        ['live:connect', 'live:disconnect', 'live:render:started', 'live:render:finished', 'live:error'],
        'livecomponent'
    );
}

describe('EventMonitor', () => {
    let monitor;

    beforeEach(() => {
        monitor = new EventMonitor();
    });

    afterEach(() => {
        monitor.destroy();
        document.body.innerHTML = '';
        vi.restoreAllMocks();
    });

    describe('constructor', () => {
        it('defaults maxEntries to 500', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            for (let i = 0; i < 510; i++) {
                document.dispatchEvent(new CustomEvent('turbo:load'));
            }
            expect(monitor.entries).toHaveLength(500);
        });

        it('accepts a custom maxEntries value', () => {
            const small = new EventMonitor(5);
            registerDefaultEvents(small);
            small.start();
            for (let i = 0; i < 10; i++) {
                document.dispatchEvent(new CustomEvent('turbo:load'));
            }
            expect(small.entries).toHaveLength(5);
            small.destroy();
        });
    });

    describe('start()', () => {
        it('makes active true', () => {
            expect(monitor.active).toBe(false);
            monitor.start();
            expect(monitor.active).toBe(true);
        });

        it('registers event listeners so events are captured', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(monitor.entries).toHaveLength(1);
        });

        it('adds callback as listener when provided', () => {
            const received = [];
            registerDefaultEvents(monitor);
            monitor.start((entry) => received.push(entry));
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(received).toHaveLength(1);
            expect(received[0].event).toBe('turbo:load');
        });

        it('when already active just adds the new listener', () => {
            const first = [];
            const second = [];
            registerDefaultEvents(monitor);
            monitor.start((entry) => first.push(entry));
            monitor.start((entry) => second.push(entry));
            expect(monitor.active).toBe(true);

            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(first).toHaveLength(1);
            expect(second).toHaveLength(1);
        });
    });

    describe('stop()', () => {
        it('makes active false', () => {
            monitor.start();
            monitor.stop();
            expect(monitor.active).toBe(false);
        });

        it('removes event listeners so events are no longer captured', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            monitor.stop();
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(monitor.entries).toHaveLength(0);
        });

        it('is a no-op when not active', () => {
            expect(() => monitor.stop()).not.toThrow();
            expect(monitor.active).toBe(false);
        });
    });

    describe('event capture', () => {
        beforeEach(() => {
            registerDefaultEvents(monitor);
            monitor.start();
        });

        it('captures turbo events with type "turbo"', () => {
            document.dispatchEvent(new CustomEvent('turbo:visit'));
            const entry = monitor.entries[0];
            expect(entry.type).toBe('turbo');
            expect(entry.event).toBe('turbo:visit');
        });

        it('captures stimulus events with type "stimulus"', () => {
            document.dispatchEvent(new CustomEvent('stimulus:connect'));
            const entry = monitor.entries[0];
            expect(entry.type).toBe('stimulus');
            expect(entry.event).toBe('stimulus:connect');
        });

        it('captures live events with type "livecomponent"', () => {
            document.dispatchEvent(new CustomEvent('live:connect'));
            const entry = monitor.entries[0];
            expect(entry.type).toBe('livecomponent');
            expect(entry.event).toBe('live:connect');
        });

        it('records time, target, and detail', () => {
            const el = document.createElement('div');
            document.body.appendChild(el);
            el.dispatchEvent(new CustomEvent('turbo:load', { bubbles: true, detail: { url: '/test' } }));
            const entry = monitor.entries[0];
            expect(entry.time).toBeGreaterThan(0);
            expect(entry.target).toBe(el);
            expect(entry.detail).toEqual({ url: '/test' });
        });

        it('sets target to null when target is not an Element', () => {
            document.dispatchEvent(new CustomEvent('turbo:load'));
            const entry = monitor.entries[0];
            expect(entry.target).toBeNull();
        });
    });

    describe('listeners', () => {
        it('listener callback is called with the entry', () => {
            const received = [];
            registerDefaultEvents(monitor);
            monitor.start((entry) => received.push(entry));
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(received).toHaveLength(1);
            expect(received[0].event).toBe('turbo:load');
        });

        it('multiple listeners all receive entries', () => {
            const a = [];
            const b = [];
            registerDefaultEvents(monitor);
            monitor.start((entry) => a.push(entry));
            monitor.addListener((entry) => b.push(entry));
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(a).toHaveLength(1);
            expect(b).toHaveLength(1);
        });

        it('addListener() adds a callback', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            const received = [];
            monitor.addListener((entry) => received.push(entry));
            document.dispatchEvent(new CustomEvent('stimulus:connect'));
            expect(received).toHaveLength(1);
        });

        it('applies subscription changes to the next delivery, retaining duplicate registrations', () => {
            const added = vi.fn();
            const duplicate = vi.fn();
            const change = () => {
                monitor.removeListener(change);
                monitor.removeListener(duplicate);
                monitor.addListener(added);
            };
            monitor.addListener(change);
            monitor.addListener(duplicate);
            monitor.addListener(duplicate);

            monitor.record({ event: 'first' });
            expect(duplicate).toHaveBeenCalledTimes(2);
            expect(added).not.toHaveBeenCalled();
            monitor.record({ event: 'second' });
            expect(duplicate).toHaveBeenCalledTimes(3);
            expect(added).toHaveBeenCalledOnce();
        });
    });

    describe('entries', () => {
        it('getter returns a copy of the entries array', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            document.dispatchEvent(new CustomEvent('turbo:load'));
            const copy = monitor.entries;
            copy.push({ fake: true });
            expect(monitor.entries).toHaveLength(1);
        });
    });

    describe('native web snapshots', () => {
        it('serializes fetch primitives and redacts sensitive headers and URL parameters', () => {
            const headers = new Headers({
                Accept: 'text/html',
                Authorization: 'Bearer private',
                Cookie: 'session=private',
                'X-Sec-Purpose': 'prefetch',
            });
            const response = new Response(null, { status: 201, headers: { 'Set-Cookie': 'private', 'X-Trace': 'ok' } });
            const signal = new AbortController().signal;

            monitor.record({
                type: 'turbo',
                event: 'turbo:before-fetch-request',
                detail: {
                    url: new URL('https://example.com/live?page=2&token=private'),
                    params: new URLSearchParams('page=2&csrfToken=private'),
                    headers,
                    response,
                    signal,
                },
            });

            const detail = monitor.entries[0].detail;
            expect(detail.url).toContain('page=2');
            expect(detail.url).toContain('token=%5Bredacted%5D');
            expect(detail.params).toContain('csrfToken=%5Bredacted%5D');
            expect(detail.headers).toMatchObject({
                accept: 'text/html',
                authorization: '[redacted]',
                cookie: '[redacted]',
                'x-sec-purpose': 'prefetch',
            });
            expect(detail.response).toMatchObject({
                status: 201,
                ok: true,
                headers: { 'set-cookie': '[redacted]', 'x-trace': 'ok' },
            });
            expect(detail.signal).toEqual({ aborted: false });
            expect(JSON.stringify(detail)).not.toContain('Bearer private');
            expect(JSON.stringify(detail)).not.toContain('session=private');
        });
    });

    describe('clear()', () => {
        it('empties entries', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(monitor.entries).toHaveLength(1);
            monitor.clear();
            expect(monitor.entries).toHaveLength(0);
        });
    });

    describe('destroy()', () => {
        it('stops monitoring, clears entries, and removes listeners', () => {
            const received = [];
            registerDefaultEvents(monitor);
            monitor.start((entry) => received.push(entry));
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(monitor.entries).toHaveLength(1);

            monitor.destroy();

            expect(monitor.active).toBe(false);
            expect(monitor.entries).toHaveLength(0);

            // Listener should no longer fire
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(received).toHaveLength(1);
        });
    });

    describe('maxEntries limit', () => {
        it('evicts oldest entries when limit is exceeded', () => {
            const small = new EventMonitor(3);
            registerDefaultEvents(small);
            small.start();
            document.dispatchEvent(new CustomEvent('turbo:load', { detail: { i: 1 } }));
            document.dispatchEvent(new CustomEvent('turbo:visit', { detail: { i: 2 } }));
            document.dispatchEvent(new CustomEvent('turbo:render', { detail: { i: 3 } }));
            document.dispatchEvent(new CustomEvent('turbo:load', { detail: { i: 4 } }));

            const entries = small.entries;
            expect(entries).toHaveLength(3);
            expect(entries[0].detail).toEqual({ i: 2 });
            expect(entries[2].detail).toEqual({ i: 4 });
            small.destroy();
        });
    });

    describe('getEntriesForElement()', () => {
        it('returns entries for the exact element', () => {
            const el = document.createElement('div');
            document.body.appendChild(el);
            registerDefaultEvents(monitor);
            monitor.start();
            el.dispatchEvent(new CustomEvent('turbo:load', { bubbles: true }));
            document.dispatchEvent(new CustomEvent('turbo:visit'));

            const result = monitor.getEntriesForElement(el);
            expect(result).toHaveLength(1);
            expect(result[0].event).toBe('turbo:load');
        });

        it('does not attribute child entries to the parent by default', () => {
            const parent = document.createElement('div');
            const child = document.createElement('span');
            parent.appendChild(child);
            document.body.appendChild(parent);
            registerDefaultEvents(monitor);
            monitor.start();
            child.dispatchEvent(new CustomEvent('turbo:load', { bubbles: true }));

            const result = monitor.getEntriesForElement(parent);
            expect(result).toHaveLength(0);
        });

        it('can include entries owned by descendants explicitly', () => {
            const parent = document.createElement('div');
            const child = document.createElement('span');
            parent.appendChild(child);
            document.body.appendChild(parent);
            monitor.record({ type: 'stimulus', event: 'search:change', target: child, owner: child });

            const result = monitor.getEntriesForElement(parent, { includeDescendants: true });
            expect(result).toHaveLength(1);
        });

        it('uses the resolved component owner for descendant event targets', () => {
            const component = document.createElement('div');
            const button = document.createElement('button');
            component.appendChild(button);
            document.body.appendChild(component);
            monitor.record({ type: 'stimulus', event: 'click', target: button, owner: component });

            expect(monitor.getEntriesForElement(component)).toHaveLength(1);
        });

        it('returns empty array for unrelated elements', () => {
            const el = document.createElement('div');
            const other = document.createElement('span');
            document.body.appendChild(el);
            document.body.appendChild(other);
            registerDefaultEvents(monitor);
            monitor.start();
            el.dispatchEvent(new CustomEvent('turbo:load', { bubbles: true }));

            const result = monitor.getEntriesForElement(other);
            expect(result).toHaveLength(0);
        });
    });

    describe('project()', () => {
        it('does not traverse immutable history again across projections', () => {
            const target = document.createElement('div');
            for (let i = 0; i < 100; i++) {
                monitor.record({ event: `event:${i}`, target, detail: { nested: { value: i } } });
            }
            const descriptors = vi.spyOn(Object, 'getOwnPropertyDescriptors');
            const rows = monitor.project();
            const scoped = monitor.project(target, false);
            const cached = monitor.project();
            const cachedScoped = monitor.project(target, false);
            const traversals = descriptors.mock.calls.length;
            descriptors.mockRestore();
            // Only the two new row arrays need freezing, not 100 entries and their snapshots.
            expect(traversals).toBe(2);
            expect(rows).toHaveLength(100);
            expect(scoped).toEqual(rows);
            expect(Object.isFrozen(rows)).toBe(true);
            expect(Object.isFrozen(scoped)).toBe(true);
            expect(Object.isFrozen(rows[0].detail.nested)).toBe(true);
            expect(cached).toBe(rows);
            expect(cachedScoped).toBe(scoped);
        });

        it('evicts history from every projection while preserving unrelated cached scopes', () => {
            monitor = new EventMonitor(2);
            const first = document.createElement('div');
            const second = document.createElement('div');
            const related = document.createElement('button');
            monitor.record({ event: 'old', owner: first, target: related, relatedElements: [first] });
            const retained = monitor.record({ event: 'retained', target: second });
            for (const compact of [true, false]) {
                monitor.project(null, compact);
                monitor.project(first, compact);
                monitor.project(related, compact);
            }
            const unchanged = monitor.project(second);
            const newest = monitor.record({ event: 'newest' });

            for (const compact of [true, false]) {
                expect(monitor.project(null, compact)).toEqual([retained, newest]);
                expect(monitor.project(first, compact)).toEqual([]);
                expect(monitor.project(related, compact)).toEqual([]);
            }
            expect(monitor.getEntriesForElement(first)).toEqual([]);
            expect(monitor.getEntriesForElement(related)).toEqual([]);
            expect(monitor.project(second)).toBe(unchanged);
        });

        it.each(['clear', 'destroy'])('releases all indexed history and projections on %s', (method) => {
            const target = document.createElement('div');
            monitor.record({ event: 'old', target, detail: { value: 1 } });
            for (const compact of [true, false]) {
                monitor.project(null, compact);
                monitor.project(target, compact);
            }
            monitor[method]();
            expect(monitor.entries).toEqual([]);
            expect(monitor.getEntriesForElement(target)).toEqual([]);
            for (const compact of [true, false]) {
                expect(monitor.project(null, compact)).toEqual([]);
                expect(monitor.project(target, compact)).toEqual([]);
            }
            const fresh = monitor.record({ event: 'fresh', target, detail: { value: 2 } });
            expect(monitor.project(target)).toEqual([fresh]);
            expect(Object.isFrozen(fresh.detail)).toBe(true);
        });
    });

    describe('monitorEvents()', () => {
        it('registers events with correct category', () => {
            monitor.monitorEvents(['custom:action'], 'myPlugin');
            monitor.start();
            document.dispatchEvent(new CustomEvent('custom:action'));
            expect(monitor.entries).toHaveLength(1);
            expect(monitor.entries[0].type).toBe('myPlugin');
        });

        it('defaults category to unknown when not specified', () => {
            monitor.monitorEvents(['custom:event']);
            monitor.start();
            document.dispatchEvent(new CustomEvent('custom:event'));
            expect(monitor.entries[0].type).toBe('unknown');
        });

        it('deduplicates event names', () => {
            const received = [];
            monitor.monitorEvents(['turbo:load'], 'turbo');
            monitor.monitorEvents(['turbo:load'], 'turbo');
            monitor.start((entry) => received.push(entry));
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(received).toHaveLength(1);
        });

        it('adds listeners immediately when already active', () => {
            monitor.start();
            monitor.monitorEvents(['custom:late'], 'custom');
            document.dispatchEvent(new CustomEvent('custom:late'));
            expect(monitor.entries).toHaveLength(1);
            expect(monitor.entries[0].type).toBe('custom');
        });
    });

    describe('setDynamicEvents()', () => {
        it('does not rebind unchanged events and releases exactly the active registrations', () => {
            const add = vi.spyOn(document, 'addEventListener');
            const remove = vi.spyOn(document, 'removeEventListener');
            monitor.monitorEvents(['static', 'shared'], 'custom');
            monitor.setDynamicEvents(['shared', 'dynamic'], 'custom');
            monitor.start();
            monitor.monitorEvents(['static', 'shared'], 'custom');
            monitor.setDynamicEvents(['shared', 'dynamic'], 'custom');
            expect(add).toHaveBeenCalledTimes(3);
            expect(remove).not.toHaveBeenCalled();

            monitor.setDynamicEvents(['next'], 'custom');
            expect(add).toHaveBeenCalledTimes(4);
            expect(remove.mock.calls.map(([name]) => name)).toEqual(['dynamic']);
            monitor.destroy();
            expect(remove.mock.calls.map(([name]) => name)).toEqual(['dynamic', 'static', 'shared', 'next']);
            monitor.start();
            expect(add).toHaveBeenCalledTimes(4);
        });

        it('replaces previous dynamic events for the same category', () => {
            // First register dynamic events via setDynamicEvents (not monitorEvents)
            monitor.setDynamicEvents(['ctrl:open', 'ctrl:close'], 'stimulus');
            monitor.start();

            // Replace with a new set
            monitor.setDynamicEvents(['ctrl:toggle'], 'stimulus');

            // Old events should no longer be captured
            document.dispatchEvent(new CustomEvent('ctrl:open'));
            expect(monitor.entries).toHaveLength(0);

            // New event should be captured
            document.dispatchEvent(new CustomEvent('ctrl:toggle'));
            expect(monitor.entries).toHaveLength(1);
            expect(monitor.entries[0].type).toBe('stimulus');
        });

        it('does not remove events from other categories', () => {
            monitor.monitorEvents(['turbo:load'], 'turbo');
            monitor.setDynamicEvents(['ctrl:open'], 'stimulus');
            monitor.start();

            monitor.setDynamicEvents(['ctrl:close'], 'stimulus');

            // turbo events are unaffected
            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(monitor.entries).toHaveLength(1);
            expect(monitor.entries[0].type).toBe('turbo');
        });

        it('does not remove static events registered via monitorEvents', () => {
            monitor.monitorEvents(['stimulus:connect'], 'stimulus');
            monitor.setDynamicEvents(['ctrl:open'], 'stimulus');
            monitor.start();

            // Replace dynamic set -- static event should survive
            monitor.setDynamicEvents(['ctrl:close'], 'stimulus');

            document.dispatchEvent(new CustomEvent('stimulus:connect'));
            expect(monitor.entries).toHaveLength(1);
            expect(monitor.entries[0].type).toBe('stimulus');
        });
    });

    describe('categorize', () => {
        it('returns category from monitorEvents registration', () => {
            monitor.monitorEvents(['turbo:load'], 'turbo');
            monitor.monitorEvents(['stimulus:connect'], 'stimulus');
            monitor.monitorEvents(['live:connect'], 'livecomponent');
            monitor.start();

            document.dispatchEvent(new CustomEvent('turbo:load'));
            expect(monitor.entries[0].type).toBe('turbo');

            document.dispatchEvent(new CustomEvent('stimulus:connect'));
            expect(monitor.entries[1].type).toBe('stimulus');

            document.dispatchEvent(new CustomEvent('live:connect'));
            expect(monitor.entries[2].type).toBe('livecomponent');
        });
    });

    describe('detail snapshots', () => {
        it('does not retain event detail objects or DOM nodes', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            const node = document.createElement('button');
            node.id = 'save';
            const detail = { node, nested: { value: 'before' } };

            document.dispatchEvent(new CustomEvent('turbo:load', { detail }));
            detail.nested.value = 'after';

            expect(monitor.entries[0].detail).toEqual({
                node: 'button#save',
                nested: { value: 'before' },
            });
        });

        it('bounds depth, collection size, and strings', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            const detail = {
                deep: { one: { two: { three: true } } },
                list: Array.from({ length: 25 }, (_, index) => index),
                text: 'x'.repeat(600),
            };

            document.dispatchEvent(new CustomEvent('turbo:load', { detail }));
            const snapshot = monitor.entries[0].detail;

            expect(snapshot.deep.one.two).toBe('[truncated]');
            expect(snapshot.list).toHaveLength(21);
            expect(snapshot.list.at(-1)).toBe('[truncated]');
            expect(snapshot.text.length).toBeLessThan(600);
        });

        it('handles cycles and redacts common secret fields', () => {
            registerDefaultEvents(monitor);
            monitor.start();
            const detail = { csrfToken: 'secret', password: 'secret' };
            detail.self = detail;

            document.dispatchEvent(new CustomEvent('turbo:load', { detail }));

            expect(monitor.entries[0].detail).toEqual({
                csrfToken: '[redacted]',
                password: '[redacted]',
                self: '[circular]',
            });
        });
    });
    it('records invalid dates without invoking overridden date methods', () => {
        const date = new Date('2025-01-01T00:00:00.000Z');
        date.toISOString = vi.fn(() => {
            throw new Error('must not run');
        });
        const entry = monitor.record({ event: 'custom', detail: { invalid: new Date(NaN), date } });
        expect(entry.detail).toEqual({ invalid: '[invalid date]', date: '2025-01-01T00:00:00.000Z' });
        expect(date.toISOString).not.toHaveBeenCalled();
    });
});
