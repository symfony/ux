import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { Highlighter } from '../../../src/visual/highlighter';
import { StateManager } from '../../../src/core/state-manager';

describe('Highlighter', () => {
    let root, state, registry, visual, observer, resized;

    beforeEach(() => {
        observer = { observe: vi.fn(), unobserve: vi.fn(), disconnect: vi.fn() };
        vi.stubGlobal(
            'ResizeObserver',
            class {
                constructor(callback) {
                    resized = callback;
                    return observer;
                }
            }
        );
        root = document.createElement('div');
        state = new StateManager();
        registry = {
            getForElement: vi.fn(() => [{ name: 'stimulus' }]),
            get: vi.fn(() => ({ getDisplayName: () => 'search' })),
        };
        visual = new Highlighter(root, state, registry);
    });

    afterEach(() => {
        visual.destroy();
        document.body.replaceChildren();
        vi.useRealTimers();
        vi.restoreAllMocks();
        vi.unstubAllGlobals();
    });

    it('uses one visual container for every mode', () => {
        expect(root.querySelectorAll('[data-ux-inspector-overlay]')).toHaveLength(1);
    });

    it('renders hover and selection without changing target styles', () => {
        const target = document.createElement('div');
        document.body.appendChild(target);
        const before = target.getAttribute('style');
        visual.hover(target, 'stimulus');
        visual.select(target, 'stimulus');
        expect(root.querySelectorAll('.box')).toHaveLength(2);
        expect(target.getAttribute('style')).toBe(before);
        target.remove();
    });

    it('shows every tracked component and tags its framework', () => {
        const target = document.createElement('div');
        document.body.appendChild(target);
        state.set(target, 'stimulus', { data: {} });
        visual.showAll();
        const box = root.querySelector('.box[data-mode="all"]');
        expect(box.dataset.framework).toBe('stimulus');
        expect(box.textContent).toBe('search');
        target.remove();
    });

    it('reuses hover and selection boxes, captions, and observations across repeated requests', () => {
        const target = document.createElement('div');
        document.body.append(target);
        const create = vi.spyOn(document, 'createElement');
        visual.hover(target, 'stimulus', 'search');
        visual.select(target, 'stimulus', 'search');
        const boxes = [...root.querySelectorAll('.box')];
        const captions = boxes.map((box) => box.firstElementChild);
        for (let i = 1; i < 100; i++) {
            visual.hover(target, 'stimulus', 'search');
            visual.select(target, 'stimulus', 'search');
        }
        expect(create).toHaveBeenCalledTimes(4);
        expect([...root.querySelectorAll('.box')]).toEqual(boxes);
        expect(boxes.map((box) => box.firstElementChild)).toEqual(captions);
        expect(observer.observe).toHaveBeenCalledExactlyOnceWith(target, { box: 'border-box' });
        expect(observer.unobserve).not.toHaveBeenCalled();
    });

    it('retargets existing boxes without detaching them and releases observations only after the last user', () => {
        const first = document.createElement('div');
        const second = document.createElement('div');
        document.body.append(first, second);
        visual.hover(first, 'stimulus', 'first');
        visual.select(first, 'stimulus', 'first');
        const hover = root.querySelector('[data-mode="hover"]');
        const selected = root.querySelector('[data-mode="selected"]');
        const caption = hover.firstElementChild;
        const removed = vi.spyOn(hover, 'remove');
        visual.hover(second, 'turbo', 'second');
        expect(observer.unobserve).not.toHaveBeenCalled();
        expect(root.querySelector('[data-mode="hover"]')).toBe(hover);
        expect(hover.firstElementChild).toBe(caption);
        expect(caption.textContent).toBe('second');
        expect(hover.dataset.framework).toBe('turbo');
        expect(removed).not.toHaveBeenCalled();
        visual.select(second, 'turbo');
        expect(root.querySelector('[data-mode="selected"]')).toBe(selected);
        expect(selected.children).toHaveLength(0);
        expect(observer.observe).toHaveBeenCalledTimes(2);
        expect(observer.unobserve).toHaveBeenCalledExactlyOnceWith(first);
        visual.clearAll();
        expect(observer.unobserve).toHaveBeenLastCalledWith(second);
        expect(observer.unobserve).toHaveBeenCalledTimes(2);
    });

    it('only creates and measures the added box while showing 100 existing components', () => {
        const targets = Array.from({ length: 100 }, () => document.createElement('div'));
        document.body.append(...targets);
        for (const target of targets) {
            target.getBoundingClientRect = vi.fn(() => ({ left: 0, top: 0, width: 10, height: 10 }));
            state.set(target, 'stimulus', { data: {} });
        }
        visual.showAll();
        const boxes = [...root.querySelectorAll('[data-mode="all"]')];
        const added = document.createElement('div');
        document.body.append(added);
        const create = vi.spyOn(document, 'createElement');
        observer.observe.mockClear();
        for (const target of targets) target.getBoundingClientRect.mockClear();
        state.set(added, 'stimulus', { data: {} });
        expect(create).toHaveBeenCalledTimes(2);
        expect([...root.querySelectorAll('[data-mode="all"]')].slice(0, 100)).toEqual(boxes);
        expect(observer.observe).toHaveBeenCalledExactlyOnceWith(added, { box: 'border-box' });
        expect(observer.unobserve).not.toHaveBeenCalled();
        expect(targets.map((target) => target.getBoundingClientRect.mock.calls.length)).toEqual(Array(100).fill(0));
        create.mockClear();
        state.remove(added);
        expect([...root.querySelectorAll('[data-mode="all"]')]).toEqual(boxes);
        expect(create).not.toHaveBeenCalled();
        expect(observer.unobserve).toHaveBeenCalledExactlyOnceWith(added);
    });

    it('updates show-all metadata in place and drops boxes when the state is cleared', () => {
        const target = document.createElement('div');
        document.body.append(target);
        state.set(target, 'stimulus', { data: {} });
        visual.showAll();
        const box = root.querySelector('[data-mode="all"]');
        const caption = box.firstElementChild;
        registry.get.mockReturnValue({ getDisplayName: () => 'updated' });
        state.set(target, 'stimulus', { data: {} });
        expect(root.querySelector('[data-mode="all"]')).toBe(box);
        expect(box.firstElementChild).toBe(caption);
        expect(caption.textContent).toBe('updated');
        state.clear();
        expect(root.querySelectorAll('.box')).toHaveLength(0);
        expect(observer.unobserve).toHaveBeenCalledExactlyOnceWith(target);
    });

    it('reads geometry once per target and only writes changed geometry across its four modes', () => {
        const target = document.createElement('div');
        document.body.append(target);
        target.getBoundingClientRect = vi.fn(() => ({ left: 10, top: 20, width: 30, height: 40 }));
        state.set(target, 'stimulus', { data: {} });
        visual.showAll();
        visual.hover(target);
        visual.select(target);
        visual.pulse(target);
        const styles = [...root.querySelectorAll('.box')].map((box) => vi.spyOn(box.style, 'setProperty'));
        target.getBoundingClientRect.mockClear();
        visual.refresh();
        expect(target.getBoundingClientRect).toHaveBeenCalledOnce();
        for (const style of styles) expect(style).not.toHaveBeenCalled();
        target.getBoundingClientRect.mockReturnValue({ left: 10, top: 20, width: 35, height: 40 });
        resized([{ target }]);
        for (const style of styles) expect(style).toHaveBeenCalledExactlyOnceWith('width', '39px');
        target.remove();
        visual.refresh();
        expect(root.querySelectorAll('.box')).toHaveLength(0);
        expect(observer.unobserve).toHaveBeenCalledExactlyOnceWith(target);
    });

    it('aborts state listeners and ignores queued resize notifications after teardown', () => {
        visual.destroy();
        const listen = vi.spyOn(state, 'addEventListener');
        visual = new Highlighter(root, state, registry);
        const target = document.createElement('div');
        document.body.append(target);
        target.getBoundingClientRect = vi.fn(() => ({ left: 0, top: 0, width: 10, height: 10 }));
        state.set(target, 'stimulus', { data: {} });
        visual.showAll();
        target.getBoundingClientRect.mockClear();
        visual.destroy();
        for (const [, , { signal }] of listen.mock.calls) expect(signal.aborted).toBe(true);
        resized([{ target }]);
        expect(target.getBoundingClientRect).not.toHaveBeenCalled();
        expect(observer.unobserve).toHaveBeenCalledExactlyOnceWith(target);
    });

    it('groups identical rapid events and restarts their lifetime', () => {
        vi.useFakeTimers();
        const target = document.createElement('div');
        document.body.appendChild(target);

        visual.pulse(target, 'livecomponent', 'live:render');
        vi.advanceTimersByTime(800);
        visual.pulse(target, 'livecomponent', 'live:render');

        expect(root.querySelectorAll('.box[data-mode="event"]')).toHaveLength(1);
        expect(root.querySelector('.box[data-mode="event"]').textContent).toBe('live:render ×2');

        vi.advanceTimersByTime(800);
        expect(root.querySelector('.box[data-mode="event"]')).not.toBeNull();
        vi.advanceTimersByTime(401);
        expect(root.querySelector('.box[data-mode="event"]')).toBeNull();
    });

    it('replaces a grouped badge when a different event affects the component', () => {
        vi.useFakeTimers();
        const target = document.createElement('div');
        document.body.appendChild(target);

        visual.pulse(target, 'turbo', 'turbo:load');
        visual.pulse(target, 'turbo', 'turbo:load');
        vi.advanceTimersByTime(800);
        visual.pulse(target, 'turbo', 'turbo:render');

        expect(root.querySelectorAll('.box[data-mode="event"]')).toHaveLength(1);
        expect(root.querySelector('.box[data-mode="event"]').textContent).toBe('turbo:render');
        vi.advanceTimersByTime(800);
        expect(root.querySelector('.box[data-mode="event"]')).not.toBeNull();
    });

    it('keeps the event badge separate from the show-all component label', () => {
        vi.useFakeTimers();
        const target = document.createElement('div');
        document.body.appendChild(target);
        state.set(target, 'stimulus', { data: {} });

        visual.showAll();
        visual.pulse(target, 'stimulus', 'search:submit');

        expect(root.querySelector('.box[data-mode="all"]').textContent).toBe('search');
        expect(root.querySelector('.box[data-mode="event"]').textContent).toBe('search:submit');
        expect(root.querySelectorAll('.box')).toHaveLength(2);
    });

    it('clears event timers and boxes deterministically', () => {
        vi.useFakeTimers();
        const first = document.createElement('div');
        const second = document.createElement('div');
        document.body.append(first, second);
        visual.pulse(first, 'stimulus', 'one');
        visual.pulse(second, 'turbo', 'two');

        visual.clearAll();

        expect(root.querySelectorAll('.box[data-mode="event"]')).toHaveLength(0);
        expect(vi.getTimerCount()).toBe(0);
    });

    it('destroy cancels event timers and removes the shared container', () => {
        vi.useFakeTimers();
        const target = document.createElement('div');
        document.body.appendChild(target);
        visual.pulse(target, 'stimulus', 'search:connect');

        visual.destroy();

        expect(root.querySelector('[data-ux-inspector-overlay]')).toBeNull();
        expect(vi.getTimerCount()).toBe(0);
    });

    it('removes the event badge when its component disconnects', () => {
        vi.useFakeTimers();
        const target = document.createElement('div');
        document.body.appendChild(target);
        visual.pulse(target, 'livecomponent', 'live:disconnect');

        target.remove();
        visual.refresh();

        expect(root.querySelector('.box[data-mode="event"]')).toBeNull();
        expect(vi.getTimerCount()).toBe(0);
    });

    it('repositions event badges during refresh', () => {
        vi.useFakeTimers();
        const target = document.createElement('div');
        document.body.appendChild(target);
        target.getBoundingClientRect = vi.fn(() => ({ left: 10, top: 20, width: 30, height: 40 }));
        visual.pulse(target, 'stimulus', 'search:change');
        const box = root.querySelector('.box[data-mode="event"]');

        target.getBoundingClientRect.mockReturnValue({ left: 50, top: 60, width: 70, height: 80 });
        visual.refresh();

        expect(box.style.translate).toBe('48px 58px');
        expect(box.style.width).toBe('74px');
        expect(box.style.height).toBe('84px');
    });
    it('tracks size changes only while a target has visible overlays', () => {
        visual.destroy();
        let resized;
        const observe = vi.fn();
        const unobserve = vi.fn();
        const disconnect = vi.fn();
        vi.stubGlobal(
            'ResizeObserver',
            class {
                constructor(callback) {
                    resized = callback;
                }
                observe = observe;
                unobserve = unobserve;
                disconnect = disconnect;
            }
        );
        visual = new Highlighter(root, state, registry);
        const target = document.createElement('div');
        document.body.append(target);
        let width = 100;
        target.getBoundingClientRect = () => ({ left: 10, top: 20, width, height: 30 });
        visual.hover(target);
        visual.select(target);
        expect(observe).toHaveBeenCalledExactlyOnceWith(target, { box: 'border-box' });
        width = 180;
        resized([{ target }]);
        expect(root.querySelector('.box[data-mode="selected"]').style.width).toBe('184px');
        visual.clearHover();
        expect(unobserve).not.toHaveBeenCalled();
        visual.deselect();
        expect(unobserve).toHaveBeenCalledExactlyOnceWith(target);
        visual.destroy();
        expect(disconnect).toHaveBeenCalledOnce();
    });

    it('repositions only the boxes tracking a resized element', () => {
        visual.destroy();
        let resized;
        vi.stubGlobal(
            'ResizeObserver',
            class {
                constructor(callback) {
                    resized = callback;
                }
                observe = vi.fn();
                unobserve = vi.fn();
                disconnect = vi.fn();
            }
        );
        visual = new Highlighter(root, state, registry);
        const first = document.createElement('div');
        const second = document.createElement('div');
        document.body.append(first, second);
        first.getBoundingClientRect = vi.fn(() => ({ left: 0, top: 0, width: 10, height: 10 }));
        second.getBoundingClientRect = vi.fn(() => ({ left: 0, top: 0, width: 20, height: 20 }));
        state.set(first, 'stimulus', { data: {} });
        state.set(second, 'stimulus', { data: {} });
        visual.showAll();
        const [firstBox, secondBox] = root.querySelectorAll('.box[data-mode="all"]');
        first.getBoundingClientRect.mockReturnValue({ left: 5, top: 5, width: 50, height: 50 });
        second.getBoundingClientRect.mockReturnValue({ left: 7, top: 7, width: 70, height: 70 });
        second.getBoundingClientRect.mockClear();

        resized([{ target: first }]);

        expect(firstBox.style.width).toBe('54px');
        expect(secondBox.style.width).toBe('24px');
        expect(second.getBoundingClientRect).not.toHaveBeenCalled();

        visual.refresh();

        expect(secondBox.style.width).toBe('74px');
        expect(second.getBoundingClientRect).toHaveBeenCalled();

        first.remove();
        second.remove();
    });
});
