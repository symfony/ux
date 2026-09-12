import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { RelationshipEngine } from '../../../src/core/relationship-engine';

// ── helpers ──────────────────────────────────────────────────────────

function createMockRegistry() {
    return {
        collectRelationships: vi.fn(() => []),
    };
}

function createMockState() {
    const events = new EventTarget();
    const dataStore = new Map();
    const trackedElements = [];

    return {
        get elements() {
            return trackedElements;
        },
        addEventListener: vi.fn((...args) => events.addEventListener(...args)),
        get: vi.fn((el) => dataStore.get(el)),
        _track(el, pluginName = 'default', data = {}) {
            if (!trackedElements.includes(el)) {
                trackedElements.push(el);
            }
            if (!dataStore.has(el)) {
                dataStore.set(el, new Map());
            }
            dataStore.get(el).set(pluginName, data);
        },
        _untrack(el) {
            const idx = trackedElements.indexOf(el);
            if (idx !== -1) trackedElements.splice(idx, 1);
            dataStore.delete(el);
        },
        _clearAll() {
            trackedElements.length = 0;
            dataStore.clear();
        },
        _emit(event) {
            events.dispatchEvent(new Event(event));
        },
    };
}

let container;
let idCounter = 0;

function createConnectedElement(tag = 'div', parent = container) {
    const el = document.createElement(tag);
    el.id = `el-${++idCounter}`;
    parent.appendChild(el);
    return el;
}

function buildTrackedTree(state) {
    const parent = createConnectedElement('div');
    const childA = createConnectedElement('span', parent);
    const childB = createConnectedElement('p', parent);
    state._track(parent, 'plug', {});
    state._track(childA, 'plug', {});
    state._track(childB, 'plug', {});
    return { parent, childA, childB };
}

// ── tests ────────────────────────────────────────────────────────────

