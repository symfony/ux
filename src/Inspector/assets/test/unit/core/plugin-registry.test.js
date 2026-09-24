import { describe, it, expect, vi, beforeEach } from 'vitest';
import { PluginRegistry } from '../../../src/core/plugin-registry';

function makePlugin(overrides = {}) {
    return {
        name: 'test-plugin',
        selectors: ['.test'],
        canHandle: () => true,
        parse: () => ({}),
        getDisplayName: () => 'Test',
        ...overrides,
    };
}

describe('PluginRegistry', () => {
    let registry;

    beforeEach(() => {
        registry = new PluginRegistry();
    });

    it('caches static watched attributes', () => {
        const getWatchedAttributes = vi.fn(() => ['data-custom']);
        registry = new PluginRegistry([
            ...registry.getAll(),
            makePlugin({
                name: 'first',
                getWatchedAttributes,
                matchesAttribute: (name) => name.startsWith('data-dynamic-'),
            }),
        ]);
        expect(registry.isWatchedAttribute('data-custom')).toBe(true);
        expect(registry.isWatchedAttribute('data-dynamic-value')).toBe(true);
        expect(registry.isWatchedAttribute('class')).toBe(false);
        expect(getWatchedAttributes).toHaveBeenCalledTimes(1);
        registry.collectWatchedAttributes().push('data-external');
        expect(registry.isWatchedAttribute('data-external')).toBe(false);
    });

    describe('get()', () => {
        it('returns registered plugin by name', () => {
            const plugin = makePlugin();
            registry = new PluginRegistry([...registry.getAll(), plugin]);
            expect(registry.get('test-plugin')).toBe(plugin);
        });

        it('returns undefined for unknown name', () => {
            expect(registry.get('nope')).toBeUndefined();
        });
    });

    describe('getAll()', () => {
        it('returns all registered plugins', () => {
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'a' })]);
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'b' })]);
            const all = registry.getAll();
            expect(all).toHaveLength(2);
            expect(all.map((p) => p.name)).toEqual(['a', 'b']);
        });
    });

    describe('getForElement()', () => {
        it('filters by canHandle()', () => {
            const el = document.createElement('div');
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'yes', canHandle: () => true })]);
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'no', canHandle: () => false })]);
            const matched = registry.getForElement(el);
            expect(matched).toHaveLength(1);
            expect(matched[0].name).toBe('yes');
        });
    });

    describe('combinedSelector', () => {
        it('joins all plugin selectors', () => {
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'a', selectors: ['.a'] })]);
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'b', selectors: ['.b1', '.b2'] })]);
            expect(registry.combinedSelector).toBe('.a, .b1, .b2');
        });

        it('returns null when no plugins', () => {
            expect(registry.combinedSelector).toBeNull();
        });
    });

    describe('getForElement() classification', () => {
        it('evaluates current element attributes on every lookup', () => {
            const el = document.createElement('div');
            const canHandle = vi.fn((element) => element.hasAttribute('data-controller'));
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'spy', canHandle })]);

            expect(registry.getForElement(el)).toHaveLength(0);
            el.dataset.controller = 'hello';
            expect(registry.getForElement(el)).toHaveLength(1);
            el.removeAttribute('data-controller');
            expect(registry.getForElement(el)).toHaveLength(0);

            expect(canHandle).toHaveBeenCalledTimes(3);
        });

        it('computes separately for different elements', () => {
            const el1 = document.createElement('div');
            const el2 = document.createElement('span');
            const canHandle = vi.fn((el) => el.tagName === 'DIV');
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'div-only', canHandle })]);

            const r1 = registry.getForElement(el1);
            const r2 = registry.getForElement(el2);

            expect(r1).toHaveLength(1);
            expect(r2).toHaveLength(0);
            expect(canHandle).toHaveBeenCalledTimes(2);
        });

        it('returns empty array when no plugins match', () => {
            const el = document.createElement('div');
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'nope', canHandle: () => false })]);
            expect(registry.getForElement(el)).toEqual([]);
        });
    });

    describe('notifyEvent()', () => {
        it('calls onEvent on plugins that canHandle the element', () => {
            const el = document.createElement('div');
            const onEvent = vi.fn();
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({ name: 'listener', canHandle: () => true, onEvent }),
            ]);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({ name: 'skip', canHandle: () => false, onEvent: vi.fn() }),
            ]);

            const entry = { type: 'click' };
            registry.notifyEvent(entry, el);

            expect(onEvent).toHaveBeenCalledWith(entry, el);
            expect(registry.get('skip').onEvent).not.toHaveBeenCalled();
        });

        it('does nothing when no plugins are registered', () => {
            const el = document.createElement('div');
            expect(() => registry.notifyEvent({ type: 'click' }, el)).not.toThrow();
        });
    });

    describe('collectRelationships()', () => {
        it('merges edges from all matching plugins', () => {
            const el = document.createElement('div');
            const target = document.createElement('span');
            const edges1 = [{ source: el, target, type: 'outlet', label: 'A' }];
            const edges2 = [{ source: el, target, type: 'parent-child', label: 'B' }];

            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'p1',
                    canHandle: () => true,
                    getRelationships: () => edges1,
                }),
            ]);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'p2',
                    canHandle: () => true,
                    getRelationships: () => edges2,
                }),
            ]);

            const result = registry.collectRelationships(el, {});
            expect(result).toHaveLength(2);
            expect(result[0].label).toBe('A');
            expect(result[1].label).toBe('B');
        });

        it('calls only the plugin named by stored component data', () => {
            const el = document.createElement('div');
            const first = vi.fn(() => []);
            const second = vi.fn(() => []);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({ name: 'first', getRelationships: first }),
            ]);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({ name: 'second', getRelationships: second }),
            ]);

            registry.collectRelationships(el, { name: 'data' }, 'second');

            expect(first).not.toHaveBeenCalled();
            expect(second).toHaveBeenCalledWith(el, { name: 'data' });
        });

        it('skips plugins that cannot handle the element', () => {
            const el = document.createElement('div');
            const getRelationships = vi.fn(() => [{ source: el, target: el, type: 'x', label: 'X' }]);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({ name: 'no-match', canHandle: () => false, getRelationships }),
            ]);

            const result = registry.collectRelationships(el, {});
            expect(result).toEqual([]);
            expect(getRelationships).not.toHaveBeenCalled();
        });

        it('ignores plugins whose getRelationships returns non-array', () => {
            const el = document.createElement('div');
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'bad',
                    canHandle: () => true,
                    getRelationships: () => null,
                }),
            ]);

            const result = registry.collectRelationships(el, {});
            expect(result).toEqual([]);
        });

        it('returns empty array when no plugins registered', () => {
            const el = document.createElement('div');
            expect(registry.collectRelationships(el, {})).toEqual([]);
        });
    });

    describe('collectWatchedAttributes()', () => {
        it('merges and deduplicates attributes from all plugins', () => {
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'p1',
                    getWatchedAttributes: () => ['data-action', 'data-controller'],
                }),
            ]);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'p2',
                    getWatchedAttributes: () => ['data-controller', 'data-target'],
                }),
            ]);

            const attrs = registry.collectWatchedAttributes();
            expect(attrs).toHaveLength(3);
            expect(attrs).toContain('data-action');
            expect(attrs).toContain('data-controller');
            expect(attrs).toContain('data-target');
        });

        it('skips plugins without getWatchedAttributes', () => {
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'no-hook' })]);
            expect(registry.collectWatchedAttributes()).toEqual([]);
        });

        it('returns empty array when no plugins registered', () => {
            expect(registry.collectWatchedAttributes()).toEqual([]);
        });

        it('ignores non-array return values from getWatchedAttributes', () => {
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'bad',
                    getWatchedAttributes: () => 'not-an-array',
                }),
            ]);
            expect(registry.collectWatchedAttributes()).toEqual([]);
        });
    });

    describe('collectMonitoredEvents()', () => {
        it('collects static events from plugins', () => {
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'statics',
                    getMonitoredEvents: () => ({
                        static: ['click', 'submit'],
                    }),
                }),
            ]);

            const { staticEvents, dynamicEvents } = registry.collectMonitoredEvents();
            expect(staticEvents.get('statics')).toEqual(['click', 'submit']);
            expect(dynamicEvents.size).toBe(0);
        });

        it('collects dynamic events using elementsByPlugin map', () => {
            const el = document.createElement('div');
            el.setAttribute('data-action', 'hover->tooltip#show');
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'dynamic-p',
                    getMonitoredEvents: () => ({
                        static: [],
                        dynamic: (elements) => elements.map(() => 'hover'),
                    }),
                }),
            ]);

            const elementsByPlugin = new Map([['dynamic-p', [el]]]);
            const { staticEvents, dynamicEvents } = registry.collectMonitoredEvents(elementsByPlugin);
            expect(staticEvents.size).toBe(0);
            expect(dynamicEvents.get('dynamic-p')).toEqual(['hover']);
        });

        it('collects both static and dynamic events from same plugin', () => {
            const el = document.createElement('div');
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'both',
                    getMonitoredEvents: () => ({
                        static: ['connect'],
                        dynamic: () => ['custom:event'],
                    }),
                }),
            ]);

            const elementsByPlugin = new Map([['both', [el]]]);
            const { staticEvents, dynamicEvents } = registry.collectMonitoredEvents(elementsByPlugin);
            expect(staticEvents.get('both')).toEqual(['connect']);
            expect(dynamicEvents.get('both')).toEqual(['custom:event']);
        });

        it('handles dynamic function that throws', () => {
            const spy = vi.spyOn(console, 'warn').mockImplementation(() => {});
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'thrower',
                    getMonitoredEvents: () => ({
                        static: [],
                        dynamic: () => {
                            throw new Error('oops');
                        },
                    }),
                }),
            ]);

            const { dynamicEvents } = registry.collectMonitoredEvents();
            expect(dynamicEvents.size).toBe(0);
            expect(spy).toHaveBeenCalled();
            spy.mockRestore();
        });

        it('uses empty array when elementsByPlugin has no entry for the plugin', () => {
            const dynamicFn = vi.fn(() => []);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'lonely',
                    getMonitoredEvents: () => ({
                        static: [],
                        dynamic: dynamicFn,
                    }),
                }),
            ]);

            registry.collectMonitoredEvents(new Map());
            expect(dynamicFn).toHaveBeenCalledWith([]);
        });

        it('ignores plugins without getMonitoredEvents', () => {
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'plain' })]);
            const { staticEvents, dynamicEvents } = registry.collectMonitoredEvents();
            expect(staticEvents.size).toBe(0);
            expect(dynamicEvents.size).toBe(0);
        });

        it('skips empty static arrays', () => {
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'empty-static',
                    getMonitoredEvents: () => ({ static: [] }),
                }),
            ]);

            const { staticEvents } = registry.collectMonitoredEvents();
            expect(staticEvents.size).toBe(0);
        });

        it('keeps empty dynamic event lists so previous subscriptions can be removed', () => {
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'empty-dyn',
                    getMonitoredEvents: () => ({
                        static: [],
                        dynamic: () => [],
                    }),
                }),
            ]);

            const { dynamicEvents } = registry.collectMonitoredEvents();
            expect([...dynamicEvents.values()]).toEqual([[]]);
        });

        it('returns empty maps when no plugins registered', () => {
            const { staticEvents, dynamicEvents } = registry.collectMonitoredEvents();
            expect(staticEvents.size).toBe(0);
            expect(dynamicEvents.size).toBe(0);
        });

        it('defaults elementsByPlugin to empty map when omitted', () => {
            const dynamicFn = vi.fn(() => ['evt']);
            registry = new PluginRegistry([
                ...registry.getAll(),
                makePlugin({
                    name: 'defaults',
                    getMonitoredEvents: () => ({
                        static: [],
                        dynamic: dynamicFn,
                    }),
                }),
            ]);

            registry.collectMonitoredEvents();
            expect(dynamicFn).toHaveBeenCalledWith([]);
        });
    });

    describe('getAll() ordering', () => {
        it('sorts known plugins in priority order', () => {
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'stimulus' })]);
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'livecomponent' })]);
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'turbo' })]);
            const names = registry.getAll().map((p) => p.name);
            expect(names).toEqual(['livecomponent', 'turbo', 'stimulus']);
        });

        it('places unknown plugins after known ones', () => {
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'custom' })]);
            registry = new PluginRegistry([...registry.getAll(), makePlugin({ name: 'stimulus' })]);
            const names = registry.getAll().map((p) => p.name);
            expect(names).toEqual(['stimulus', 'custom']);
        });
    });
});
