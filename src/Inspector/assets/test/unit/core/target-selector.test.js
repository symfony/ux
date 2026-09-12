import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { TargetSelector } from '../../../src/core/target-selector';

function createMockHighlighter() {
    return {
        hover: vi.fn(),
        clearHover: vi.fn(),
        select: vi.fn(),
        deselect: vi.fn(),
        clearAll: vi.fn(),
    };
}

function createMockRegistry(selector = null, plugins = []) {
    return {
        get combinedSelector() {
            return selector;
        },
        getForElement: vi.fn(() => plugins),
    };
}

describe('TargetSelector', () => {
    let highlighter;
    let registry;
    let selector;

    beforeEach(() => {
        vi.useFakeTimers();
        highlighter = createMockHighlighter();
        registry = createMockRegistry('[data-controller]', [{ name: 'stimulus' }]);
        selector = new TargetSelector(highlighter, registry);
    });

    afterEach(() => {
        vi.useRealTimers();
        selector.destroy();
        document.body.innerHTML = '';
    });

    describe('constructor', () => {
        it('sets active to false', () => {
            expect(selector.active).toBe(false);
        });
    });

    describe('enable()', () => {
        it('cancels delayed click activation when disabled and re-enabled before the timer fires', () => {
            const target = document.createElement('div');
            target.dataset.controller = 'search';
            document.body.append(target);
            const stale = vi.fn();
            const current = vi.fn();
            selector.enable(stale);
            selector.disable();
            selector.enable(current);
            vi.runAllTimers();
            target.click();
            expect(stale).not.toHaveBeenCalled();
            expect(current).toHaveBeenCalledExactlyOnceWith(target, [{ name: 'stimulus' }], false);
        });
        it('reports active state changes', () => {
            const onStateChange = vi.fn();
            selector = new TargetSelector(highlighter, registry, onStateChange);
            selector.enable(vi.fn());
            selector.disable();
            expect(onStateChange.mock.calls).toEqual([[true], [false]]);
        });

        it('sets active to true', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            expect(selector.active).toBe(true);
        });

        it('adds event listeners', () => {
            const spy = vi.spyOn(document, 'addEventListener');
            selector.enable(vi.fn());
            vi.runAllTimers();
            const eventNames = spy.mock.calls.map((c) => c[0]);
            expect(eventNames).toContain('mouseover');
            expect(eventNames).toContain('mouseout');
            expect(eventNames).toContain('click');
            expect(eventNames).toContain('keydown');
            spy.mockRestore();
        });

        it('is a no-op when already active', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            const spy = vi.spyOn(document, 'addEventListener');
            selector.enable(vi.fn());
            vi.runAllTimers();
            expect(spy).not.toHaveBeenCalled();
            spy.mockRestore();
        });
    });

    describe('disable()', () => {
        it('sets active to false', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            selector.disable();
            expect(selector.active).toBe(false);
        });

        it('stops handling events and can be activated again without duplicates', () => {
            const target = document.createElement('div');
            target.dataset.controller = 'search';
            document.body.append(target);
            const onSelect = vi.fn();
            selector.enable(onSelect);
            vi.runAllTimers();
            selector.disable();
            highlighter.hover.mockClear();
            target.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            target.click();
            expect(highlighter.hover).not.toHaveBeenCalled();
            expect(onSelect).not.toHaveBeenCalled();

            selector.enable(onSelect);
            vi.runAllTimers();
            target.click();
            expect(onSelect).toHaveBeenCalledOnce();
        });

        it('clears hover highlight', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            selector.disable();
            expect(highlighter.clearHover).toHaveBeenCalled();
            expect(highlighter.deselect).toHaveBeenCalled();
        });

        it('is a no-op when not active', () => {
            expect(() => selector.disable()).not.toThrow();
            expect(selector.active).toBe(false);
        });
    });

    describe('toggle()', () => {
        it('enables when inactive and returns true', () => {
            const result = selector.toggle(vi.fn());
            expect(result).toBe(true);
            expect(selector.active).toBe(true);
        });

        it('disables when active and returns false', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            const result = selector.toggle(vi.fn());
            expect(result).toBe(false);
            expect(selector.active).toBe(false);
        });
    });

    describe('click event', () => {
        it('resolves the same closest component for hover and click with one inspector exclusion per event', () => {
            const component = document.createElement('div');
            component.dataset.controller = 'search';
            const child = document.createElement('button');
            component.append(child);
            document.body.append(component);
            const closest = vi.spyOn(child, 'closest');
            const onSelect = vi.fn();
            selector.enable(onSelect);
            vi.runAllTimers();
            child.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
            child.click();
            expect(highlighter.hover).toHaveBeenCalledWith(component, 'stimulus');
            expect(onSelect).toHaveBeenCalledWith(component, [{ name: 'stimulus' }], false);
            expect(closest.mock.calls).toEqual([
                ['ux-inspector, .sf-toolbar'],
                ['[data-controller]'],
                ['ux-inspector, .sf-toolbar'],
                ['[data-controller]'],
            ]);
            closest.mockRestore();
        });

        it('blocks page navigation on unmatched links while inspect mode remains active', () => {
            const link = document.createElement('a');
            link.href = '/another-page';
            document.body.append(link);
            selector.enable(vi.fn());
            vi.runAllTimers();
            const click = new MouseEvent('click', { bubbles: true, cancelable: true });
            link.dispatchEvent(click);
            expect(click.defaultPrevented).toBe(true);
            expect(selector.active).toBe(true);
        });
        it('calls onSelect with element and plugins', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            const onSelect = vi.fn();
            const plugins = [{ name: 'stimulus' }];
            registry = createMockRegistry('[data-controller]', plugins);
            selector = new TargetSelector(highlighter, registry);
            selector.enable(onSelect);

            const event = new MouseEvent('click', { bubbles: true });
            vi.runAllTimers();
            el.dispatchEvent(event);

            expect(onSelect).toHaveBeenCalledWith(el, plugins, false);
        });

        it('prevents default and stops propagation', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            selector.enable(vi.fn());
            vi.runAllTimers();

            const outerClicked = vi.fn();
            document.body.addEventListener('click', outerClicked);

            const event = new MouseEvent('click', { bubbles: true, cancelable: true });
            vi.runAllTimers();
            el.dispatchEvent(event);

            // stopImmediatePropagation prevents the outer listener from firing
            // when both use capturing. The selector uses capturing, so the
            // click is handled before bubbling listeners.
            expect(event.defaultPrevented).toBe(true);
            expect(outerClicked).not.toHaveBeenCalled();
        });

        it('exits after a normal selection while preserving the selected highlight', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            selector.enable(vi.fn());
            vi.runAllTimers();
            const event = new MouseEvent('click', { bubbles: true });
            vi.runAllTimers();
            el.dispatchEvent(event);

            expect(selector.active).toBe(false);
            expect(highlighter.deselect).not.toHaveBeenCalled();
        });

        it('keeps selecting after Shift-click', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            const onSelect = vi.fn();
            selector.enable(onSelect);
            vi.runAllTimers();
            el.dispatchEvent(new MouseEvent('click', { bubbles: true, shiftKey: true }));

            expect(onSelect).toHaveBeenCalledWith(el, [{ name: 'stimulus' }], true);
            expect(selector.active).toBe(true);
        });

        it.each(['ux-inspector', '.sf-toolbar'])(
            'ignores hover and clicks inside %s without leaving inspect mode',
            (root) => {
                const inspector = document.createElement(root === 'ux-inspector' ? root : 'div');
                if (root === '.sf-toolbar') inspector.className = 'sf-toolbar';
                const button = document.createElement('button');
                inspector.appendChild(button);
                document.body.appendChild(inspector);
                const onSelect = vi.fn();
                selector.enable(onSelect);
                vi.runAllTimers();

                button.dataset.controller = 'toolbar';
                const clicked = vi.fn();
                button.addEventListener('click', clicked);
                button.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));
                button.click();

                expect(highlighter.hover).not.toHaveBeenCalled();
                expect(clicked).toHaveBeenCalledOnce();
                expect(selector.active).toBe(true);
                expect(onSelect).not.toHaveBeenCalled();
            }
        );
    });

    describe('escape key', () => {
        it('disables selector', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
            expect(selector.active).toBe(false);
        });

        it('keeps the event from reaching other Escape handlers', () => {
            // The panel pops a drill level on Escape: leaving inspect mode must
            // not also navigate back.
            const other = vi.fn();
            document.addEventListener('keydown', other);
            selector.enable(vi.fn());
            vi.runAllTimers();

            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }));

            expect(selector.active).toBe(false);
            expect(other).not.toHaveBeenCalled();
            document.removeEventListener('keydown', other);
        });

        it('leaves other keys alone', () => {
            const other = vi.fn();
            document.addEventListener('keydown', other);
            selector.enable(vi.fn());
            vi.runAllTimers();

            document.dispatchEvent(new KeyboardEvent('keydown', { key: 'a', bubbles: true }));

            expect(selector.active).toBe(true);
            expect(other).toHaveBeenCalledTimes(1);
            document.removeEventListener('keydown', other);
        });
    });

    describe('mouseover', () => {
        it('highlights closest component', () => {
            const el = document.createElement('div');
            el.setAttribute('data-controller', 'hello');
            document.body.appendChild(el);

            selector.enable(vi.fn());
            vi.runAllTimers();
            el.dispatchEvent(new MouseEvent('mouseover', { bubbles: true }));

            expect(highlighter.hover).toHaveBeenCalledWith(el, 'stimulus');
        });
    });

    describe('mouseout', () => {
        it('clears hover highlight', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            document.dispatchEvent(new MouseEvent('mouseout', { bubbles: true }));
            expect(highlighter.clearHover).toHaveBeenCalled();
        });
    });

    describe('destroy()', () => {
        it('disables selector', () => {
            selector.enable(vi.fn());
            vi.runAllTimers();
            selector.destroy();
            expect(selector.active).toBe(false);
        });
    });

    describe('ux-inspector elements', () => {
        it('ignores events on ux-inspector elements', () => {
            const inspector = document.createElement('ux-inspector');
            const inner = document.createElement('div');
            inner.setAttribute('data-controller', 'hello');
            inspector.appendChild(inner);
            document.body.appendChild(inspector);

            const onSelect = vi.fn();
            selector.enable(onSelect);
            inner.dispatchEvent(new MouseEvent('click', { bubbles: true }));

            // onSelect should not be called for inspector-internal elements
            expect(onSelect).not.toHaveBeenCalled();
        });
    });
});
