import { projectActivity } from '../../../src/core/activity-projector';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { makeField } from '../../../src/ui/fields';
import { ComponentDetail } from '../../../src/ui/component-detail';

describe('ComponentDetail', () => {
    let target, related, registry, state, monitor, relationships, drill;

    beforeEach(() => {
        target = document.createElement('div');
        target.id = 'search-element';
        target.dataset.controller = 'search';
        related = document.createElement('div');
        related.id = 'results';
        const maps = new Map([[related, new Map([['stimulus', { data: {} }]])]]);
        registry = {
            get: vi.fn(() => ({
                getDisplayName: (element) => (element === target ? 'search' : 'results-list'),
                renderCard: (data) =>
                    Object.assign(document.createElement('p'), { textContent: `query ${data.data.query || 'hello'}` }),
            })),
        };
        state = new EventTarget();
        state.get = (element) => maps.get(element);
        state.maps = maps;
        monitor = {
            listeners: [],
            getEntriesForElement: vi.fn(() => [{ time: 1200, event: 'stimulus:connect', target }]),
            addListener(listener) {
                this.listeners.push(listener);
            },
            removeListener(listener) {
                this.listeners = this.listeners.filter((item) => item !== listener);
            },
        };
        monitor.project = (element) => projectActivity(monitor.getEntriesForElement(element));
        relationships = {
            getRelatedTo: vi.fn(() => [
                { source: target, target: related, type: 'outlet', label: 'search -> results' },
            ]),
        };
        drill = vi.fn();
    });

    function render() {
        const detail = new ComponentDetail(
            {
                registry,
                state,
                eventMonitor: monitor,
                relationshipEngine: relationships,
                render: (_, data, context) => registry.get().renderCard(data, context),
            },
            drill
        );
        return { detail, view: detail.render(target, new Map([['stimulus', { data: { query: 'hello' } }]])) };
    }

    it('prioritizes component identity and keeps the selector secondary', () => {
        const { detail, view } = render();
        expect(view.querySelector('h2').textContent).toBe('search');
        expect(view.querySelector('.framework').textContent).toBe('Stimulus');
        expect(view.querySelector('.framework').dataset.framework).toBe('stimulus');
        expect(view.querySelector('.detail-selector').textContent).toBe('div#search-element');
        expect(view.querySelector('.framework-detail > h3').textContent).toBe('Stimulus: search');
        expect(view.querySelector('.detail__stats')).toBeNull();
        expect(view.querySelector('.detail__tabs')).toBeNull();
        expect(view.querySelector('.detail-body').hasAttribute('data-tab')).toBe(false);
        expect(view.querySelector('.detail__activity')).toBeNull();
        expect(detail.activityCount).toBe(1);
        expect(view.querySelector('[role="region"]').hidden).toBe(false);
        expect(view.querySelector('[data-view="activity"]')).toBeNull();
        expect(view.querySelector('[data-group="component-events"]')).toBeNull();
        expect(view.querySelector('.activity-label').textContent).toBe('Activity');
        expect(view.querySelector('.activity-label .badge')).toBeNull();
    });

    it('shows plugin data without duplicating raw dataset facts', () => {
        const { view } = render();
        expect(view.textContent).toContain('query hello');
        expect(view.querySelector('.facts')).toBeNull();
        expect(view.textContent).not.toContain('data-controller');
    });

    it('groups outlets without verbose directional text and remains drillable', () => {
        const { view } = render();
        const relation = view.querySelector('.relation');
        expect(relation.textContent).toContain('results-list');
        expect(relation.textContent).toContain('#results');
        expect(relation.closest('details.group').querySelector(':scope > .title > .name').textContent).toBe('Outlets');
        expect(relation.closest('details.group').querySelector(':scope > .title > .badge')).toBeNull();
        expect(relation.closest('.component-relations')).toBeNull();
        expect(relation.closest('[data-group]').dataset.group).toBe('stimulus-outlets');
        expect(relation.closest('details.group').open).toBe(true);
        expect(relation.getAttribute('aria-label')).toBe('results outlet results-list #results');
        relation.click();
        expect(drill).toHaveBeenCalledWith(related);
    });

    it('combines declared and resolved outlets without title metadata', () => {
        registry.get.mockReturnValue({
            getDisplayName: (element) => (element === target ? 'search' : 'results-list'),
            renderCard: () => {
                const groups = document.createElement('div');
                groups.className = 'groups';
                const outlets = document.createElement('details');
                outlets.className = 'group';
                outlets.dataset.group = 'stimulus-outlets';
                outlets.innerHTML =
                    '<summary class="title"><span class="name">Outlets</span></summary><div class="content"><div class="key-value"></div></div>';
                groups.append(outlets);
                return groups;
            },
        });

        const { view } = render();

        const outlets = view.querySelector('[data-group="stimulus-outlets"]');
        expect(outlets.querySelector(':scope > .title > .badge')).toBeNull();
        expect(outlets.querySelectorAll(':scope > .content > *')).toHaveLength(2);
        expect(view.querySelectorAll('[data-group="stimulus-outlets"]')).toHaveLength(1);
    });

    it('separates incoming nesting into the parent component group', () => {
        relationships.getRelatedTo.mockReturnValue([
            { source: related, target, type: 'dom-parent', label: 'contains' },
        ]);
        const { view } = render();
        const parent = view.querySelector('[data-group="stimulus-parent"]');
        expect(parent.querySelector(':scope > .title > .name').textContent).toBe('Parent');
        expect(parent.querySelector(':scope > .title > .badge')).toBeNull();
        expect(parent.querySelector('.relation').textContent).toContain('parent component');
        expect(parent.querySelector('.relation').textContent).toContain('results-list');
    });

    it('does not repeat a bare tag name as secondary identity', () => {
        target.removeAttribute('id');
        registry.get.mockReturnValue({
            getDisplayName: () => 'greeter',
            renderCard: () => document.createElement('p'),
        });
        relationships.getRelatedTo.mockReturnValue([]);

        const { view } = render();

        expect(view.querySelector('h2').textContent).toBe('greeter');
        expect(view.querySelector('.detail-selector')).toBeNull();
    });

    it('omits relationship groups when no relationship data exists', () => {
        relationships.getRelatedTo.mockReturnValue([]);

        const { view } = render();
        expect(view.querySelector('[data-group="stimulus-parent"]')).toBeNull();
        expect(view.querySelector('[data-group="stimulus-children"]')).toBeNull();
        expect(view.querySelector('[data-group="stimulus-outlets"]')).toBeNull();
    });

    it('updates the recent activity footer and unsubscribes from the monitor', () => {
        const { detail, view } = render();
        monitor.getEntriesForElement.mockReturnValue([
            { time: 1200, event: 'stimulus:connect', target },
            { time: 1300, event: 'stimulus:action', target },
        ]);
        monitor.listeners[0]({ target });
        expect(view.querySelector('.activity-label').textContent).toBe('Activity');
        detail.destroy();
        expect(monitor.listeners).toHaveLength(0);
    });

    it('keeps Activity as a static group title when live data refreshes it', () => {
        const { view } = render();
        monitor.listeners[0]({ target });

        expect(view.querySelector('.activity-label').textContent).toBe('Activity');
        expect(view.querySelector('.detail__activity-link')).toBeNull();
    });

    it('shows every non-empty group open on first render', () => {
        const { detail, view } = render();
        const groups = [...view.querySelectorAll('.detail-body details.group')];

        expect(groups.map((group) => group.querySelector(':scope > .title > .name').textContent)).toEqual([
            'Details',
            'Outlets',
        ]);
        expect(groups.every((group) => group.open)).toBe(true);
        expect(detail.getUiState()).toEqual({ groups: { 'stimulus-details': true, 'stimulus-outlets': true } });
    });

    it('renders no placeholder when a component exposes no detail groups', () => {
        registry.get.mockReturnValue({
            getDisplayName: () => 'empty',
            renderCard: () => document.createDocumentFragment(),
        });
        relationships.getRelatedTo.mockReturnValue([]);

        const { view } = render();

        expect(view.querySelector('.framework-detail')).toBeNull();
        expect(view.querySelector('.detail-body').textContent).toBe('');
    });

    it('preserves disclosure state across component refreshes', () => {
        registry.get.mockReturnValue({
            getDisplayName: () => 'search',
            renderCard: () => {
                const wrapper = document.createElement('div');
                const group = document.createElement('details');
                group.className = 'group';
                group.dataset.group = 'values';
                group.open = true;
                group.append(document.createElement('summary'), document.createElement('div'));
                wrapper.append(group);
                return wrapper;
            },
        });
        const before = new Map([['stimulus', { data: {} }]]);
        const after = new Map([['stimulus', { data: { changed: true } }]]);
        const detail = new ComponentDetail(
            {
                registry,
                state,
                eventMonitor: monitor,
                relationshipEngine: relationships,
                render: (_, data, context) => registry.get().renderCard(data, context),
            },
            drill
        );
        const view = detail.render(target, before);
        view.querySelector('[data-group="values"]').open = false;
        state.maps.set(target, after);

        state.dispatchEvent(
            new CustomEvent('component-updated', { detail: { element: target, previous: before, current: after } })
        );

        expect(view.querySelector('[data-group="values"]').open).toBe(false);
        expect(view.querySelector('.activity-label')).not.toBeNull();
        detail.destroy();
    });

    it('keeps an open detail current and marks changed state', () => {
        registry.get.mockReturnValue({
            getDisplayName: () => 'search',
            renderCard: (data, context) => {
                const row = document.createElement('p');
                row.textContent = `${data.data.values.search.query}:${context.changes.has('values.search.query')}`;
                return row;
            },
        });
        const before = new Map([['stimulus', { type: 'stimulus', data: { values: { search: { query: 'old' } } } }]]);
        const after = new Map([['stimulus', { type: 'stimulus', data: { values: { search: { query: 'new' } } } }]]);
        const detail = new ComponentDetail(
            {
                registry,
                state,
                eventMonitor: monitor,
                relationshipEngine: relationships,
                render: (_, data, context) => registry.get().renderCard(data, context),
            },
            drill
        );
        const view = detail.render(target, before);
        state.maps.set(target, after);

        state.dispatchEvent(
            new CustomEvent('component-updated', { detail: { element: target, previous: before, current: after } })
        );

        expect(view.textContent).toContain('new:true');
        detail.destroy();
    });

    it('retains identity, expansion, focus and activity status when component data changes', () => {
        const label = 'a component name long enough to expand';
        registry.get.mockReturnValue({ getDisplayName: () => label, renderCard: () => makeField('value', 1) });
        const { detail, view } = render();
        document.body.append(view);
        const identity = view.querySelector('.detail-head');
        const title = identity.querySelector('h2');
        const body = view.querySelector('.detail-body');
        body.scrollTop = 40;
        const footer = view.querySelector('.activity-label');
        title.click();
        title.focus();
        monitor.listeners[0]({ target, type: 'turbo' });
        state.maps.set(target, new Map([['stimulus', { data: { query: 'changed' } }]]));

        state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: target } }));

        expect(view.querySelector('.detail-head')).toBe(identity);
        expect(view.querySelector('.detail-body')).toBe(body);
        expect(body.scrollTop).toBe(40);
        expect(view.querySelector('.activity-label')).toBe(footer);
        expect(footer.dataset.framework).toBe('turbo');
        expect(title.getAttribute('aria-expanded')).toBe('true');
        expect(document.activeElement).toBe(title);
        detail.destroy();
        view.remove();
    });

    it('refreshes component identity and outlet metadata even when component data is unchanged', () => {
        const { detail, view } = render();
        const data = new Map([['stimulus', { data: { query: 'hello' } }]]);
        state.maps.set(target, data);
        target.id = 'new-search';
        related.id = 'new-results';
        relationships.getRelatedTo.mockReturnValue([
            { source: target, target: related, type: 'outlet', label: 'search -> updated' },
        ]);

        state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: target, previous: data } }));

        expect(view.querySelector('.detail-selector').textContent).toBe('div#new-search');
        expect(view.querySelector('.relation').getAttribute('aria-label')).toBe(
            'updated outlet results-list #new-results'
        );
        detail.destroy();
    });

    it('clears changed markers without rendering or replacing focused and expanded fields', () => {
        vi.useFakeTimers();
        const renderer = vi.fn((data, context) => {
            const change = context.changes.get('props.value');
            return makeField('value', data.data.props.value, { changed: Boolean(change), previous: change?.previous });
        });
        registry.get.mockReturnValue({ getDisplayName: () => 'search', renderCard: renderer });
        const detail = new ComponentDetail(
            {
                registry,
                state,
                eventMonitor: monitor,
                relationshipEngine: relationships,
                render: (_, data, context) => renderer(data, context),
            },
            drill
        );
        const before = new Map([['stimulus', { data: { props: { value: { nested: { count: 1 } } } } }]]);
        const after = new Map([['stimulus', { data: { props: { value: { nested: { count: 2 } } } } }]]);
        const view = detail.render(target, before);
        document.body.append(view);
        state.maps.set(target, after);
        state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: target, previous: before } }));
        const field = view.querySelector('[data-changed]');
        const tree = field.querySelector('details');
        tree.open = true;
        const summary = tree.querySelector('summary');
        summary.tabIndex = 0;
        summary.focus();
        const nodes = [...view.querySelectorAll('*')];
        const create = vi.spyOn(document, 'createElement');
        renderer.mockClear();
        try {
            vi.advanceTimersByTime(1800);
            expect(field.hasAttribute('data-changed')).toBe(false);
            expect(field.hasAttribute('title')).toBe(false);
            expect(tree.open).toBe(true);
            expect(document.activeElement).toBe(summary);
            expect([...view.querySelectorAll('*')]).toEqual(nodes);
            expect(renderer).not.toHaveBeenCalled();
            expect(create).not.toHaveBeenCalled();
        } finally {
            create.mockRestore();
            detail.destroy();
            view.remove();
            vi.useRealTimers();
        }
    });

    it('releases old subscriptions and cancels pending change expiry on render and destroy', () => {
        vi.useFakeTimers();
        const renderer = vi.fn(() => makeField('value', 'content'));
        registry.get.mockReturnValue({ getDisplayName: () => 'search', renderCard: renderer });
        const { detail } = render();
        const schedule = vi.spyOn(globalThis, 'setTimeout');
        const cancel = vi.spyOn(globalThis, 'clearTimeout');
        const before = new Map([['stimulus', { data: { props: { value: 1 } } }]]);
        const after = new Map([['stimulus', { data: { props: { value: 2 } } }]]);
        state.maps.set(target, after);
        state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: target, previous: before } }));
        const expiry = schedule.mock.results[schedule.mock.calls.findIndex(([, delay]) => delay === 1800)].value;
        try {
            const view = detail.render(related, new Map([['stimulus', { data: {} }]]));
            expect(monitor.listeners).toHaveLength(1);
            expect(cancel).toHaveBeenCalledWith(expiry);
            renderer.mockClear();
            state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: target } }));
            expect(renderer).not.toHaveBeenCalled();
            state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: related } }));
            expect(renderer).toHaveBeenCalledTimes(1);
            detail.destroy();
            expect(monitor.listeners).toHaveLength(0);
            expect(detail.activityCount).toBe(0);
            renderer.mockClear();
            state.dispatchEvent(new CustomEvent('component-updated', { detail: { element: related } }));
            expect(renderer).not.toHaveBeenCalled();
            detail.render(target, before);
            expect(monitor.listeners).toHaveLength(1);
            expect(view.querySelector('.activity-label')).not.toBeNull();
        } finally {
            detail.destroy();
            schedule.mockRestore();
            cancel.mockRestore();
            vi.useRealTimers();
        }
    });
});
