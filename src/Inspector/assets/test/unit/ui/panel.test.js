import { renderComponent } from '../../../src/ui/render-component';
vi.mock('../../../src/ui/render-component', () => ({ renderComponent: vi.fn() }));
import { projectActivity } from '../../../src/core/activity-projector';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Panel } from '../../../src/ui/panel';
import { StateManager } from '../../../src/core/state-manager';

describe('Panel', () => {
    let state, registry, monitor, timeline, host, panel, target;

    beforeEach(() => {
        state = new StateManager();
        target = document.createElement('div');
        target.id = 'search';
        document.body.appendChild(target);
        registry = {
            get: vi.fn(() => ({
                getDisplayName: () => 'search',
                renderCard: () => document.createElement('p'),
            })),
        };
        renderComponent.mockImplementation((name, data, context) => registry.get(name).renderCard(data, context));
        monitor = {
            entries: [],
            listeners: [],
            getEntriesForElement: vi.fn(),
            addListener(listener) {
                this.listeners.push(listener);
            },
            removeListener(listener) {
                this.listeners = this.listeners.filter((item) => item !== listener);
            },
        };
        monitor.getEntriesForElement = (element) => monitor.entries.filter((entry) => entry.target === element);
        monitor.project = (element) =>
            projectActivity(element ? monitor.getEntriesForElement(element) : monitor.entries);
        timeline = {
            element: document.createElement('div'),
            paused: false,
            refresh: vi.fn(),
            clear: vi.fn(),
            pause: vi.fn(),
            resume: vi.fn(),
            configure: vi.fn(),
            expandFirstVisible: vi.fn(),
            copySelected: vi.fn().mockResolvedValue(true),
        };
        host = {
            open: vi.fn(),
            close: vi.fn(),
            isOpen: false,
            getAttribute: vi.fn(() => 'right'),
            setPanelWidth: vi.fn((width) => Math.max(208, width)),
        };
        panel = new Panel(
            state,
            registry,
            monitor,
            timeline,
            host,
            { getRelatedTo: () => [] },
            {
                stimulus: true,
                livecomponent: false,
                turbo: true,
            }
        );
    });

    it('has centered identity, navigation actions, and a minimal status footer', () => {
        expect(panel.element.querySelector('.tabs')).toBeNull();
        expect(panel.element.textContent).not.toContain('Server');
        expect(panel.element.querySelector('.spacer')).toBeNull();
        expect(panel.element.querySelector('header strong').textContent).toBe('UX Inspector');
        expect(panel.element.querySelector('header [data-action="target"]').textContent).toBe('');
        expect(panel.element.querySelector('header [data-action="target"]').getAttribute('aria-label')).toBe(
            'Inspect page components'
        );
        expect(panel.element.querySelector('header [data-action="overlay"]').getAttribute('aria-label')).toBe(
            'Show all components'
        );
        expect(panel.element.querySelector('header [data-action="activity"]').getAttribute('aria-label')).toBe(
            'Show activity'
        );
        expect(panel.element.querySelector('header [data-action="activity"] b').textContent).toBe('0');
        expect(panel.element.querySelector('header [data-action="activity"] b').hidden).toBe(true);
        expect([...panel.element.querySelector('footer').children].map((child) => child.className)).toEqual([
            'monitor-status',
        ]);
        expect(panel.element.querySelector('.settings-panel')).toBeNull();
        expect(panel.element.querySelector('[aria-label="Hide inspector"]')).not.toBeNull();
        expect(panel.element.querySelector('footer').textContent).not.toMatch(/components|events/);
    });

    it('puts search below the framework filters without a redundant rescan action', () => {
        const filters = panel.element.querySelector('.filters');
        expect(filters.firstElementChild.className).toBe('filter-list');
        expect(filters.lastElementChild).toBe(panel.element.querySelector('[aria-label="Find a component"]'));
        expect(filters.querySelector('[aria-label="Rescan components"]')).toBeNull();
        expect(panel.element.querySelector('.toolbar--components')).toBeNull();
        expect(panel.element.querySelector('.toolbar--activity').hidden).toBe(true);
        expect(panel.element.querySelectorAll('.activity-filter-list .filter')).toHaveLength(3);
    });

    it('mounts Activity as a single inline disclosure surface', () => {
        expect(panel.element.querySelector('#panel-activity').firstElementChild).toBe(timeline.element);
        expect(panel.element.querySelector('.split-view')).toBeNull();
        expect(panel.element.querySelector('#panel-activity [role="separator"]')).toBeNull();
        expect(panel.element.querySelector('.panel-resize').getAttribute('aria-orientation')).toBe('vertical');
    });

    it('reports target selection mode without changing the button label', () => {
        panel.setTargetModeActive(true);
        expect(panel.element.querySelector('[data-action="target"]').textContent).toBe('');
        expect(panel.element.querySelector('[data-action="target"]').getAttribute('aria-pressed')).toBe('true');
        expect(panel.element.querySelector('[data-action="target"]').getAttribute('aria-label')).toBe(
            'Stop inspecting'
        );
        expect(panel.element.querySelector('[data-action="target"]').title).toContain('Shift-click');
        panel.setTargetModeActive(false);
        expect(panel.element.querySelector('[data-action="target"]').getAttribute('aria-label')).toBe(
            'Inspect page components'
        );
    });

    it('explains active page modes through the live monitor status', () => {
        const status = panel.element.querySelector('.monitor-status');
        panel.setTargetModeActive(true);
        expect(status.textContent).toBe('Inspecting');
        expect(status.classList.contains('inspecting')).toBe(true);

        panel.setOverlayActive(true);
        expect(status.textContent).toContain('Overlay enabled');

        panel.setTargetModeActive(false);
        panel.setOverlayActive(false);
        expect(status.textContent).toContain('Watching');
        expect(status.classList.contains('inspecting')).toBe(false);
    });

    it('routes page inspection actions', () => {
        const actions = { target: vi.fn(), overlay: vi.fn() };
        panel.setActionCallbacks(actions);
        panel.element.querySelector('[data-action="target"]').click();
        panel.element.querySelector('[data-action="overlay"]').click();
        expect(actions.target).toHaveBeenCalledOnce();
        expect(actions.overlay).toHaveBeenCalledOnce();
    });

    it('uses the header Activity action to switch views', () => {
        const activity = panel.element.querySelector('[data-action="activity"]');
        activity.click();
        expect(activity.getAttribute('aria-pressed')).toBe('true');
        expect(activity.getAttribute('aria-label')).toBe('Show components');
        expect(panel.element.querySelector('#panel-components').hidden).toBe(true);
        expect(panel.element.querySelector('.toolbar--activity').hidden).toBe(false);
        expect(panel.element.querySelector('.filters').hidden).toBe(true);
        expect(timeline.refresh).toHaveBeenCalledOnce();

        activity.click();
        expect(activity.getAttribute('aria-pressed')).toBe('false');
        expect(panel.element.querySelector('#panel-components').hidden).toBe(false);
    });

    it('uses the same header Activity action as a global route inside a component', async () => {
        monitor.entries.push({ time: 100, type: 'stimulus', event: 'connect', target });
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.textContent).toContain('search'));
        panel.drillInto(target);
        const detail = panel.element.querySelector('.detail');

        const activity = panel.element.querySelector('[data-action="activity"]');
        expect(activity.getAttribute('aria-label')).toBe('Show activity, 1 activity');
        expect(activity.querySelector('b').textContent).toBe('1');
        expect(activity.querySelector('b').hidden).toBe(false);
        expect(panel.element.querySelector('.activity-label')).not.toBeNull();

        activity.click();

        expect(activity.getAttribute('aria-pressed')).toBe('true');
        expect(activity.getAttribute('aria-label')).toBe('Show components, 1 activity');
        expect(panel.element.querySelector('#panel-components').hidden).toBe(true);
        expect(panel.element.querySelector('#panel-activity').hidden).toBe(false);

        activity.click();
        expect(panel.element.querySelector('#panel-components').hidden).toBe(false);
        expect(panel.element.querySelector('.detail')).toBe(detail);
        expect(timeline.element.parentElement).toBe(panel.element.querySelector('.drawer'));
        expect(timeline.configure).toHaveBeenLastCalledWith({
            contextual: true,
            frameworks: null,
            element: target,
            query: '',
        });
    });

    it('restores parent Activity and its manual height without reopening drawers during a push or in the journal', () => {
        state.set(target, 'stimulus', { data: {} });
        panel.drillInto(target);
        const detail = panel.element.querySelector('.detail');
        const drawer = panel.element.querySelector('.drawer');
        Object.defineProperty(drawer.parentElement, 'clientHeight', { configurable: true, value: 600 });
        drawer.getBoundingClientRect = () => ({ height: 300 });
        drawer.querySelector('.drawer-resize').dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowUp' }));
        expect(drawer.style.height).toBe('316px');
        const child = document.createElement('div');
        child.id = 'child';
        document.body.append(child);
        state.set(child, 'stimulus', { data: {} });
        timeline.configure.mockClear();
        panel.drillInto(child);
        expect(timeline.configure.mock.calls.filter(([options]) => options.contextual)).toEqual([
            [{ contextual: true, frameworks: null, element: child, query: '' }],
        ]);
        panel.drillBack();
        expect(panel.element.querySelector('.detail')).toBe(detail);
        expect(panel.element.querySelector('.drawer').style.height).toBe('316px');
        expect(timeline.configure).toHaveBeenLastCalledWith({
            contextual: true,
            frameworks: null,
            element: target,
            query: '',
        });
        panel.drillInto(child);
        const toggle = panel.element.querySelector('[data-action="activity"]');
        toggle.click();
        timeline.configure.mockClear();
        panel.drillBack();
        expect(panel.element.querySelector('.drawer')).toBeNull();
        expect(timeline.element.parentElement.id).toBe('panel-activity');
        expect(timeline.configure).not.toHaveBeenCalled();
        toggle.click();
        expect(panel.element.querySelector('.detail')).toBe(detail);
        expect(panel.element.querySelector('.drawer').style.height).toBe('316px');
        expect(panel.element.querySelector('.drawer').hasAttribute('data-sized')).toBe(true);
        expect(timeline.element.parentElement).toBe(panel.element.querySelector('.drawer'));
    });

    it('resets both the global Activity search input and its query when reopening Activity', () => {
        const toggle = panel.element.querySelector('[data-action="activity"]');
        toggle.click();
        const search = panel.element.querySelector('[aria-label="Filter activity"]');
        search.value = 'impossible';
        search.dispatchEvent(new Event('input'));
        expect(timeline.configure).toHaveBeenLastCalledWith({ query: 'impossible' });
        toggle.click();
        toggle.click();
        expect(search.value).toBe('');
        expect(timeline.configure).toHaveBeenLastCalledWith({ query: '' });
    });

    it('keeps component Activity open without a Hide control', async () => {
        monitor.entries.push({ time: 100, type: 'stimulus', event: 'connect', target });
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.textContent).toContain('search'));
        panel.drillInto(target);
        await vi.waitFor(() => expect(panel.element.querySelector('.drawer')).not.toBeNull());

        const drawer = panel.element.querySelector('.drawer');
        expect(panel.element.querySelector('#panel-components').hidden).toBe(false);
        expect(panel.element.querySelector('#panel-activity').hidden).toBe(true);
        expect(timeline.element.parentElement).toBe(drawer);
        expect(panel.element.querySelector('.activity-label').textContent).toBe('Activity');
        expect(panel.element.querySelector('.detail__activity-link')).toBeNull();
        expect(timeline.configure).toHaveBeenCalledWith({
            contextual: true,
            frameworks: null,
            element: target,
            query: '',
        });
        expect(timeline.expandFirstVisible).toHaveBeenCalledOnce();
        expect(drawer.querySelector('.detail__activity-all')).toBeNull();

        panel.element.querySelector('.activity-label').click();

        expect(panel.element.querySelector('.drawer')).not.toBeNull();
        panel.drillInto(target);
        expect(panel.element.querySelector('.drawer')).not.toBeNull();
    });

    it('exposes a top resize handle for contextual Activity', async () => {
        monitor.entries.push({ time: 100, type: 'stimulus', event: 'connect', target });
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.textContent).toContain('search'));
        panel.drillInto(target);

        const drawer = panel.element.querySelector('.drawer');
        const handle = drawer.querySelector('.drawer-resize');
        Object.defineProperty(drawer.parentElement, 'clientHeight', { configurable: true, value: 600 });
        drawer.getBoundingClientRect = vi.fn(() => ({ height: Number.parseFloat(drawer.style.height) || 300 }));

        expect(handle.getAttribute('role')).toBe('separator');
        expect(handle.getAttribute('aria-orientation')).toBe('horizontal');
        expect(handle.tabIndex).toBe(0);

        handle.dispatchEvent(
            new MouseEvent('pointerdown', {
                bubbles: true,
                cancelable: true,
                button: 0,
                clientY: 300,
            })
        );
        expect(drawer.hasAttribute('data-resizing')).toBe(true);
        handle.dispatchEvent(new MouseEvent('pointermove', { bubbles: true, clientY: 180 }));
        expect(drawer.style.height).toBe('420px');
        handle.dispatchEvent(new MouseEvent('pointerup', { bubbles: true }));
        expect(drawer.hasAttribute('data-resizing')).toBe(false);

        drawer.getBoundingClientRect.mockReturnValue({ height: 420 });
        handle.dispatchEvent(new KeyboardEvent('keydown', { bubbles: true, key: 'ArrowDown' }));
        expect(drawer.style.height).toBe('404px');
    });

    it('navigates directly to log, components and search', () => {
        document.body.appendChild(panel.element);
        panel.navigate('log');
        expect(panel.element.querySelector('[data-action="activity"]').getAttribute('aria-pressed')).toBe('true');
        panel.navigate('components');
        expect(panel.element.querySelector('[data-action="activity"]').getAttribute('aria-pressed')).toBe('false');
        panel.navigate('search');
        expect(document.activeElement).toBe(panel.element.querySelector('[aria-label="Find a component"]'));
        expect(host.open).toHaveBeenCalledTimes(3);
    });

    it('keeps pause and clear out of the visible Activity toolbar', () => {
        panel.setLogPaused(true);
        expect(panel.element.querySelector('[data-action="pause"]')).toBeNull();
        expect(panel.element.querySelector('[data-action="clear"]')).toBeNull();
        expect(panel.element.querySelector('.monitor-status').textContent).toContain('Activity paused');
        panel.setLogPaused(false);
        expect(panel.element.querySelector('.monitor-status').textContent).toContain('Watching');
    });

    it('filters the global activity from the Activity toolbar', () => {
        panel.element.querySelector('[data-action="activity"]').click();
        const search = panel.element.querySelector('[aria-label="Filter activity"]');
        search.value = 'live request';
        search.dispatchEvent(new Event('input'));

        expect(timeline.configure).toHaveBeenCalledWith({ query: 'live request' });
    });

    it('filters Activity by framework and exposes per-framework event counts', () => {
        monitor.entries.push(
            { type: 'stimulus', event: 'connect', target },
            { type: 'turbo', event: 'turbo:load', target: null },
            { type: 'turbo', event: 'turbo:render', target: null }
        );
        monitor.listeners.forEach((listener) => listener(monitor.entries.at(-1)));
        panel.refresh();

        const stimulus = panel.element.querySelector('.activity-filter-list [data-framework="stimulus"]');
        const turbo = panel.element.querySelector('.activity-filter-list [data-framework="turbo"]');
        expect(stimulus.querySelector('b').textContent).toBe('1');
        expect(turbo.querySelector('b').textContent).toBe('2');

        turbo.click();
        expect(turbo.getAttribute('aria-pressed')).toBe('false');
        expect(timeline.configure).toHaveBeenCalledWith({ frameworks: expect.any(Set) });
        expect([...timeline.configure.mock.calls.at(-1)[0].frameworks]).toEqual(['stimulus', 'livecomponent']);
    });

    it('keeps component and activity framework filters independent', () => {
        const component = panel.element.querySelector('.filters [data-framework="stimulus"]');
        const activity = panel.element.querySelector('.activity-filter-list [data-framework="stimulus"]');
        component.click();
        expect(component.getAttribute('aria-pressed')).toBe('false');
        expect(activity.getAttribute('aria-pressed')).toBe('true');
        expect(timeline.configure).not.toHaveBeenCalled();
        activity.click();
        expect(activity.getAttribute('aria-pressed')).toBe('false');
        component.click();
        expect(component.getAttribute('aria-pressed')).toBe('true');
        expect(activity.getAttribute('aria-pressed')).toBe('false');
        expect([...timeline.configure.mock.calls.at(-1)[0].frameworks]).toEqual(['livecomponent', 'turbo']);
    });

    it('counts a paired Turbo fetch as one activity instead of two raw hooks', () => {
        monitor.entries.push(
            {
                type: 'turbo',
                event: 'turbo:before-fetch-request',
                time: 10,
                target,
                detail: { url: '/live', fetchOptions: { method: 'GET', headers: { 'X-Sec-Purpose': 'prefetch' } } },
            },
            {
                type: 'turbo',
                event: 'turbo:before-fetch-response',
                time: 20,
                target,
                detail: { fetchResponse: { response: { url: '/live', status: 200 } } },
            }
        );
        monitor.listeners.forEach((listener) => listener(monitor.entries.at(-1)));
        panel.refresh();

        const activity = panel.element.querySelector('[data-action="activity"]');
        const turbo = panel.element.querySelector('.activity-filter-list [data-framework="turbo"]');
        expect(activity.querySelector('b').textContent).toBe('1');
        expect(activity.getAttribute('aria-label')).toBe('Show activity, 1 activity');
        expect(turbo.querySelector('b').textContent).toBe('1');
        expect(turbo.title).toBe('1 Turbo activity');
    });

    it('shows detected component identity and updates counts', async () => {
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.textContent).toContain('search'));
        expect(panel.element.querySelector('.stack-title').textContent).toBe('Components (1)');
        const filter = panel.element.querySelector('.filters [data-framework="stimulus"]');
        expect(filter.querySelector('b').textContent).toBe('1');
        expect(filter.title).toBe('1 detected on this page');
    });

    it('preserves unaffected component rows when one controller updates', async () => {
        const second = document.createElement('div');
        second.id = 'second';
        document.body.append(second, panel.element);
        state.set(target, 'stimulus', { data: { value: 0 } });
        state.set(second, 'stimulus', { data: { value: 0 } });
        await vi.waitFor(() => expect(panel.element.querySelectorAll('.component')).toHaveLength(2));
        const before = [...panel.element.querySelectorAll('.component')];

        state.set(target, 'stimulus', { data: { value: 1 } });

        await vi.waitFor(() => expect(panel.element.querySelectorAll('.component')).toHaveLength(2));
        expect([...panel.element.querySelectorAll('.component')]).toEqual(before);
    });

    it('moves component focus with vertical arrow keys without selecting', async () => {
        const second = document.createElement('div');
        second.id = 'second';
        document.body.append(second, panel.element);
        state.set(target, 'stimulus', { data: {} });
        state.set(second, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.querySelectorAll('.component-row')).toHaveLength(2));
        const rows = [...panel.element.querySelectorAll('.component-row')];

        rows[0].focus();
        rows[0].dispatchEvent(new KeyboardEvent('keydown', { key: 'ArrowDown', bubbles: true }));

        expect(document.activeElement).toBe(rows[1]);
        expect(rows[1].getAttribute('aria-current')).toBeNull();
        expect(rows[0].getAttribute('aria-current')).toBeNull();
    });

    it('distinguishes a package that is not installed from zero detected', () => {
        const filter = panel.element.querySelector('.filters [data-framework="livecomponent"]');
        expect(filter.classList.contains('unavailable')).toBe(true);
        expect(filter.dataset.status).toBe('not-installed');
        expect(filter.querySelector('b').textContent).toBe('-');
        expect(filter.disabled).toBe(false);
        expect(filter.title).toBe('Live package not installed');
    });

    it('pushes component details over the list and returns to it', async () => {
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.querySelector('.component-row')).not.toBeNull());
        panel.element.querySelector('.component-row').click();
        await vi.waitFor(() => expect(panel.element.querySelector('.detail')).not.toBeNull());
        expect(panel.element.querySelector('.components').hidden).toBe(false);
        expect(panel.element.querySelector('#panel-components .stack-page').hidden).toBe(true);
        expect(panel.element.querySelector('.filters').hidden).toBe(true);
        expect(panel.element.querySelector('.component').classList.contains('selected')).toBe(true);
        expect(panel.element.querySelector('.detail h2').textContent).toBe('search');
        expect(panel.element.querySelector('.detail-selector').textContent).toBe('div#search');
        expect(panel.drillBack()).toBe(true);
        expect(panel.element.querySelector('.detail')).toBeNull();
        expect(panel.element.querySelector('#panel-components .stack-page').hidden).toBe(false);
        expect(panel.element.querySelector('.filters').hidden).toBe(false);
        expect(panel.element.querySelector('.recent-components')).toBeNull();
        panel.element.querySelector('.component-row').click();
        expect(panel.element.querySelector('.detail-selector').textContent).toBe('div#search');
    });

    it('closes stale details for navigation and restores their UI state for the same component', () => {
        registry.get.mockReturnValue({
            getDisplayName: () => 'search',
            renderCard: () => {
                const container = document.createElement('div');
                const group = document.createElement('details');
                group.className = 'group';
                group.dataset.group = 'stimulus-values';
                group.open = true;
                group.append(document.createElement('summary'), document.createElement('div'));
                container.append(group);
                return container;
            },
        });
        monitor.entries.push({ time: 100, event: 'stimulus:connect', target });
        state.set(target, 'stimulus', { data: {} });
        panel.drillInto(target);
        panel.element.querySelector('[data-group="stimulus-values"]').open = false;

        panel.suspendForNavigation();
        expect(panel.element.querySelector('.detail')).toBeNull();
        expect(panel.element.querySelector('#panel-components .stack-page').hidden).toBe(false);
        target.remove();
        state.clear();

        const replacement = document.createElement('div');
        replacement.id = 'search-after-navigation';
        document.body.appendChild(replacement);
        state.set(replacement, 'stimulus', { data: {} });
        panel.drillInto(replacement);

        expect(panel.element.querySelector('[data-group="stimulus-values"]').open).toBe(false);
        expect(panel.element.querySelector('.component-events__search')).toBeNull();
    });

    it('closes an open detail when its component disappears without a replacement', async () => {
        state.set(target, 'stimulus', { data: {} });
        const sibling = document.createElement('div');
        sibling.id = 'other-search';
        document.body.appendChild(sibling);
        state.set(sibling, 'stimulus', { data: {} });
        panel.drillInto(target);

        target.remove();
        state.remove(target);
        await Promise.resolve();

        expect(panel.element.querySelector('.detail')).toBeNull();
        expect(panel.element.querySelector('#panel-components .stack-page').hidden).toBe(false);
    });

    it('rebinds an open detail when the component root is replaced', async () => {
        state.set(target, 'stimulus', { data: { value: 'before' } });
        panel.drillInto(target);

        target.remove();
        state.remove(target);
        const replacement = document.createElement('div');
        replacement.id = 'search';
        document.body.appendChild(replacement);
        state.set(replacement, 'stimulus', { data: { value: 'after' } });
        await Promise.resolve();

        expect(panel.element.querySelector('.detail')).not.toBeNull();
        expect(panel.element.querySelector('.detail-selector').textContent).toBe('div#search');
        await vi.waitFor(() => expect(panel.element.querySelector('.component')).not.toBeNull());
        expect(panel.element.querySelector('.component').classList.contains('selected')).toBe(true);
    });

    it('restores the same logical component detail after Turbo navigation', () => {
        state.set(target, 'stimulus', { data: {} });
        panel.drillInto(target);
        panel.suspendForNavigation();
        state.clear();

        const replacement = document.createElement('div');
        replacement.id = 'search';
        document.body.appendChild(replacement);
        state.set(replacement, 'stimulus', { data: {} });
        panel.resumeAfterNavigation();

        expect(panel.element.querySelector('.detail')).not.toBeNull();
        expect(panel.element.querySelector('.detail-selector').textContent).toBe('div#search');
    });

    it.each(['pointerover', 'focusin'])('previews a component on %s', async (eventName) => {
        const preview = vi.fn();
        panel.setVisualCallbacks({ onPreview: preview, onClearPreview: vi.fn() });
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.querySelector('.component')).not.toBeNull());
        const subject =
            eventName === 'focusin'
                ? panel.element.querySelector('.component-row')
                : panel.element.querySelector('.component');
        subject.dispatchEvent(new Event(eventName, { bubbles: true }));
        expect(preview.mock.calls[0][0].element).toBe(target);
    });

    it('updates the affected component row when an event is captured', async () => {
        state.set(target, 'stimulus', { data: {} });
        await vi.waitFor(() => expect(panel.element.querySelector('.component-row')).not.toBeNull());
        const entry = { event: 'search:submit', label: 'submit → run()', target };
        monitor.entries.push(entry);
        monitor.listeners.forEach((listener) => listener(entry));
        panel.refresh();
        expect(panel.element.querySelector('.activity').textContent).toBe('1');
        expect(panel.element.querySelector('.activity').title).toContain('Latest: submit → run()');
    });
});
