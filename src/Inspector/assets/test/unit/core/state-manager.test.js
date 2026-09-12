import { describe, it, expect, beforeEach } from 'vitest';
import { StateManager } from '../../../src/core/state-manager';

describe('StateManager', () => {
    let state;

    beforeEach(() => {
        state = new StateManager();
    });

    describe('set()', () => {
        it('stores data and dispatches component-added for new elements', () => {
            const el = document.createElement('div');
            const data = { type: 'test', element: el, data: {} };
            let fired = null;
            state.addEventListener('component-added', (e) => {
                fired = e.detail;
            });

            state.set(el, 'myPlugin', data);

            expect(state.get(el).get('myPlugin')).toBe(data);
            expect(fired).not.toBeNull();
            expect(fired.element).toBe(el);
            expect(fired.pluginName).toBe('myPlugin');
        });

        it('dispatches component-updated for existing elements', () => {
            const el = document.createElement('div');
            state.set(el, 'p1', { type: 'a', element: el, data: {} });

            let fired = null;
            state.addEventListener('component-updated', (e) => {
                fired = e.detail;
            });
            state.set(el, 'p2', { type: 'b', element: el, data: {} });

            expect(fired).not.toBeNull();
            expect(fired.pluginName).toBe('p2');
        });
    });

    describe('remove()', () => {
        it('deletes element and dispatches component-removed', () => {
            const el = document.createElement('div');
            state.set(el, 'p', { type: 'x', element: el, data: {} });

            let fired = false;
            state.addEventListener('component-removed', () => {
                fired = true;
            });
            state.remove(el);

            expect(state.get(el)).toBeUndefined();
            expect(fired).toBe(true);
        });

        it('no-ops for unknown elements', () => {
            let fired = false;
            state.addEventListener('component-removed', () => {
                fired = true;
            });
            state.remove(document.createElement('div'));
            expect(fired).toBe(false);
        });
    });

    describe('replace()', () => {
        it('atomically replaces stale plugin identities', () => {
            const el = document.createElement('div');
            state.set(el, 'stimulus', { type: 'stimulus' });
            const live = { type: 'livecomponent' };

            state.replace(el, new Map([['livecomponent', live]]));

            expect(state.get(el)).toEqual(new Map([['livecomponent', live]]));
        });

        it('exposes previous and current snapshots to reactive detail views', () => {
            const el = document.createElement('div');
            const before = new Map([['stimulus', { type: 'stimulus', data: { count: 1 } }]]);
            const after = new Map([['stimulus', { type: 'stimulus', data: { count: 2 } }]]);
            state.replace(el, before);
            let detail;
            state.addEventListener('component-updated', (event) => {
                detail = event.detail;
            });

            state.replace(el, after);

            expect(detail).toMatchObject({ element: el, previous: before, current: after });
        });

        it('removes the element when no plugin handles it', () => {
            const el = document.createElement('div');
            state.set(el, 'stimulus', { type: 'stimulus' });

            state.replace(el, new Map());

            expect(state.get(el)).toBeUndefined();
        });
    });

    describe('get()', () => {
        it('returns plugin data map for known element', () => {
            const el = document.createElement('div');
            state.set(el, 'plug', { type: 'test', element: el, data: {} });
            const result = state.get(el);
            expect(result).toBeInstanceOf(Map);
            expect(result.has('plug')).toBe(true);
        });

        it('returns undefined for unknown element', () => {
            expect(state.get(document.createElement('span'))).toBeUndefined();
        });
    });

    describe('elements', () => {
        it('returns all tracked elements', () => {
            const a = document.createElement('div');
            const b = document.createElement('span');
            state.set(a, 'p', { type: 'x', element: a, data: {} });
            state.set(b, 'p', { type: 'x', element: b, data: {} });
            expect(state.elements).toHaveLength(2);
            expect(state.elements).toContain(a);
            expect(state.elements).toContain(b);
        });
    });

    describe('size', () => {
        it('returns correct count', () => {
            expect(state.size).toBe(0);
            const el = document.createElement('div');
            state.set(el, 'p', { type: 'x', element: el, data: {} });
            expect(state.size).toBe(1);
        });
    });

    describe('countByPlugin()', () => {
        it('returns correct counts', () => {
            const a = document.createElement('div');
            const b = document.createElement('span');
            state.set(a, 'stimulus', { type: 'x', element: a, data: {} });
            state.set(a, 'turbo', { type: 'x', element: a, data: {} });
            state.set(b, 'stimulus', { type: 'x', element: b, data: {} });
            expect(state.countByPlugin()).toEqual({ stimulus: 2, turbo: 1 });
        });
    });

    describe('clear()', () => {
        it('removes all data and dispatches components-cleared', () => {
            const el = document.createElement('div');
            state.set(el, 'p', { type: 'x', element: el, data: {} });

            let fired = false;
            state.addEventListener('components-cleared', () => {
                fired = true;
            });
            state.clear();

            expect(state.size).toBe(0);
            expect(fired).toBe(true);
        });
    });
});
