import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { ComponentDetector } from '../../../src/core/component-detector';
import { StateManager } from '../../../src/core/state-manager';

function createMockRegistry(selector = null, plugins = []) {
    return {
        collectExternalChanges: vi.fn(() => []),
        get combinedSelector() {
            return selector;
        },
        getForElement: vi.fn(() => plugins),
        addEventListener: vi.fn(),
        removeEventListener: vi.fn(),
        collectWatchedAttributes: vi.fn(() => []),
        isWatchedAttribute: vi.fn(() => false),
        notifyElementRemoved: vi.fn(),
    };
}

function createMockState(elements = []) {
    return {
        replace: vi.fn(),
        remove: vi.fn(),
        get: vi.fn(() => null),
        notifyPageUpdated: vi.fn(),
        get elements() {
            return elements;
        },
    };
}

function createMockPlugin(name, parseResult = {}) {
    return {
        name,
        parse: vi.fn(() => parseResult),
    };
}

describe('ComponentDetector', () => {
    let inspectorEl;

    beforeEach(() => {
        inspectorEl = document.createElement('ux-inspector');
        document.body.appendChild(inspectorEl);
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    describe('scan()', () => {
        it('reads registry.combinedSelector', () => {
            const registry = createMockRegistry(null);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);
            const spy = vi.spyOn(registry, 'combinedSelector', 'get');

            detector.scan();
            expect(spy).toHaveBeenCalled();
        });

        it('does nothing when selector is empty', () => {
            const registry = createMockRegistry(null);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.scan();
            expect(state.replace).not.toHaveBeenCalled();
            expect(state.remove).not.toHaveBeenCalled();
        });

        it('finds elements matching selector and calls plugin.parse', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.scan();

            expect(registry.getForElement).toHaveBeenCalledWith(el);
            expect(plugin.parse).toHaveBeenCalledWith(el, expect.any(Function));
        });

        it('stores parsed data in state in one transition', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            const parsed = { type: 'stimulus', name: 'hello' };
            const plugin = createMockPlugin('stimulus', parsed);
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.scan();

            expect(state.replace).toHaveBeenCalledWith(el, new Map([['stimulus', parsed]]));
        });

        it('ignores elements inside the inspector element', () => {
            const inner = document.createElement('div');
            inner.setAttribute('data-controller', 'hidden');
            inspectorEl.appendChild(inner);

            const plugin = createMockPlugin('stimulus');
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.scan();

            expect(state.replace).not.toHaveBeenCalled();
        });

        it('removes stale elements from state', () => {
            const staleEl = document.createElement('div');
            // staleEl is not in the DOM, so it won't be found by querySelectorAll
            const registry = createMockRegistry('[data-controller]', []);
            const state = createMockState([staleEl]);
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.scan();

            expect(state.remove).toHaveBeenCalledWith(staleEl);
        });

        it('handles multiple plugins for the same element', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            const pluginA = createMockPlugin('stimulus', { type: 'stimulus' });
            const pluginB = createMockPlugin('turbo', { type: 'turbo' });
            const registry = createMockRegistry('[data-controller]', [pluginA, pluginB]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.scan();

            expect(state.replace).toHaveBeenCalledWith(
                el,
                new Map([
                    ['stimulus', { type: 'stimulus' }],
                    ['turbo', { type: 'turbo' }],
                ])
            );
        });

        it('skips elements matching ignore selectors', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            el.classList.add('no-inspect');
            document.body.appendChild(el);

            const plugin = createMockPlugin('stimulus');
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl, ['.no-inspect']);

            detector.scan();

            expect(state.replace).not.toHaveBeenCalled();
        });

        it('skips elements inside an ignored ancestor (closest semantics)', () => {
            const wrapper = document.createElement('div');
            wrapper.setAttribute('data-no-inspect', '');
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'nested');
            wrapper.appendChild(el);
            document.body.appendChild(wrapper);

            const plugin = createMockPlugin('stimulus');
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl, ['[data-no-inspect]']);

            detector.scan();

            expect(state.replace).not.toHaveBeenCalled();
        });

        it('does not skip elements outside ignored selectors', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            const plugin = createMockPlugin('stimulus', { ok: true });
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl, ['.no-inspect']);

            detector.scan();

            expect(state.replace).toHaveBeenCalledTimes(1);
        });

        it('warns once for an invalid ignore selector and continues', () => {
            for (let i = 0; i < 5; i++) {
                const el = document.createElement('div');
                el.setAttribute('data-controller', `hello${i}`);
                document.body.appendChild(el);
            }

            const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
            const plugin = createMockPlugin('stimulus', { ok: true });
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl, ['[[[invalid']);

            detector.scan();
            detector.scan();

            // Validated at construction: not once per element, per scan.
            expect(warnSpy).toHaveBeenCalledTimes(1);
            expect(warnSpy).toHaveBeenCalledWith(
                expect.stringContaining('Ignoring invalid "ignore_selectors" entry'),
                expect.anything()
            );
            // Elements are still detected since the bad selector is dropped.
            expect(state.replace).toHaveBeenCalledTimes(10);
            warnSpy.mockRestore();
        });

        it('reports a failing plugin once instead of on every element', () => {
            for (let i = 0; i < 4; i++) {
                const el = document.createElement('div');
                el.setAttribute('data-controller', `hello${i}`);
                document.body.appendChild(el);
            }

            const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});
            const plugin = {
                name: 'broken',
                parse: vi.fn(() => {
                    throw new Error('boom');
                }),
            };
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const detector = new ComponentDetector(registry, createMockState(), inspectorEl);

            detector.scan();
            detector.scan();

            expect(plugin.parse).toHaveBeenCalledTimes(8);
            expect(warnSpy).toHaveBeenCalledTimes(1);
            warnSpy.mockRestore();
        });
    });

    describe('observe()', () => {
        it('creates a MutationObserver', () => {
            const registry = createMockRegistry('[data-controller]', []);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.observe();

            // If observe did not throw, the observer was created. Disconnect to clean up.
            detector.disconnect();
        });

        it('is idempotent -- calling twice does not create two observers', () => {
            const registry = createMockRegistry('[data-controller]', []);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            const origObserve = MutationObserver.prototype.observe;
            let observeCount = 0;
            MutationObserver.prototype.observe = function (...args) {
                observeCount++;
                return origObserve.apply(this, args);
            };

            detector.observe();
            detector.observe();

            expect(observeCount).toBe(1);

            MutationObserver.prototype.observe = origObserve;
            detector.disconnect();
        });

        it('processes only components in an added subtree', async () => {
            const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);
            detector.observe();

            const unrelated = document.createElement('p');
            const wrapper = document.createElement('section');
            const component = document.createElement('div');
            component.setAttribute('data-controller', 'new');
            wrapper.append(unrelated, component);
            document.body.appendChild(wrapper);

            await vi.waitFor(() => expect(state.replace).toHaveBeenCalledTimes(1));
            expect(state.replace.mock.calls[0][0]).toBe(component);
            detector.disconnect();
        });

        it('removes tracked components in a removed subtree', async () => {
            const wrapper = document.createElement('section');
            const component = document.createElement('div');
            component.setAttribute('data-controller', 'old');
            wrapper.appendChild(component);
            document.body.appendChild(wrapper);
            const registry = createMockRegistry('[data-controller]', []);
            const state = createMockState([component]);
            state.get.mockReturnValue(new Map([['stimulus', {}]]));
            const detector = new ComponentDetector(registry, state, inspectorEl);
            detector.observe();

            wrapper.remove();

            await vi.waitFor(() => expect(state.remove).toHaveBeenCalledWith(component));
            expect(registry.notifyElementRemoved).toHaveBeenCalledWith(component, ['stimulus']);
            detector.disconnect();
        });

        it('reclassifies an element when its identifying attribute changes', async () => {
            const stimulus = createMockPlugin('stimulus', { type: 'stimulus' });
            const live = createMockPlugin('livecomponent', { type: 'livecomponent' });
            const registry = createMockRegistry('[data-controller]', []);
            registry.getForElement.mockImplementation((element) =>
                element.dataset.controller === 'live' ? [live] : [stimulus]
            );
            const state = new StateManager();
            const component = document.createElement('div');
            component.dataset.controller = 'stimulus';
            document.body.appendChild(component);
            const detector = new ComponentDetector(registry, state, inspectorEl);
            detector.scan();
            detector.observe();

            component.dataset.controller = 'live';

            await vi.waitFor(() => expect(state.get(component)?.has('livecomponent')).toBe(true));
            expect(state.get(component)?.has('stimulus')).toBe(false);
            expect(registry.notifyElementRemoved).toHaveBeenCalledWith(component, ['stimulus']);
            detector.disconnect();
        });

        it('removes state when a component attribute is removed', async () => {
            const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
            const registry = createMockRegistry('[data-controller]', []);
            registry.getForElement.mockImplementation((element) =>
                element.hasAttribute('data-controller') ? [plugin] : []
            );
            const state = new StateManager();
            const component = document.createElement('div');
            component.dataset.controller = 'hello';
            document.body.appendChild(component);
            const detector = new ComponentDetector(registry, state, inspectorEl);
            detector.scan();
            detector.observe();

            component.removeAttribute('data-controller');

            await vi.waitFor(() => expect(state.get(component)).toBeUndefined());
            detector.disconnect();
        });

        it('reparses a tracked owner when a dynamic descendant attribute changes', async () => {
            const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
            const registry = createMockRegistry('[data-controller]', [plugin]);
            registry.isWatchedAttribute.mockImplementation((name) => /^data-search-.+-target$/.test(name));
            const state = new StateManager();
            const component = document.createElement('div');
            component.dataset.controller = 'search';
            const child = document.createElement('button');
            component.appendChild(child);
            document.body.appendChild(component);
            const detector = new ComponentDetector(registry, state, inspectorEl);
            detector.scan();
            detector.observe();
            plugin.parse.mockClear();

            child.setAttribute('data-search-results-target', 'row');

            await vi.waitFor(() => expect(plugin.parse).toHaveBeenCalledWith(component, expect.any(Function)));
            expect(plugin.parse).not.toHaveBeenCalledWith(child);
            detector.disconnect();
        });

        it('reparses a tracked owner when a child interaction is inserted', async () => {
            const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
            const registry = createMockRegistry('[data-controller]', [plugin]);
            const state = new StateManager();
            const component = document.createElement('div');
            component.dataset.controller = 'search';
            document.body.appendChild(component);
            const detector = new ComponentDetector(registry, state, inspectorEl);
            detector.scan();
            detector.observe();
            plugin.parse.mockClear();

            const action = document.createElement('button');
            action.dataset.action = 'click->search#run';
            component.appendChild(action);

            await vi.waitFor(() => expect(plugin.parse).toHaveBeenCalledWith(component, expect.any(Function)));
            detector.disconnect();
        });
    });

    describe('disconnect()', () => {
        it('cleans up observer', () => {
            const registry = createMockRegistry('[data-controller]', []);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.observe();
            // Should not throw on disconnect
            expect(() => detector.disconnect()).not.toThrow();
        });

        it('does not process mutations after disconnect', async () => {
            const registry = createMockRegistry('[data-controller]', []);
            const state = createMockState();
            const detector = new ComponentDetector(registry, state, inspectorEl);

            detector.observe();
            detector.disconnect();
            const component = document.createElement('div');
            component.dataset.controller = 'late';
            document.body.appendChild(component);

            await Promise.resolve();
            expect(state.replace).not.toHaveBeenCalled();
        });
    });

    it('applies the same exclusions to a scan, an added subtree, and explicit inspection', async () => {
        const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
        const registry = createMockRegistry('[data-controller]', [plugin]);
        const state = new StateManager();
        const detector = new ComponentDetector(registry, state, inspectorEl, ['.ignored']);
        const wrapper = document.createElement('section');
        wrapper.innerHTML =
            '<div data-controller="visible"></div><div class="ignored"><i data-controller="hidden"></i></div>';
        const [visible, hidden] = wrapper.querySelectorAll('[data-controller]');
        document.body.append(wrapper);
        detector.scan();
        expect(state.elements).toEqual([visible]);
        plugin.parse.mockClear();
        detector.observe();
        const added = wrapper.cloneNode(true);
        document.body.append(added);
        await vi.waitFor(() => expect(state.elements).toHaveLength(2));
        expect(plugin.parse).toHaveBeenCalledTimes(1);
        expect(detector.inspect(hidden)).toBeUndefined();
        expect(plugin.parse).toHaveBeenCalledTimes(1);
        visible.className = 'ignored';
        expect(detector.inspect(visible)).toBeUndefined();
        expect(registry.notifyElementRemoved).toHaveBeenCalledWith(visible, ['stimulus']);
        detector.destroy();
    });

    it('excludes the Symfony toolbar before detection and mutation processing', async () => {
        const page = document.createElement('main');
        page.dataset.controller = 'page';
        const toolbar = document.createElement('div');
        toolbar.className = 'sf-toolbar';
        toolbar.dataset.controller = 'toolbar';
        toolbar.innerHTML = '<span data-controller="counter">0</span>';
        document.body.append(page, toolbar);
        const counter = toolbar.firstElementChild;
        const plugin = createMockPlugin('stimulus');
        const registry = createMockRegistry('[data-controller]', [plugin]);
        const state = new StateManager();
        const detector = new ComponentDetector(registry, state, inspectorEl, ['.custom-ignore']);
        const updated = vi.spyOn(state, 'notifyPageUpdated');
        detector.scan();
        expect([...state.elements]).toEqual([page]);
        expect(detector.inspect(counter)).toBeUndefined();
        detector.observe();
        plugin.parse.mockClear();
        for (let i = 1; i <= 100; i++) {
            counter.firstChild.data = String(i);
            counter.dataset.action = `click->counter#update${i}`;
            toolbar.append(document.createElement('span'));
        }
        await new Promise((resolve) => setTimeout(resolve, 0));
        expect(plugin.parse).not.toHaveBeenCalled();
        expect(registry.collectExternalChanges).not.toHaveBeenCalled();
        expect(updated).not.toHaveBeenCalled();
        page.dataset.action = 'click->page#update';
        await vi.waitFor(() => expect(plugin.parse).toHaveBeenCalledOnce());
        expect(registry.collectExternalChanges).toHaveBeenCalledOnce();
        detector.destroy();
    });

    it('batches text and attribute mutations while retaining external dependencies and one query cache', async () => {
        const owner = document.createElement('div');
        const external = document.createElement('div');
        owner.dataset.controller = external.dataset.controller = 'search';
        owner.innerHTML = '<button>Before</button>';
        document.body.append(owner, external);
        const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
        const registry = createMockRegistry('[data-controller]', [plugin]);
        registry.collectExternalChanges.mockReturnValue([external]);
        const state = new StateManager();
        const detector = new ComponentDetector(registry, state, inspectorEl);
        detector.scan();
        detector.observe();
        plugin.parse.mockClear();
        const child = owner.firstElementChild;
        child.firstChild.data = 'Intermediate';
        child.firstChild.data = 'After';
        child.dataset.action = 'click->search#first';
        child.dataset.action = 'click->search#last';
        await vi.waitFor(() => expect(plugin.parse).toHaveBeenCalledTimes(2));
        expect(plugin.parse.mock.calls.map(([element]) => element)).toEqual([owner, external]);
        expect(plugin.parse.mock.calls[0][1]).toBe(plugin.parse.mock.calls[1][1]);
        expect(registry.collectExternalChanges).toHaveBeenCalledOnce();
        detector.destroy();
    });

    it('refreshes an owner after a characterData-only mutation and cancels a pending refresh on disconnect', async () => {
        const owner = document.createElement('div');
        owner.dataset.controller = 'search';
        owner.textContent = 'Before';
        document.body.append(owner);
        const plugin = createMockPlugin('stimulus', { type: 'stimulus' });
        const registry = createMockRegistry('[data-controller]', [plugin]);
        const state = new StateManager();
        const detector = new ComponentDetector(registry, state, inspectorEl);
        detector.scan();
        detector.observe();
        plugin.parse.mockClear();
        owner.firstChild.data = 'After';
        await vi.waitFor(() => expect(plugin.parse).toHaveBeenCalledOnce());
        plugin.parse.mockClear();
        detector.refresh(owner);
        detector.disconnect();
        await Promise.resolve();
        expect(plugin.parse).not.toHaveBeenCalled();
        expect(registry.notifyElementRemoved).toHaveBeenCalledWith(owner, expect.anything());
    });
    it('does not restore a disconnected component queued by an earlier mutation', async () => {
        const target = document.createElement('div');
        target.dataset.controller = 'search';
        document.body.append(target);
        const state = new StateManager();
        const registry = createMockRegistry('[data-controller]', [createMockPlugin('stimulus')]);
        const detector = new ComponentDetector(registry, state, inspectorEl);
        detector.scan();
        detector.observe();
        target.setAttribute('data-action', 'click->search#run');
        target.remove();
        await new Promise((resolve) => setTimeout(resolve, 0));
        expect(state.elements).toHaveLength(0);
        expect(registry.notifyElementRemoved).toHaveBeenCalledWith(target, ['stimulus']);
        detector.destroy();
    });

    it('ignores subtrees added and removed before the mutation callback', async () => {
        const state = new StateManager();
        const registry = createMockRegistry('[data-controller]', [createMockPlugin('stimulus')]);
        const detector = new ComponentDetector(registry, state, inspectorEl);
        detector.observe();
        const target = document.createElement('div');
        target.dataset.controller = 'search';
        document.body.append(target);
        target.remove();
        await new Promise((resolve) => setTimeout(resolve, 0));
        expect(state.elements).toHaveLength(0);
        expect(registry.getForElement).not.toHaveBeenCalled();
        detector.destroy();
    });
});
