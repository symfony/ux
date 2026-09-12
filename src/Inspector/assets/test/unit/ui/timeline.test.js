import { projectActivity } from '../../../src/core/activity-projector';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { EventMonitor } from '../../../src/core/event-monitor';
import { Timeline } from '../../../src/ui/timeline';
import { ActivityDrawer } from '../../../src/ui/activity-drawer';

describe('Timeline', () => {
    let timeline;
    let mockMonitor;

    function makeEntry(overrides = {}) {
        return {
            type: 'turbo',
            event: 'turbo:load',
            time: 150,
            target: null,
            detail: null,
            ...overrides,
        };
    }

    function connectedTarget(tag = 'turbo-frame', id = 'messages') {
        const target = document.createElement(tag);
        target.id = id;
        document.body.appendChild(target);
        return target;
    }

    function addEntry(entry) {
        mockMonitor.entries.push(entry);
        timeline.addEntry(entry);
    }

    function render(entry) {
        addEntry(entry);
        timeline.flush();
        return timeline.element.querySelector('.event');
    }

    beforeEach(() => {
        mockMonitor = { entries: [] };
        mockMonitor.project = (_, compact) => projectActivity(mockMonitor.entries, compact);
        mockMonitor.clear = vi.fn(() => {
            mockMonitor.entries = [];
        });
        timeline = new Timeline(mockMonitor);
        document.body.innerHTML = '';
    });

    afterEach(() => {
        document.body.innerHTML = '';
        vi.restoreAllMocks();
    });

    describe('structure and selection', () => {
        it('renders events with parameters as semantic rows controlling an inline detail', () => {
            const item = render(makeEntry({ target: connectedTarget(), detail: { value: 1 } }));
            const row = item.querySelector('.event-row');
            const disclosure = row.querySelector('.disclosure');
            const go = row.querySelector('.event-go');
            const detail = item.querySelector('.event-detail');

            expect(timeline.element.className).toBe('timeline');
            expect(timeline.element.querySelector('.events').tagName).toBe('OL');
            expect(item.tagName).toBe('LI');
            expect(disclosure.tagName).toBe('BUTTON');
            expect(disclosure.type).toBe('button');
            expect(disclosure.tabIndex).toBe(0);
            expect(go.tagName).toBe('BUTTON');
            expect(disclosure.contains(go)).toBe(false);
            expect(row.querySelector('button button')).toBeNull();
            expect(disclosure.getAttribute('aria-controls')).toBe(detail.id);
            expect(disclosure.getAttribute('aria-expanded')).toBe('false');
            expect(detail.hidden).toBe(true);
        });

        it('renders events without parameters as compact static rows', () => {
            const item = render(makeEntry({ event: 'live:connect' }));

            expect(item.querySelector('.event-row').classList.contains('compact')).toBe(true);
            expect(item.querySelector('.disclosure').tagName).toBe('DIV');
            expect(item.querySelector('button.disclosure')).toBeNull();
            expect(item.querySelector('.event-detail')).toBeNull();
        });

        it('expands the selected activity directly below its row', () => {
            const entry = makeEntry({ detail: { value: 1 } });
            const selected = vi.fn();
            timeline.element.addEventListener('activity-selected', selected);
            const item = render(entry);
            const disclosure = item.querySelector('.disclosure');

            disclosure.click();
            expect(disclosure.getAttribute('aria-expanded')).toBe('true');
            expect(item.querySelector('.event-row').classList.contains('selected')).toBe(true);
            expect(item.querySelector('.event-detail').hidden).toBe(false);
            expect(item.querySelector('.event-detail .activity-detail')).not.toBeNull();
            expect(selected).toHaveBeenCalledOnce();
            expect(selected.mock.calls[0][0].detail.entry).toBe(entry);
        });

        it('expands the newest visible activity with parameters on request', () => {
            addEntry(makeEntry({ event: 'first', detail: { value: 1 } }));
            addEntry(makeEntry({ event: 'latest', detail: null }));
            timeline.flush();

            timeline.expandFirstVisible();

            const visible = [...timeline.element.querySelectorAll('.event:not([hidden])')];
            expect(visible[0].querySelector('.name').textContent).toBe('latest');
            expect(visible[0].querySelector('button.disclosure')).toBeNull();
            expect(visible[1].querySelector('.name').textContent).toBe('first');
            expect(visible[1].querySelector('.disclosure').getAttribute('aria-expanded')).toBe('true');
        });

        it('keeps contextual activity to one row per raw event and uses event names as titles', () => {
            timeline.configure({ contextual: true });
            addEntry(makeEntry({ event: 'live:connect', label: 'SearchDashboard: connect' }));
            addEntry(makeEntry({ event: 'live:connect', label: 'SearchDashboard: connect' }));
            timeline.flush();

            const names = [...timeline.element.querySelectorAll('.name')].map((node) => node.textContent);
            expect(names).toEqual(['live:connect', 'live:connect']);
            expect(timeline.element.querySelector('.event-count')).toBeNull();
        });

        it('projects a LiveComponent lifecycle as one contextual rerender', () => {
            timeline.configure({ contextual: true });
            addEntry(makeEntry({ type: 'livecomponent', event: 'live:request', detail: { actions: ['send'] } }));
            addEntry(makeEntry({ type: 'livecomponent', event: 'live:render:started' }));
            addEntry(makeEntry({ type: 'livecomponent', event: 'live:render:finished' }));
            timeline.flush();

            const names = [...timeline.element.querySelectorAll('.name')].map((node) => node.textContent);
            expect(names).toEqual(['rerender · send()']);
        });

        it('shows a rerender trigger, calls, changes and hooks in one detail', () => {
            const entry = makeEntry({
                type: 'livecomponent',
                event: 'live:rerender',
                activityKind: 'live-rerender',
                live: {
                    trigger: 'save()',
                    actions: ['save'],
                    models: ['title'],
                    changes: [{ model: 'title', value: 'Draft' }],
                    hooks: ['request', 'render:started', 'render:finished'],
                    status: 'complete',
                    duration: 24,
                },
            });

            const detail = timeline.renderDetail(entry);

            expect(detail.querySelector('[data-field-key="Trigger"] .value').textContent).toBe('save()');
            expect(detail.querySelector('[data-field-key="Calls"] .value').textContent).toContain('save');
            expect(detail.querySelector('[data-field-key="Changes"] .value').textContent).toContain('title');
            expect(detail.querySelector('[data-field-key="Hooks"] .value').textContent).toContain('render:finished');
            expect(detail.querySelector('[data-field-key="Status"] .value').textContent).toBe('complete');
            expect(detail.querySelector('[data-field-key="Duration"] .value').textContent).toBe('24 ms');
        });

        it('collapses an open activity when clicked again', () => {
            const item = render(makeEntry({ detail: { value: 1 } }));
            const disclosure = item.querySelector('.disclosure');

            disclosure.click();
            disclosure.click();

            expect(disclosure.getAttribute('aria-expanded')).toBe('false');
            expect(item.querySelector('.event-detail').hidden).toBe(true);
            expect(item.querySelector('.event-detail').children).toHaveLength(0);
        });

        it('keeps only one activity selected at a time', () => {
            addEntry(makeEntry({ event: 'turbo:before-fetch-request', detail: { value: 1 } }));
            addEntry(makeEntry({ event: 'turbo:load', detail: { value: 2 } }));
            timeline.flush();
            const disclosures = [...timeline.element.querySelectorAll('.disclosure')];

            disclosures[0].click();
            disclosures[1].click();

            expect(disclosures[0].getAttribute('aria-expanded')).toBe('false');
            expect(disclosures[1].getAttribute('aria-expanded')).toBe('true');
            expect(timeline.element.querySelectorAll('.event-row.selected')).toHaveLength(1);
            expect(timeline.element.querySelectorAll('.event-detail:not([hidden])')).toHaveLength(1);
        });

        it('renders detail data as text instead of HTML', () => {
            const detail = timeline.renderDetail(makeEntry({ detail: { html: '<img src=x onerror=alert(1)>' } }));

            expect(detail.textContent).toContain('<img src=x onerror=alert(1)>');
            expect(detail.querySelector('img')).toBeNull();
        });

        it('shows payload values directly without repeating event metadata', () => {
            const detail = timeline.renderDetail(makeEntry({ detail: { value: 13, nested: { enabled: true } } }));

            expect(detail.querySelector('[data-field-key="value"] .value').textContent).toBe('13');
            expect(detail.querySelector('[data-field-key="nested"] .value-meta').textContent).toBe('Object · 1');
            expect(detail.querySelector('[data-field-key="Framework"]')).toBeNull();
            expect(detail.querySelector('[data-field-key="Time"]')).toBeNull();
            expect(detail.querySelector('[data-field-key="Target"]')).toBeNull();
            expect(detail.querySelector('[data-field-key="Detail"]')).toBeNull();
        });

        it('moves selection with vertical arrow keys', () => {
            addEntry(makeEntry({ event: 'first', detail: { value: 1 } }));
            addEntry(makeEntry({ event: 'second', detail: { value: 2 } }));
            timeline.flush();
            document.body.appendChild(timeline.element);
            const disclosures = [...timeline.element.querySelectorAll('.disclosure')];

            disclosures[0].focus();
            disclosures[0].dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));

            expect(document.activeElement).toBe(disclosures[1]);
            expect(disclosures[1].getAttribute('aria-expanded')).toBe('false');
        });
    });

    describe('collapsed identity and navigation', () => {
        it('keeps the event and target visible while moving framework and time to the accessible name', () => {
            const target = connectedTarget('turbo-frame', 'checkout');
            const item = render(makeEntry({ label: 'stream: append → #messages', time: 500, target }));
            const disclosure = item.querySelector('.disclosure');

            expect(disclosure.querySelector('.time')).toBeNull();
            expect(disclosure.querySelector('.uxli-timeline-framework')).toBeNull();
            expect(disclosure.querySelector('.name').textContent).toBe('stream: append → #messages');
            expect(disclosure.querySelector('.event-target').textContent).toBe('turbo-frame#checkout');
            expect(disclosure.getAttribute('aria-label')).toBe(
                'Turbo activity, stream: append → #messages, turbo-frame#checkout, 0.500 seconds'
            );
        });

        it('shows the event as the title and the LiveComponent name as context', () => {
            const target = connectedTarget('div', 'live-2893674948-0');
            target.dataset.controller = 'live';
            target.dataset.liveNameValue = 'SearchDashboard';
            const item = render(
                makeEntry({ type: 'livecomponent', event: 'live:render', label: 'SearchDashboard: render', target })
            );
            const disclosure = item.querySelector('.disclosure');

            expect(disclosure.querySelector('.name').textContent).toBe('render');
            expect(disclosure.querySelector('.event-target').textContent).toBe('SearchDashboard');
            expect(disclosure.textContent).not.toContain('div#live-');
            expect(disclosure.getAttribute('aria-label')).toContain('div#live-2893674948-0');
        });

        it('gives the Go button an explicit target-aware name and selects the target', () => {
            const target = connectedTarget('turbo-frame', 'messages');
            const onSelect = vi.fn();
            const onHighlight = vi.fn();
            timeline = new Timeline(mockMonitor, { onSelect, onHighlight });
            const item = render(makeEntry({ target }));
            const go = item.querySelector('.event-go');

            expect(go.getAttribute('aria-label')).toBe('Go to component turbo-frame#messages');
            expect(go.title).toBe('Go to component turbo-frame#messages');
            expect(go.hidden).toBe(false);

            go.click();
            expect(onHighlight).toHaveBeenCalledWith(null);
            expect(onSelect).toHaveBeenCalledWith(target);
        });

        it('hides Go for disconnected targets and rechecks before navigation', () => {
            const target = connectedTarget();
            const onSelect = vi.fn();
            timeline = new Timeline(mockMonitor, { onSelect });
            const item = render(makeEntry({ target }));
            const row = item.querySelector('.event-row');
            const go = item.querySelector('.event-go');

            target.remove();
            row.dispatchEvent(new MouseEvent('mouseenter'));
            expect(go.hidden).toBe(true);

            go.click();
            expect(onSelect).not.toHaveBeenCalled();
        });

        it('highlights connected targets on row hover and clears on leave', () => {
            const target = connectedTarget();
            const onHighlight = vi.fn();
            timeline = new Timeline(mockMonitor, { onHighlight });
            const row = render(makeEntry({ target })).querySelector('.event-row');
            onHighlight.mockClear();

            row.dispatchEvent(new MouseEvent('mouseenter'));
            row.dispatchEvent(new MouseEvent('mouseleave'));

            expect(onHighlight).toHaveBeenNthCalledWith(1, target, 'turbo');
            expect(onHighlight).toHaveBeenNthCalledWith(2, null);
        });
    });

    it('does not group consecutive events from different frameworks', () => {
        const target = connectedTarget('div', 'shared');
        addEntry(makeEntry({ type: 'stimulus', event: 'connect', label: 'connect', target }));
        addEntry(makeEntry({ type: 'turbo', event: 'connect', label: 'connect', target }));
        timeline.flush();

        expect(timeline.element.querySelectorAll('.event')).toHaveLength(2);
    });

    it('clears a selected activity when the bounded list evicts it', () => {
        const first = render(makeEntry({ event: 'first', label: 'first' }));
        first.querySelector('.disclosure').click();

        for (let index = 0; index < 100; index++) {
            addEntry(makeEntry({ event: `event-${index}`, label: `event-${index}` }));
        }
        timeline.flush();

        expect(timeline.element.querySelector('.event-row.selected')).toBeNull();
    });

    describe('batching and grouping', () => {
        it('batches entries until flushed and removes the empty state', () => {
            timeline.refresh();
            addEntry(makeEntry());

            expect(timeline.element.querySelector('.event-row')).toBeNull();
            timeline.flush();
            expect(timeline.element.querySelector('.event-row')).not.toBeNull();
            expect(timeline.element.querySelector('.events > .empty')).toBeNull();
        });

        it('groups consecutive identical events from the same target before Go', () => {
            const target = connectedTarget();
            addEntry(makeEntry({ target }));
            addEntry(makeEntry({ target }));
            timeline.flush();
            const row = timeline.element.querySelector('.event-row');

            expect(timeline.element.querySelectorAll('.event')).toHaveLength(1);
            expect(row.querySelector('.event-count').textContent).toBe('2');
            expect(row.lastElementChild.classList.contains('event-go')).toBe(true);
        });

        it('projects a Turbo fetch pair into one normalized operation with raw hooks', () => {
            const target = connectedTarget('a', 'live');
            const request = makeEntry({
                event: 'turbo:before-fetch-request',
                time: 10,
                target,
                detail: {
                    url: 'https://example.com/live',
                    fetchOptions: { method: 'GET', headers: { 'X-Sec-Purpose': 'prefetch' }, priority: 'low' },
                },
            });
            const response = makeEntry({
                event: 'turbo:before-fetch-response',
                time: 42,
                target,
                detail: { fetchResponse: { response: { url: 'https://example.com/live', status: 200 } } },
            });

            addEntry(request);
            addEntry(response);
            timeline.flush();

            const item = timeline.element.querySelector('.event');
            expect(timeline.element.querySelectorAll('.event')).toHaveLength(1);
            expect(item.querySelector('.name').textContent).toBe('prefetch GET /live');
            item.querySelector('.disclosure').click();
            expect(item.querySelector('.activity-detail').textContent).toContain('IntentPrefetch');
            expect(item.querySelector('.activity-detail').textContent).toContain('Raw hooks2');
            expect(item.querySelector('.raw-events').open).toBe(false);
            expect(item.querySelectorAll('.raw-list li')).toHaveLength(2);
        });

        it('produces the same projected rows live and after refresh', () => {
            const target = connectedTarget('a', 'live');
            const entries = [
                makeEntry({
                    event: 'turbo:before-fetch-request',
                    time: 10,
                    target,
                    detail: { url: '/live', fetchOptions: { method: 'GET', headers: {} } },
                }),
                makeEntry({
                    event: 'turbo:before-fetch-response',
                    time: 20,
                    target,
                    detail: { fetchResponse: { response: { url: '/live', status: 200 } } },
                }),
            ];
            addEntry(entries[0]);
            addEntry(entries[1]);
            timeline.flush();
            const live = timeline.element.textContent;

            mockMonitor.entries = entries;
            timeline.refresh();

            expect(timeline.element.textContent).toBe(live);
        });

        it('does not group identical events from different target elements', () => {
            const first = connectedTarget('button', 'first');
            const second = connectedTarget('button', 'second');
            addEntry(makeEntry({ target: first }));
            addEntry(makeEntry({ target: second }));
            timeline.flush();

            expect(timeline.element.querySelectorAll('.event')).toHaveLength(2);
        });

        it('preserves grouping when refreshing from monitor entries', () => {
            const target = connectedTarget();
            mockMonitor.entries = [makeEntry({ target }), makeEntry({ target })];
            timeline.refresh();

            expect(timeline.element.querySelectorAll('.event')).toHaveLength(1);
            expect(timeline.element.querySelector('.event-count').textContent).toBe('2');
        });

        it('limits rendered rows to the latest 100 entries', () => {
            for (let index = 0; index < 105; index++) {
                addEntry(makeEntry({ event: `event:${index}` }));
            }
            timeline.flush();

            expect(timeline.element.querySelectorAll('.event')).toHaveLength(100);
            expect(timeline.element.textContent).toContain('event:104');
            expect(timeline.element.textContent).not.toContain('event:0');
        });
    });

    describe('filter and diagnostic copy', () => {
        it('filters by event, framework, target, and owning controller identity', () => {
            const component = connectedTarget('section', 'checkout');
            component.dataset.controller = 'cart';
            const button = document.createElement('button');
            button.id = 'save';
            component.appendChild(button);
            addEntry(makeEntry({ type: 'stimulus', event: 'click', label: 'save → submit()', target: button }));
            addEntry(makeEntry({ type: 'turbo', event: 'turbo:load', target: connectedTarget() }));
            timeline.flush();

            for (const query of ['submit', 'stimulus', 'button#save', 'cart']) {
                timeline.configure({ query });
                const visible = [...timeline.element.querySelectorAll('.event')].filter((item) => !item.hidden);
                expect(visible).toHaveLength(1);
                expect(visible[0].textContent).toContain('save → submit()');
            }

            timeline.configure({ query: 'missing' });
            expect(timeline.element.querySelector('.empty:not([hidden])').textContent).toContain('No matches.');
        });

        it('filters to an exact component instance even when labels are duplicated', () => {
            const first = connectedTarget('section', 'first');
            const second = connectedTarget('section', 'second');
            first.dataset.controller = 'counter';
            second.dataset.controller = 'counter';
            addEntry(makeEntry({ type: 'stimulus', event: 'counter:change', owner: first, target: first }));
            addEntry(makeEntry({ type: 'stimulus', event: 'counter:change', owner: second, target: second }));
            timeline.flush();

            timeline.configure({ query: 'counter', element: first });

            const visible = [...timeline.element.querySelectorAll('.event')].filter((item) => !item.hidden);
            expect(visible).toHaveLength(1);
            expect(visible[0].textContent).toContain('counter:change');
            expect(visible[0].querySelector('.event-target').textContent).toBe('section#first');
        });

        it('filters by one or more frameworks independently from the text query', () => {
            addEntry(makeEntry({ type: 'stimulus', event: 'counter:change' }));
            addEntry(makeEntry({ type: 'livecomponent', event: 'live:render' }));
            addEntry(makeEntry({ type: 'turbo', event: 'turbo:load' }));
            timeline.flush();

            timeline.configure({ frameworks: ['stimulus', 'livecomponent'] });
            let visible = [...timeline.element.querySelectorAll('.event')].filter((item) => !item.hidden);
            expect(visible).toHaveLength(2);
            expect(visible.map((item) => item._inspectorEntry.type)).toEqual(['livecomponent', 'stimulus']);

            timeline.configure({ query: 'counter' });
            visible = [...timeline.element.querySelectorAll('.event')].filter((item) => !item.hidden);
            expect(visible).toHaveLength(1);
            expect(visible[0]._inspectorEntry.type).toBe('stimulus');
        });

        it('keeps a matching selection and clears it when the filter hides it', () => {
            const entry = makeEntry({ event: 'turbo:load', detail: { value: 1 } });
            const selected = vi.fn();
            timeline.element.addEventListener('activity-selected', selected);
            render(entry).querySelector('.disclosure').click();

            timeline.configure({ query: 'turbo' });
            expect(timeline.element.querySelector('.event-row.selected')).not.toBeNull();
            timeline.configure({ query: 'stimulus' });

            expect(timeline.element.querySelector('.event-row.selected')).toBeNull();
            expect(selected.mock.calls.at(-1)[0].detail.entry).toBeNull();
        });

        it('copies only the monitor diagnostic for the selected activity', async () => {
            const writeText = vi.fn().mockResolvedValue(undefined);
            Object.defineProperty(navigator, 'clipboard', { configurable: true, value: { writeText } });
            const entry = makeEntry({ detail: { csrfToken: '[redacted]' } });
            render(entry).querySelector('.disclosure').click();

            expect(await timeline.copySelected()).toBe(true);
            expect(JSON.parse(writeText.mock.calls[0][0])).toEqual({
                event: 'turbo:load',
                framework: 'turbo',
                time: 0.15,
                detail: { csrfToken: '[redacted]' },
            });

            const copy = timeline.renderDetail(entry).querySelector('.event-copy');
            copy.click();
            await vi.waitFor(() => expect(copy.getAttribute('aria-label')).toBe('Activity copied'));
        });

        it('reports copy failure without a selection or clipboard access', async () => {
            Object.defineProperty(navigator, 'clipboard', { configurable: true, value: undefined });
            expect(await timeline.copySelected()).toBe(false);
            render(makeEntry()).querySelector('.disclosure').click();
            expect(await timeline.copySelected()).toBe(false);
        });
    });

    describe('pause, resume, and clear', () => {
        it('freezes the DOM while paused and rebuilds it from monitor entries on resume', () => {
            mockMonitor.entries = [makeEntry({ event: 'before-pause' })];
            timeline.refresh();
            const frozen = timeline.element.innerHTML;

            expect(timeline.pause()).toBe(true);
            expect(timeline.paused).toBe(true);
            addEntry(makeEntry({ event: 'while-paused' }));
            timeline.refresh();

            expect(timeline.element.innerHTML).toBe(frozen);
            expect(timeline.resume()).toBe(true);
            expect(timeline.paused).toBe(false);
            expect(timeline.element.textContent).toContain('before-pause');
            expect(timeline.element.textContent).toContain('while-paused');
        });

        it('cancels a queued render when paused', () => {
            addEntry(makeEntry({ event: 'queued' }));
            timeline.pause();
            timeline.flush();

            expect(timeline.element.querySelector('.event-row')).toBeNull();
        });

        it('clears monitor history, pending work, grouping, and rendered rows', () => {
            mockMonitor.entries = [makeEntry(), makeEntry()];
            timeline.refresh();
            addEntry(makeEntry({ event: 'pending' }));

            timeline.clear();
            timeline.flush();

            expect(mockMonitor.clear).toHaveBeenCalledOnce();
            expect(mockMonitor.entries).toHaveLength(0);
            expect(timeline.element.querySelectorAll('.event')).toHaveLength(0);
            expect(timeline.element.querySelector('.empty').textContent).toContain('No events captured yet.');
        });

        it('reports idempotent pause and resume transitions', () => {
            expect(timeline.pause()).toBe(true);
            expect(timeline.pause()).toBe(false);
            expect(timeline.resume()).toBe(true);
            expect(timeline.resume()).toBe(false);
        });
    });
    it('retains existing DOM and focus when new events arrive', () => {
        document.body.append(timeline.element);
        const item = render(makeEntry({ event: 'first', detail: { value: 1 } }));
        const button = item.querySelector('.disclosure');
        button.click();
        button.focus();
        render(makeEntry({ event: 'second', detail: { value: 2 } }));
        expect(button.isConnected).toBe(true);
        expect(document.activeElement).toBe(button);
        expect(button.getAttribute('aria-expanded')).toBe('true');
    });

    it('restores focus and expansion when the focused operation grows', () => {
        document.body.append(timeline.element);
        const item = render(makeEntry({ detail: { value: 1 } }));
        const button = item.querySelector('.disclosure');
        button.click();
        button.focus();
        render(makeEntry({ detail: { value: 1 } }));
        const current = timeline.element.querySelector('.disclosure');
        expect(document.activeElement).toBe(current);
        expect(current.getAttribute('aria-expanded')).toBe('true');
        expect(timeline.element.querySelector('.event-count').textContent).toBe('2');
    });

    it('keeps focus on the same control when the row gains a disclosure', () => {
        document.body.append(timeline.element);
        const target = connectedTarget('div', 'live-1');
        target.dataset.controller = 'live';
        // Without parameters the row is compact: Go is its only focusable control.
        const change = makeEntry({ type: 'livecomponent', event: 'live:model:set', time: 10, target, detail: {} });
        render(change).querySelector('.event-go').focus();

        // The request projects the change into a rerender, which adds a disclosure button.
        addEntry(makeEntry({ type: 'livecomponent', event: 'live:request', time: 20, target, detail: {} }));
        timeline.flush();

        const item = timeline.element.querySelector('.event');
        expect(item.querySelector('button.disclosure')).not.toBeNull();
        expect(document.activeElement).toBe(item.querySelector('.event-go'));
    });

    it('walks the target ancestors once per rendered activity', () => {
        const target = connectedTarget('div', 'card');
        target.dataset.controller = 'cart';
        const closest = vi.spyOn(target, 'closest');

        render(makeEntry({ target, detail: { value: 1 } }));

        expect(closest).toHaveBeenCalledOnce();
    });

    it('renders activities whose target cannot be walked for an owner', () => {
        const item = render(makeEntry({ target: {}, detail: { value: 1 } }));

        expect(item.querySelector('.name').textContent).toBe('turbo:load');
        expect(item.querySelector('.event-go').hidden).toBe(true);
    });

    it('updates navigation when an unchanged activity target disconnects and reconnects', () => {
        const onSelect = vi.fn();
        timeline = new Timeline(mockMonitor, { onSelect });
        const target = connectedTarget();
        target.remove();
        render(makeEntry({ target, detail: { value: 1 } }));
        const go = () => timeline.element.querySelector('.event-go');
        expect(go().hidden).toBe(true);
        document.body.append(target);
        timeline.refresh();
        expect(go().hidden).toBe(false);
        go().click();
        expect(onSelect).toHaveBeenCalledExactlyOnceWith(target);
        target.remove();
        timeline.refresh();
        expect(go().hidden).toBe(true);
    });

    it('uses the bounded monitor history, including after pause and eviction', () => {
        const monitor = new EventMonitor(3);
        timeline = new Timeline(monitor);
        monitor.addListener((entry) => timeline.addEntry(entry));
        for (let i = 0; i < 6; i++) monitor.record({ event: `event:${i}`, detail: { i } });
        timeline.flush();
        expect(timeline.element.querySelectorAll('.event')).toHaveLength(3);
        expect(timeline.element.textContent).not.toContain('event:2');
        timeline.pause();
        for (let i = 6; i < 12; i++) monitor.record({ event: `event:${i}` });
        timeline.configure({ contextual: true });
        expect(timeline.element.textContent).toContain('event:3');
        expect(timeline.element.textContent).not.toContain('event:11');
        timeline.resume();
        expect(timeline.element.querySelectorAll('.event')).toHaveLength(3);
        expect(timeline.element.textContent).not.toContain('event:3');
        expect(timeline.element.textContent).toContain('event:11');
    });

    it('renders and filters once when moving activity between a drawer and the global panel', () => {
        const monitor = new EventMonitor();
        timeline = new Timeline(monitor);
        const home = document.createElement('div');
        const content = document.createElement('div');
        document.body.append(home, content);
        const target = connectedTarget('div', 'component');
        monitor.record({ type: 'turbo', event: 'other' });
        timeline.addEntry(monitor.record({ type: 'stimulus', event: 'local', target }));
        const restore = () =>
            timeline.configure({ contextual: false, element: null, frameworks: ['turbo'], query: 'other' });
        const drawer = new ActivityDrawer(timeline, home, restore);
        const project = vi.spyOn(monitor, 'project');
        const queries = vi.spyOn(timeline.element.querySelector('.events'), 'querySelectorAll');
        const visible = () => [...timeline.element.querySelectorAll('.event:not([hidden])')];

        drawer.open('component', content, target, 'component');
        timeline.flush();

        expect(project).toHaveBeenCalledExactlyOnceWith(null, false);
        expect(queries.mock.calls.filter(([selector]) => selector === '.event')).toHaveLength(1);
        expect(visible().map((item) => item._inspectorEntry.event)).toEqual(['local']);
        expect(timeline.element.parentElement).toBe(content.querySelector('.drawer'));
        project.mockClear();
        queries.mockClear();

        drawer.close();

        expect(project).toHaveBeenCalledExactlyOnceWith(null, true);
        expect(queries.mock.calls.filter(([selector]) => selector === '.event')).toHaveLength(1);
        expect(visible().map((item) => item._inspectorEntry.event)).toEqual(['other']);
        expect(timeline.element.parentElement).toBe(home);
        expect(monitor.entries).toHaveLength(2);
        monitor.destroy();
    });

    it('switches drawers without restoring the global context or advancing paused history', () => {
        const monitor = new EventMonitor(2);
        timeline = new Timeline(monitor);
        const first = connectedTarget('div', 'first');
        const second = connectedTarget('div', 'second');
        monitor.record({ event: 'first', target: first, detail: { value: 1 } });
        monitor.record({ event: 'second', target: second, detail: { value: 2 } });
        const restore = vi.fn();
        const drawer = new ActivityDrawer(timeline, document.createElement('div'), restore);
        const content = document.createElement('div');
        document.body.append(content);
        drawer.open('first', content, first, 'first');
        timeline.pause();
        monitor.record({ event: 'later', target: second });
        monitor.record({ event: 'latest', target: second });
        const project = vi.spyOn(monitor, 'project');

        drawer.open('second', content, second, 'second');

        expect(restore).not.toHaveBeenCalled();
        expect(project).not.toHaveBeenCalled();
        const visible = timeline.element.querySelector('.event:not([hidden])');
        expect(visible._inspectorEntry.event).toBe('second');
        expect(visible.querySelector('.disclosure').getAttribute('aria-expanded')).toBe('true');
        expect(timeline.element.textContent).not.toContain('latest');
        expect(timeline.paused).toBe(true);
        timeline.resume();
        expect(timeline.element.textContent).toContain('latest');
        expect(timeline.element.textContent).not.toContain('value');
        drawer.close();
        expect(restore).toHaveBeenCalledOnce();
        monitor.destroy();
    });
});
