/** @vitest-environment jsdom */
/**
 * Integration tests: Component detection -> state -> UI rendering pipeline.
 *
 * These tests wire together real instances of core modules
 * (PluginRegistry, StateManager, ComponentDetector) to verify
 * the end-to-end detection flow without mocks.
 */
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { PluginRegistry } from '../../src/core/plugin-registry';
import { StateManager } from '../../src/core/state-manager';
import { ComponentDetector } from '../../src/core/component-detector';
import { EventMonitor } from '../../src/core/event-monitor';
import { RelationshipEngine } from '../../src/core/relationship-engine';
import { StimulusPlugin } from '../../src/stimulus/plugin';

/** Minimal Stimulus-like plugin for integration testing */
function createStimulusLikePlugin() {
    return {
        name: 'stimulus',
        selectors: ['[data-controller]'],
        canHandle: (el) => el.hasAttribute('data-controller'),
        parse: (el) => ({
            name: el.dataset.controller,
            identifier: el.dataset.controller,
            values: {},
            targets: [],
            actions: [],
            outlets: [],
            classes: [],
        }),
        getDisplayName: (el) => el.dataset.controller,
        detect: () => document.querySelectorAll('[data-controller]'),
        describe: (el) => ({ label: el.dataset.controller }),
    };
}

/** Minimal LiveComponent-like plugin */
function createLiveLikePlugin() {
    return {
        name: 'livecomponent',
        selectors: ['[data-live-name-value]'],
        canHandle: (el) => el.hasAttribute('data-live-name-value'),
        parse: (el) => ({
            name: el.dataset.liveNameValue,
            identifier: el.dataset.liveNameValue,
            props: {},
        }),
        getDisplayName: (el) => el.dataset.liveNameValue,
        detect: () => document.querySelectorAll('[data-live-name-value]'),
        describe: (el) => ({ label: el.dataset.liveNameValue }),
    };
}

describe('Detection Pipeline Integration', () => {
    let registry, state, detector, inspectorEl;

    beforeEach(() => {
        registry = new PluginRegistry();
        state = new StateManager();
        inspectorEl = document.createElement('ux-inspector');
        document.body.appendChild(inspectorEl);
    });

    afterEach(() => {
        detector?.disconnect();
        document.body.innerHTML = '';
    });

    describe('scan detects existing DOM elements', () => {
        it('detects Stimulus controllers on scan', () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);

            const el = document.createElement('div');
            el.dataset.controller = 'counter';
            document.body.appendChild(el);

            detector.scan();

            expect(state.get(el)).toBeDefined();
            const data = state.get(el);
            expect(data.get('stimulus').name).toBe('counter');
        });

        it('detects multiple frameworks simultaneously', () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            registry = new PluginRegistry([...registry.getAll(), createLiveLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);

            const stim = document.createElement('div');
            stim.dataset.controller = 'search';
            const live = document.createElement('div');
            live.dataset.liveNameValue = 'UserProfile';
            document.body.appendChild(stim);
            document.body.appendChild(live);

            detector.scan();

            expect(state.elements.length).toBe(2);
            expect(state.get(stim).has('stimulus')).toBe(true);
            expect(state.get(live).has('livecomponent')).toBe(true);
        });

        it('ignores inspector element itself', () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);
            inspectorEl.dataset.controller = 'inspector-internal';

            detector.scan();

            expect(state.get(inspectorEl)).toBeUndefined();
        });

        it('emits component-added for each detected element', () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);

            const added = [];
            state.addEventListener('component-added', (e) => added.push(e.detail));

            const el1 = document.createElement('div');
            el1.dataset.controller = 'a';
            const el2 = document.createElement('div');
            el2.dataset.controller = 'b';
            document.body.appendChild(el1);
            document.body.appendChild(el2);

            detector.scan();

            expect(added.length).toBe(2);
            expect(added[0].data.name).toBe('a');
            expect(added[1].data.name).toBe('b');
        });

        it('rescan updates existing component data', () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);

            const el = document.createElement('div');
            el.dataset.controller = 'counter';
            document.body.appendChild(el);

            detector.scan();
            expect(state.get(el).get('stimulus').name).toBe('counter');

            el.dataset.controller = 'timer';
            detector.scan();
            expect(state.get(el).get('stimulus').name).toBe('timer');
        });
    });

    describe('MutationObserver detects dynamic additions', () => {
        it('detects elements added after start()', async () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);
            detector.observe();

            const el = document.createElement('div');
            el.dataset.controller = 'dynamic';
            document.body.appendChild(el);

            // MutationObserver is async -- wait for microtask + debounce
            await vi.waitFor(() => {
                expect(state.get(el)).toBeDefined();
            });
            expect(state.get(el).get('stimulus').name).toBe('dynamic');
        });

        it('handles nested element additions', async () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);
            detector.observe();

            const wrapper = document.createElement('div');
            const nested = document.createElement('div');
            nested.dataset.controller = 'nested';
            wrapper.appendChild(nested);
            document.body.appendChild(wrapper);

            await vi.waitFor(() => {
                expect(state.get(nested)).toBeDefined();
            });
        });

        it('refreshes outlet matches after an external target is inserted', async () => {
            registry = new PluginRegistry([...registry.getAll(), new StimulusPlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);
            const owner = document.createElement('div');
            owner.dataset.controller = 'greeter';
            owner.setAttribute('data-greeter-counter-outlet', '.counter');
            document.body.appendChild(owner);
            detector.scan();
            detector.observe();
            expect(state.get(owner).get('stimulus').data.outlets.greeter[0].elements).toHaveLength(0);

            const target = document.createElement('div');
            target.className = 'counter';
            document.body.appendChild(target);

            await vi.waitFor(() => {
                expect(state.get(owner).get('stimulus').data.outlets.greeter[0].elements).toEqual([target]);
            });
        });
    });

    describe('element with multiple frameworks', () => {
        it('element matching both plugins gets both entries', () => {
            registry = new PluginRegistry([...registry.getAll(), createStimulusLikePlugin()]);
            registry = new PluginRegistry([...registry.getAll(), createLiveLikePlugin()]);
            detector = new ComponentDetector(registry, state, inspectorEl);

            const el = document.createElement('div');
            el.dataset.controller = 'hybrid';
            el.dataset.liveNameValue = 'HybridComponent';
            document.body.appendChild(el);

            detector.scan();

            const data = state.get(el);
            expect(data.has('stimulus')).toBe(true);
            expect(data.has('livecomponent')).toBe(true);
        });
    });
});