describe('RelationshipEngine', () => {
    let engine;
    let registry;
    let state;

    beforeEach(() => {
        idCounter = 0;
        container = document.createElement('div');
        container.id = 'test-container';
        document.body.appendChild(container);
        registry = createMockRegistry();
        state = createMockState();
        engine = new RelationshipEngine(registry, state);
    });

    afterEach(() => {
        engine.destroy();
        document.body.removeChild(container);
    });

    // ── constructor ──────────────────────────────────────────────────

    describe('constructor', () => {
        it('registers listeners for all four state events', () => {
            const events = ['component-added', 'component-removed', 'component-updated', 'components-cleared'];
            for (const event of events) {
                expect(state.addEventListener).toHaveBeenCalledWith(event, expect.any(Function), {
                    signal: expect.any(AbortSignal),
                });
            }
            expect(state.addEventListener).toHaveBeenCalledTimes(4);
        });
    });

    // ── getEdges ─────────────────────────────────────────────────────

    describe('getEdges()', () => {
        it('returns empty array when no elements are tracked', () => {
            expect(engine.getEdges()).toEqual([]);
        });

        it('returns plugin-declared edges with declared: true', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'stimulus', { type: 'controller' });
            state._track(elB, 'stimulus', { type: 'controller' });

            registry.collectRelationships.mockReturnValue([
                { source: elA, target: elB, type: 'outlet', label: 'myOutlet' },
            ]);

            const edges = engine.getEdges();

            expect(
                edges.some(
                    (e) =>
                        e.source === elA &&
                        e.target === elB &&
                        e.type === 'outlet' &&
                        e.label === 'myOutlet' &&
                        e.declared === true
                )
            ).toBe(true);
        });

        it('returns DOM structural edges when a tracked child has a tracked parent', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const edges = engine.getEdges();
            const domEdges = edges.filter((e) => e.type === 'dom-parent');

            expect(domEdges).toHaveLength(1);
            expect(domEdges[0]).toMatchObject({
                source: parent,
                target: child,
                type: 'dom-parent',
                label: 'contains',
                declared: false,
            });
        });

        it('does not create DOM edge when parent is not tracked', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(child, 'plug', {});

            const edges = engine.getEdges();

            expect(edges.filter((e) => e.type === 'dom-parent')).toHaveLength(0);
        });

        it('combines plugin-declared and DOM structural edges', () => {
            const { childA, childB } = buildTrackedTree(state);
            registry.collectRelationships.mockReturnValue([
                { source: childA, target: childB, type: 'outlet', label: 'peer' },
            ]);

            const edges = engine.getEdges();
            const declared = edges.filter((e) => e.declared);
            const structural = edges.filter((e) => !e.declared);

            expect(declared.length).toBeGreaterThanOrEqual(1);
            expect(declared.some((e) => e.type === 'outlet')).toBe(true);
            expect(structural.length).toBeGreaterThanOrEqual(1);
            expect(structural.every((e) => e.type === 'dom-parent')).toBe(true);
        });

        it('returns a defensive copy (mutation does not affect internal state)', () => {
            const el = createConnectedElement();
            state._track(el, 'plug', {});

            const first = engine.getEdges();
            first.push({ source: null, target: null, type: 'bogus', label: '', declared: false });

            const second = engine.getEdges();
            expect(second.some((e) => e.type === 'bogus')).toBe(false);
        });

        it('filters out elements that are not connected to the DOM', () => {
            const detached = document.createElement('div');
            state._track(detached, 'plug', {});

            expect(engine.getEdges()).toEqual([]);
            expect(state.get).not.toHaveBeenCalledWith(detached);
        });

        it('filters out plugin edges whose source or target is disconnected', () => {
            const connected = createConnectedElement();
            const detached = document.createElement('div');
            state._track(connected, 'plug', {});

            registry.collectRelationships.mockReturnValue([
                { source: connected, target: detached, type: 'outlet', label: 'x' },
            ]);

            const edges = engine.getEdges();
            expect(edges.filter((e) => e.declared)).toHaveLength(0);
        });
    });

    // ── getRelatedTo ─────────────────────────────────────────────────

    describe('getRelatedTo()', () => {
        it('returns edges where element is the source', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const related = engine.getRelatedTo(parent);

            expect(related.length).toBeGreaterThanOrEqual(1);
            expect(related.some((e) => e.source === parent && e.target === child)).toBe(true);
        });

        it('returns edges where element is the target', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const related = engine.getRelatedTo(child);

            expect(related.length).toBeGreaterThanOrEqual(1);
            expect(related.some((e) => e.source === parent && e.target === child)).toBe(true);
        });

        it('returns both source and target edges for a middle node', () => {
            const { childA, childB } = buildTrackedTree(state);
            registry.collectRelationships.mockReturnValue([
                { source: childA, target: childB, type: 'outlet', label: 'peer' },
            ]);

            const related = engine.getRelatedTo(childA);

            expect(related.length).toBeGreaterThanOrEqual(2);
        });

        it('returns empty array for an element not in any edge', () => {
            const lone = createConnectedElement();
            state._track(lone, 'plug', {});

            expect(engine.getRelatedTo(lone)).toEqual([]);
        });

        it('returns empty array for element not tracked at all', () => {
            const outsider = createConnectedElement();
            expect(engine.getRelatedTo(outsider)).toEqual([]);
        });
    });

    // ── getEdgesBetween ──────────────────────────────────────────────

    describe('getEdgesBetween()', () => {
        it('returns edges in both directions between two elements', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'plug', {});
            state._track(elB, 'plug', {});
            registry.collectRelationships.mockReturnValue([
                { source: elA, target: elB, type: 'outlet', label: 'forward' },
                { source: elB, target: elA, type: 'event', label: 'backward' },
            ]);

            const between = engine.getEdgesBetween(elA, elB);

            expect(between).toHaveLength(2);
        });

        it('returns only edges between the specified pair, not others', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            const elC = createConnectedElement();
            state._track(elA, 'plug', {});
            state._track(elB, 'plug', {});
            state._track(elC, 'plug', {});
            registry.collectRelationships.mockReturnValue([
                { source: elA, target: elB, type: 'outlet', label: 'ab' },
                { source: elA, target: elC, type: 'outlet', label: 'ac' },
            ]);

            const between = engine.getEdgesBetween(elA, elB);

            expect(between).toHaveLength(1);
            expect(between[0].label).toBe('ab');
        });

        it('returns empty array when no edges exist between the pair', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'plug', {});
            state._track(elB, 'plug', {});

            expect(engine.getEdgesBetween(elA, elB)).toEqual([]);
        });

        it('includes DOM structural edges between parent and child', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const between = engine.getEdgesBetween(parent, child);

            expect(between.length).toBeGreaterThanOrEqual(1);
            expect(between.some((e) => e.type === 'dom-parent')).toBe(true);
        });

        it('is symmetric: getEdgesBetween(a,b) equals getEdgesBetween(b,a)', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const ab = engine.getEdgesBetween(parent, child);
            const ba = engine.getEdgesBetween(child, parent);

            expect(ab).toEqual(ba);
        });
    });

    // ── getConnectedElements ─────────────────────────────────────────

    describe('getConnectedElements()', () => {
        it('returns directly connected elements', () => {
            const { parent, childA, childB } = buildTrackedTree(state);

            const connected = engine.getConnectedElements(parent);

            expect(connected).toContain(childA);
            expect(connected).toContain(childB);
        });

        it('does not include the queried element itself', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const connected = engine.getConnectedElements(parent);

            expect(connected).not.toContain(parent);
        });

        it('returns empty collection for an unconnected element', () => {
            const lone = createConnectedElement();
            state._track(lone, 'plug', {});

            const connected = engine.getConnectedElements(lone);

            expect(connected).toHaveLength(0);
        });

        it('includes elements from plugin-declared edges', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'plug', {});
            state._track(elB, 'plug', {});
            registry.collectRelationships.mockReturnValue([{ source: elA, target: elB, type: 'outlet', label: 'x' }]);

            const connected = engine.getConnectedElements(elA);

            expect(connected).toContain(elB);
        });

        it('de-duplicates when element appears in multiple edges', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'plug', {});
            state._track(elB, 'plug', {});
            registry.collectRelationships.mockReturnValue([
                { source: elA, target: elB, type: 'outlet', label: 'first' },
                { source: elB, target: elA, type: 'event', label: 'second' },
            ]);

            const connected = engine.getConnectedElements(elA);
            const count = connected.filter((el) => el === elB).length;

            expect(count).toBe(1);
        });
    });

    // ── getEdgeTypes ─────────────────────────────────────────────────

    describe('getEdgeTypes()', () => {
        it('returns unique edge types present in the graph', () => {
            const { childA, childB } = buildTrackedTree(state);
            registry.collectRelationships.mockReturnValue([
                { source: childA, target: childB, type: 'outlet', label: 'x' },
            ]);

            const types = engine.getEdgeTypes();

            expect(types).toContain('dom-parent');
            expect(types).toContain('outlet');
        });

        it('returns empty array when no edges exist', () => {
            expect(engine.getEdgeTypes()).toEqual([]);
        });

        it('does not contain duplicates', () => {
            buildTrackedTree(state);

            const types = engine.getEdgeTypes();
            const unique = [...new Set(types)];

            expect(types).toEqual(unique);
        });
    });

    it('keeps distinct relationships between repeated elements without IDs', () => {
        const parent = createConnectedElement();
        const children = [createConnectedElement('div', parent), createConnectedElement('div', parent)];
        state._track(parent, 'plug');
        for (const child of children) {
            child.removeAttribute('id');
            child.dataset.controller = 'item';
            state._track(child, 'plug');
        }

        expect(
            engine
                .getEdges()
                .filter((edge) => edge.source === parent)
                .map((edge) => edge.target)
        ).toEqual(children);
    });

    // ── invalidate() ─────────────────────────────────────────────────

    describe('invalidate()', () => {
        it('forces rebuild on next access', () => {
            const el = createConnectedElement();
            state._track(el, 'plug', {});

            engine.getEdges();
            const callsBefore = registry.collectRelationships.mock.calls.length;

            engine.invalidate();
            engine.getEdges();

            expect(registry.collectRelationships.mock.calls.length).toBeGreaterThan(callsBefore);
        });
    });

    // ── dirty flag / caching ─────────────────────────────────────────

    describe('dirty flag and caching', () => {
        it('rebuilds only once on consecutive getEdges calls', () => {
            const el = createConnectedElement();
            state._track(el, 'plug', {});

            engine.getEdges();
            const callsAfterFirst = registry.collectRelationships.mock.calls.length;

            engine.getEdges();
            engine.getEdges();

            expect(registry.collectRelationships.mock.calls.length).toBe(callsAfterFirst);
        });

        it.each(['component-added', 'component-removed', 'component-updated', 'components-cleared'])(
            'invalidates cache when "%s" event fires',
            (event) => {
                const el = createConnectedElement();
                state._track(el, 'plug', {});

                engine.getEdges();
                const callsBefore = registry.collectRelationships.mock.calls.length;

                state._emit(event);
                engine.getEdges();

                expect(registry.collectRelationships.mock.calls.length).toBeGreaterThan(callsBefore);
            }
        );

        it('does not rebuild after invalidation until next access', () => {
            const el = createConnectedElement();
            state._track(el, 'plug', {});

            engine.getEdges();
            const callsAfterInitial = registry.collectRelationships.mock.calls.length;

            state._emit('component-added');
            expect(registry.collectRelationships.mock.calls.length).toBe(callsAfterInitial);

            engine.getEdges();
            expect(registry.collectRelationships.mock.calls.length).toBeGreaterThan(callsAfterInitial);
        });

        it('reflects new elements after invalidation', () => {
            expect(engine.getEdges()).toHaveLength(0);

            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});
            state._emit('component-added');

            const edges = engine.getEdges();
            expect(edges.length).toBeGreaterThanOrEqual(1);
            expect(edges.some((e) => e.type === 'dom-parent')).toBe(true);
        });

        it('reflects removed elements after invalidation', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});
            expect(engine.getEdges().length).toBeGreaterThanOrEqual(1);

            state._clearAll();
            state._emit('component-removed');

            expect(engine.getEdges()).toHaveLength(0);
        });
    });

    // ── destroy ──────────────────────────────────────────────────────

    describe('destroy()', () => {
        it('stops invalidating the graph after destroy', () => {
            buildTrackedTree(state);
            engine.getEdges();
            engine.destroy();
            for (const event of ['component-added', 'component-updated', 'component-removed', 'components-cleared']) {
                state._emit(event);
            }
            expect(engine.getEdges()).toEqual([]);
        });

        it('clears edges so they are empty after destroy', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});
            engine.getEdges();

            engine.destroy();

            expect(engine.getEdges()).toEqual([]);
        });

        it('listeners are actually unsubscribed (events after destroy do not invalidate)', () => {
            const el = createConnectedElement();
            state._track(el, 'plug', {});

            engine.getEdges();
            const callsAfterBuild = registry.collectRelationships.mock.calls.length;

            engine.destroy();

            state._emit('component-added');
            state._emit('component-removed');
            state._emit('component-updated');
            state._emit('components-cleared');

            expect(registry.collectRelationships.mock.calls.length).toBe(callsAfterBuild);
        });
    });

    // ── DOM structural edge scenarios ────────────────────────────────

    describe('DOM structural edges', () => {
        it('creates edges for deeply nested tracked elements', () => {
            const grandparent = createConnectedElement();
            const parent = createConnectedElement('div', grandparent);
            const child = createConnectedElement('div', parent);
            state._track(grandparent, 'plug', {});
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const edges = engine.getEdges();
            const domEdges = edges.filter((e) => e.type === 'dom-parent');

            expect(domEdges).toHaveLength(2);
            expect(domEdges).toContainEqual(
                expect.objectContaining({
                    source: grandparent,
                    target: parent,
                })
            );
            expect(domEdges).toContainEqual(
                expect.objectContaining({
                    source: parent,
                    target: child,
                })
            );
        });

        it('finds nearest tracked ancestor when intermediate nodes are untracked', () => {
            const grandparent = createConnectedElement();
            const middle = createConnectedElement('div', grandparent);
            const child = createConnectedElement('div', middle);
            state._track(grandparent, 'plug', {});
            state._track(child, 'plug', {});

            const edges = engine.getEdges();
            const domEdges = edges.filter((e) => e.type === 'dom-parent');

            expect(domEdges).toHaveLength(1);
            expect(domEdges[0]).toMatchObject({
                source: grandparent,
                target: child,
            });
        });

        it('handles siblings correctly (each points to same parent)', () => {
            const { parent } = buildTrackedTree(state);

            const edges = engine.getEdges();
            const domEdges = edges.filter((e) => e.type === 'dom-parent');

            expect(domEdges).toHaveLength(2);
            expect(domEdges.every((e) => e.source === parent)).toBe(true);
        });

        it('does not create edge for root elements with no tracked parent', () => {
            const root = createConnectedElement();
            state._track(root, 'plug', {});

            const domEdges = engine.getEdges().filter((e) => e.type === 'dom-parent');

            expect(domEdges).toHaveLength(0);
        });

        it('marks all DOM edges as declared: false', () => {
            const parent = createConnectedElement();
            const child = createConnectedElement('span', parent);
            state._track(parent, 'plug', {});
            state._track(child, 'plug', {});

            const domEdges = engine.getEdges().filter((e) => e.type === 'dom-parent');

            expect(domEdges.every((e) => e.declared === false)).toBe(true);
        });

        it('marks all DOM edges with type "dom-parent" and label "contains"', () => {
            buildTrackedTree(state);

            const domEdges = engine.getEdges().filter((e) => !e.declared);

            for (const edge of domEdges) {
                expect(edge.type).toBe('dom-parent');
                expect(edge.label).toBe('contains');
            }
        });
    });

    // ── deduplication ────────────────────────────────────────────────

    describe('edge deduplication', () => {
        it('deduplicates edges with same source, target, and type', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'plug1', {});
            state._track(elA, 'plug2', {});
            state._track(elB, 'plug', {});

            registry.collectRelationships.mockReturnValue([
                { source: elA, target: elB, type: 'outlet', label: 'same' },
            ]);

            const edges = engine.getEdges();
            const outletEdges = edges.filter((e) => e.type === 'outlet');

            expect(outletEdges).toHaveLength(1);
        });
    });

    // ── edge cases ───────────────────────────────────────────────────

    describe('edge cases', () => {
        it('handles plugin returning empty relationships array', () => {
            registry.collectRelationships.mockReturnValue([]);
            const el = createConnectedElement();
            state._track(el, 'plug', {});

            const edges = engine.getEdges().filter((e) => e.declared);
            expect(edges).toHaveLength(0);
        });

        it('calls collectRelationships with the matching plugin data', () => {
            const el = createConnectedElement();
            const pluginData = { type: 'controller', name: 'hello' };
            state._track(el, 'stimulus', pluginData);

            engine.getEdges();

            expect(registry.collectRelationships).toHaveBeenCalledWith(el, pluginData, 'stimulus');
        });

        it('calls collectRelationships once per plugin entry per element', () => {
            const el = createConnectedElement();
            state._track(el, 'stimulus', { a: 1 });
            state._track(el, 'turbo', { b: 2 });

            engine.getEdges();

            expect(registry.collectRelationships).toHaveBeenCalledTimes(2);
        });

        it('skips elements where state.get() returns undefined', () => {
            const el = createConnectedElement();
            state.elements.push(el);
            state.get.mockReturnValueOnce(undefined);

            expect(() => engine.getEdges()).not.toThrow();
        });

        it('handles multiple plugin edges for the same pair with different types', () => {
            const elA = createConnectedElement();
            const elB = createConnectedElement();
            state._track(elA, 'plug', {});
            state._track(elB, 'plug', {});
            registry.collectRelationships.mockReturnValue([
                { source: elA, target: elB, type: 'outlet', label: 'first' },
                { source: elA, target: elB, type: 'target', label: 'second' },
            ]);

            const edges = engine.getEdges();
            const declared = edges.filter((e) => e.declared);

            expect(declared).toHaveLength(2);
        });
    });
});
