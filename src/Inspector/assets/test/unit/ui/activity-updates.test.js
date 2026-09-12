import { afterEach, describe, expect, it, vi } from 'vitest';
import { EventMonitor } from '../../../src/core/event-monitor';
import { StateManager } from '../../../src/core/state-manager';
import { Panel } from '../../../src/ui/panel';
import { Timeline } from '../../../src/ui/timeline';

const cleanups = [];
afterEach(() => {
    for (const cleanup of cleanups.splice(0)) cleanup();
    document.body.replaceChildren();
    vi.restoreAllMocks();
});

function setup() {
    const state = new StateManager();
    const monitor = new EventMonitor(2);
    const targets = ['first', 'second', 'third'].map((id) => {
        const element = document.createElement('div');
        element.id = id;
        document.body.append(element);
        state.set(element, 'stimulus', { type: 'stimulus', element, data: {} });
        return element;
    });
    const registry = {
        get: () => ({ getDisplayName: (element) => element.id }),
        collectPageRules: vi.fn(() => [
            { kind: 'disabled', label: 'Turbo disabled', element: targets[0], framework: 'turbo' },
        ]),
    };
    const timeline = new Timeline(monitor);
    const host = { open() {}, close() {}, setPanelWidth: (width) => width };
    const panel = new Panel(state, registry, monitor, timeline, host, null);
    document.body.append(panel.element);
    cleanups.push(() => {
        panel.destroy();
        timeline.destroy();
        monitor.destroy();
    });
    const count = (index) =>
        Number(panel.element.querySelectorAll('.component')[index].querySelector('.activity')?.textContent ?? 0);
    return { panel, state, monitor, targets, registry, count };
}

describe('Activity invalidation', () => {
    it('updates owners and related scopes, including scopes whose last entry expires', async () => {
        const {
            monitor,
            targets: [first, second, third],
            count,
        } = setup();
        const child = first.appendChild(document.createElement('span'));
        monitor.record({ event: 'one', target: child, owner: first, relatedElements: [second] });
        await vi.waitFor(() => expect([count(0), count(1), count(2)]).toEqual([1, 1, 0]));
        monitor.record({ event: 'two', target: third });
        monitor.record({ event: 'three', target: third });
        await vi.waitFor(() => expect([count(0), count(1), count(2)]).toEqual([0, 0, 2]));
    });

    it('does not rebuild the component list or lose a page-rule focus for an Activity burst', async () => {
        const { panel, monitor, targets, registry, count } = setup();
        const rule = panel.element.querySelector('.page-rule');
        rule.focus();
        registry.collectPageRules.mockClear();
        for (let i = 0; i < 30; i++) monitor.record({ event: `probe:${i}`, target: targets[0] });
        await vi.waitFor(() => expect(count(0)).toBe(2));
        expect(registry.collectPageRules).not.toHaveBeenCalled();
        expect(document.activeElement).toBe(rule);
    });
});

describe('Scoped Activity history', () => {
    it('keeps unaffected projections cached and returns each entry only once per scope', () => {
        const monitor = new EventMonitor(2);
        const first = document.createElement('div');
        const second = document.createElement('div');
        const entry = monitor.record({ event: 'first', target: first, owner: first, relatedElements: [first] });
        const projection = monitor.project(first);
        expect(monitor.getEntriesForElement(first)).toEqual([entry]);
        monitor.record({ event: 'second', target: second });
        expect(monitor.project(first)).toBe(projection);
        const expired = vi.fn();
        monitor.addListener(expired);
        monitor.record({ event: 'third', target: second });
        expect(expired.mock.calls[0][1]).toEqual([entry]);
        expect(monitor.getEntriesForElement(first)).toEqual([]);
        expect(monitor.project(first)).toEqual([]);
        monitor.clear();
        expect(monitor.getEntriesForElement(second)).toEqual([]);
    });
});