describe('EventMonitor Integration', () => {
    let monitor;

    beforeEach(() => {
        monitor = new EventMonitor();
    });

    afterEach(() => {
        monitor.stop();
        document.body.innerHTML = '';
    });

    it('captures events after monitorEvents + start', () => {
        monitor.monitorEvents(['counter:increment'], 'stimulus');
        monitor.start();

        const el = document.createElement('div');
        document.body.appendChild(el);
        el.dispatchEvent(new CustomEvent('counter:increment', { bubbles: true }));

        const entries = monitor.entries;
        expect(entries.length).toBeGreaterThanOrEqual(1);
        const match = entries.find((e) => e.event === 'counter:increment');
        expect(match).toBeTruthy();
        expect(match.type).toBe('stimulus');
    });

    it('notifies listeners when events are captured', () => {
        monitor.monitorEvents(['counter:reset'], 'stimulus');
        monitor.start();

        const captured = [];
        monitor.addListener((entry) => captured.push(entry));

        const el = document.createElement('div');
        document.body.appendChild(el);
        el.dispatchEvent(new CustomEvent('counter:reset', { bubbles: true }));

        expect(captured.length).toBeGreaterThanOrEqual(1);
        expect(captured[0].event).toBe('counter:reset');
    });
});

describe('RelationshipEngine Integration', () => {
    let registry, state, engine;

    beforeEach(() => {
        registry = new PluginRegistry();
        state = new StateManager();
        engine = new RelationshipEngine(registry, state);
    });

    it('builds relationships between related components', () => {
        const plugin = createStimulusLikePlugin();
        plugin.getRelationships = (el) => {
            if (el.dataset.controller === 'child') {
                const parent = document.querySelector('[data-controller="parent"]');
                return parent ? [{ source: el, target: parent, type: 'controller-parent' }] : [];
            }
            return [];
        };
        registry = new PluginRegistry([...registry.getAll(), plugin]);
        engine = new RelationshipEngine(registry, state);

        const parent = document.createElement('div');
        parent.dataset.controller = 'parent';
        const child = document.createElement('div');
        child.dataset.controller = 'child';
        document.body.appendChild(parent);
        parent.appendChild(child);

        state.set(parent, 'stimulus', plugin.parse(parent));
        state.set(child, 'stimulus', plugin.parse(child));

        const edges = engine.getRelatedTo(child);
        // Should have at least the declared edge (plus possibly structural ones)
        const declared = edges.filter((e) => e.type === 'controller-parent');
        expect(declared.length).toBeGreaterThanOrEqual(1);
    });

    it('returns empty for element with no relationships', () => {
        const plugin = createStimulusLikePlugin();
        plugin.getRelationships = () => [];
        registry = new PluginRegistry([...registry.getAll(), plugin]);
        engine = new RelationshipEngine(registry, state);

        const el = document.createElement('div');
        el.dataset.controller = 'solo';
        document.body.appendChild(el);
        state.set(el, 'stimulus', plugin.parse(el));

        const edges = engine.getRelatedTo(el);
        expect(edges).toEqual([]);
    });
});
