import { afterEach, describe, expect, it, vi } from 'vitest';
import { EventMonitor } from '../../../src/core/event-monitor';
import { StateManager } from '../../../src/core/state-manager';
import { ComponentDetector } from '../../../src/core/component-detector';
import { PluginRegistry } from '../../../src/core/plugin-registry';
import { LiveComponentPlugin } from '../../../src/live-component/plugin';

const cleanups = [];
afterEach(() => {
    for (const cleanup of cleanups.splice(0)) cleanup();
    document.body.replaceChildren();
    vi.restoreAllMocks();
});

describe('Activity publication', () => {
    it('publishes normalized immutable snapshots before notifying observers', () => {
        const owner = document.createElement('div');
        const monitor = new EventMonitor(500, (draft) => {
            draft.owner = owner;
            draft.label = 'Normalized';
        });
        const detail = { nested: { value: 1 } };
        const observer = vi.fn();
        monitor.addListener(observer);
        const entry = monitor.record({ event: 'test', detail });
        detail.nested.value = 2;
        expect(entry.detail.nested.value).toBe(1);
        expect(observer).toHaveBeenCalledExactlyOnceWith(entry, []);
        expect(entry.owner).toBe(owner);
        expect(entry.label).toBe('Normalized');
        expect(Object.isFrozen(entry)).toBe(true);
        expect(Object.isFrozen(entry.detail.nested)).toBe(true);
        expect(Object.isFrozen(owner)).toBe(false);
    });

    it('isolates failing observers and preserves the delivery list during unsubscription', () => {
        const monitor = new EventMonitor();
        const warning = vi.spyOn(console, 'warn').mockImplementation(() => {});
        const second = vi.fn();
        monitor.addListener(() => {
            monitor.removeListener(second);
            throw new Error('observer failed');
        });
        monitor.addListener(second);
        expect(() => monitor.record({ event: 'test' })).not.toThrow();
        expect(second).toHaveBeenCalledOnce();
        expect(warning).toHaveBeenCalledOnce();
    });

    it('shares cached projections without overwriting the raw Live model changes', () => {
        const monitor = new EventMonitor();
        const target = document.createElement('div');
        const record = (event, detail) => monitor.record({ type: 'livecomponent', event, target, detail });
        const first = record('live:model:set', { model: 'query', value: 'a' });
        record('live:model:set', { model: 'query', value: 'ab' });
        record('live:request', { models: ['query'] });
        const rows = monitor.project(target);
        expect(monitor.project(target)[0]).toBe(rows[0]);
        expect(first.detail).toEqual({ model: 'query', value: 'a' });
        expect(rows[0].live.changes).toEqual([{ model: 'query', value: 'ab' }]);
        expect(Object.isFrozen(rows[0].live.changes[0])).toBe(true);
        const revision = monitor.revision;
        record('live:render:finished');
        expect(monitor.project(target)[0]).not.toBe(rows[0]);
        expect(monitor.project(target)[0].id).toBe(rows[0].id);
        expect(monitor.revision).toBe(revision + 1);
        monitor.clear();
        expect(monitor.project()).toEqual([]);
        expect(record('live:connect').id).toBeGreaterThan(first.id);
    });
});

describe('Detection transactions', () => {
    it('parses each component once per mutation burst and suppresses unchanged updates', async () => {
        const target = document.createElement('section');
        target.dataset.controller = 'probe';
        document.body.append(target);
        const parse = vi.fn((element) => ({ type: 'probe', element, data: { count: element.childElementCount } }));
        const registry = new PluginRegistry([
            {
                name: 'probe',
                selectors: ['[data-controller]'],
                canHandle: (el) => el.hasAttribute('data-controller'),
                parse,
                getDisplayName: () => 'Probe',
            },
        ]);
        const state = new StateManager();
        const detector = new ComponentDetector(registry, state, document.createElement('ux-inspector'));
        cleanups.push(() => detector.destroy());
        detector.scan();
        const updated = vi.fn();
        state.addEventListener('component-updated', updated);
        detector.scan();
        expect(updated).not.toHaveBeenCalled();
        detector.observe();
        parse.mockClear();
        for (let i = 0; i < 100; i++) target.append(document.createElement('span'));
        await vi.waitFor(() => expect(updated).toHaveBeenCalledOnce());
        expect(parse).toHaveBeenCalledOnce();
        expect(state.get(target).get('probe').data.count).toBe(100);
        detector.scan();
        expect(updated).toHaveBeenCalledOnce();
    });

    it('separates Live reads from subscriptions and releases hooks when observation stops', () => {
        const plugin = new LiveComponentPlugin();
        const target = document.createElement('div');
        target.dataset.controller = 'live';
        target.__component = { on: vi.fn(), off: vi.fn() };
        document.body.append(target);
        plugin.setEventRecorder(vi.fn());
        plugin.parse(target);
        expect(target.__component.on).not.toHaveBeenCalled();
        const detector = new ComponentDetector(
            new PluginRegistry([plugin]),
            new StateManager(),
            document.createElement('ux-inspector')
        );
        detector.scan();
        detector.scan();
        expect(target.__component.on).toHaveBeenCalledTimes(5);
        detector.disconnect();
        expect(target.__component.off).toHaveBeenCalledTimes(5);
        detector.scan();
        expect(target.__component.on).toHaveBeenCalledTimes(10);
        detector.destroy();
        expect(target.__component.off).toHaveBeenCalledTimes(10);
    });
});

it('uses exclusions and subscription cleanup for explicit inspection too', () => {
    const state = new StateManager();
    const plugin = {
        name: 'probe',
        selectors: ['[data-controller]'],
        canHandle: () => true,
        getDisplayName: () => 'Probe',
        observe: vi.fn(),
        onElementRemoved: vi.fn(),
        parse: vi.fn((element) => ({ type: 'probe', element, data: {} })),
    };
    const host = document.createElement('ux-inspector');
    const ignored = document.createElement('section');
    ignored.className = 'ignored';
    const target = document.createElement('div');
    target.dataset.controller = 'probe';
    ignored.append(target);
    document.body.append(host, ignored);
    const detector = new ComponentDetector(new PluginRegistry([plugin]), state, host, ['.ignored']);
    cleanups.push(() => detector.destroy());
    expect(detector.inspect(target)).toBeUndefined();
    expect(plugin.observe).not.toHaveBeenCalled();
    document.body.append(target);
    expect(detector.inspect(target).get('probe').element).toBe(target);
    const fresh = document.createElement('div');
    fresh.dataset.controller = 'probe';
    document.body.append(fresh);
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    plugin.parse.mockImplementation(() => {
        throw new Error('failed read');
    });
    expect(detector.inspect(fresh)).toBeUndefined();
    expect(plugin.onElementRemoved).toHaveBeenCalledWith(fresh);
});
