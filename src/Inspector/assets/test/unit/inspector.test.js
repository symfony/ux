import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { UXInspector, connectStimulus } from '../../src/inspector';
import { EventMonitor } from '../../src/core/event-monitor';

describe('UXInspector', () => {
    let inspector;
    let local;
    let session;

    beforeEach(() => {
        connectStimulus(null);
        const storage = () => ({ getItem: vi.fn(), setItem: vi.fn(), removeItem: vi.fn() });
        local = storage();
        session = storage();
        vi.stubGlobal('localStorage', local);
        vi.stubGlobal('sessionStorage', session);
        inspector = document.createElement('ux-inspector');
        document.body.appendChild(inspector);
    });

    afterEach(() => {
        vi.useFakeTimers();
        inspector?.remove();
        document.body.replaceChildren();
        vi.advanceTimersByTime(1000);
        vi.useRealTimers();
        vi.unstubAllGlobals();
        connectStimulus(null);
        vi.restoreAllMocks();
    });

    it('registers and renders the focused inspector shell', () => {
        expect(customElements.get('ux-inspector')).toBe(UXInspector);
        expect(inspector.shadowRoot.querySelector('.inspector')).not.toBeNull();
        expect(inspector.shadowRoot.querySelector('[data-ux-inspector-overlay]')).not.toBeNull();
        expect(inspector.shadowRoot.textContent).not.toContain('Server');
        expect(inspector.shadowRoot.textContent).not.toContain('Badges');
        expect(inspector.shadowRoot.textContent).not.toContain('Toasts');
        expect(inspector.shadowRoot.querySelector('.slide-handle')).toBeNull();
        expect(inspector.shadowRoot.querySelector('[aria-label="Open settings"]')).toBeNull();
    });

    it('renders one pull tab by default and opens the panel on click', () => {
        const tab = inspector.shadowRoot.querySelector('[aria-label="Open Inspector"]');
        expect(tab.textContent).toBe('UX');
        tab.click();
        expect(inspector.isOpen).toBe(true);
        inspector.remove();
        document.body.append(inspector);
        expect(inspector.shadowRoot.querySelectorAll('.pull-tab')).toHaveLength(1);
    });

    it('disables the pull tab through injected configuration while keeping the shortcut', () => {
        inspector.remove();
        inspector = document.createElement('ux-inspector');
        inspector.dataset.config = JSON.stringify({ pull_tab: false });
        document.body.append(inspector);
        expect(inspector.shadowRoot.querySelector('.pull-tab')).toBeNull();
        for (const key of ['u', 'x']) document.dispatchEvent(new KeyboardEvent('keydown', { key }));
        expect(inspector.isOpen).toBe(true);
    });

    it('opens with ux, docks on the right, and never uses persistent storage', () => {
        expect(inspector.isOpen).toBe(false);
        for (const key of ['u', 'x']) document.dispatchEvent(new KeyboardEvent('keydown', { key }));
        expect(inspector.isOpen).toBe(true);
        expect(document.documentElement.hasAttribute('data-ux-inspector-open')).toBe(true);
        expect(document.querySelector('[data-ux-inspector-layout]').textContent).toContain('padding-right:');
        inspector.setPanelWidth(300);
        expect(document.documentElement.style.getPropertyValue('--ux-inspector-width')).toBe('300px');
        inspector.close();
        expect(document.documentElement.hasAttribute('data-ux-inspector-open')).toBe(false);
        expect(document.querySelector('[data-ux-inspector-layout]')).toBeNull();
        for (const storage of [local, session]) {
            for (const method of Object.values(storage)) expect(method).not.toHaveBeenCalled();
        }
    });

    it('does not intercept Shift-click or continue ux into navigation commands', () => {
        const target = document.createElement('button');
        target.dataset.controller = 'sample';
        document.body.append(target);
        const click = new MouseEvent('click', { bubbles: true, cancelable: true, shiftKey: true });
        target.dispatchEvent(click);
        expect(click.defaultPrevented).toBe(false);
        expect(inspector.isOpen).toBe(false);
        for (const key of ['u', 'x', 'l']) document.dispatchEvent(new KeyboardEvent('keydown', { key }));
        expect(inspector.isOpen).toBe(true);
        expect(inspector.shadowRoot.querySelector('#panel-activity').hidden).toBe(true);
    });

    it('starts closed with default dimensions after a full remount', () => {
        inspector.open();
        inspector.setPanelWidth(450);
        inspector.remove();
        inspector = document.createElement('ux-inspector');
        document.body.append(inspector);
        expect(inspector.isOpen).toBe(false);
        expect(inspector.style.getPropertyValue('--panel-width')).toBe('');
    });

    it('rebuilds inside the existing shadow root after delayed teardown', () => {
        vi.useFakeTimers();
        const shadowRoot = inspector.shadowRoot;
        inspector.remove();
        vi.advanceTimersByTime(1000);

        document.body.appendChild(inspector);

        expect(inspector.shadowRoot).toBe(shadowRoot);
        expect(inspector.shadowRoot.querySelector('.inspector')).not.toBeNull();
        vi.useRealTimers();
    });

    it('does not run ux shortcuts while typing', () => {
        inspector.close();
        const input = document.createElement('input');
        document.body.appendChild(input);
        input.dispatchEvent(new KeyboardEvent('keydown', { key: 'u', bubbles: true }));
        input.dispatchEvent(new KeyboardEvent('keydown', { key: 'x', bubbles: true }));
        expect(inspector.isOpen).toBe(false);
    });

    it('does not register Alt+I as an inspector shortcut', () => {
        inspector.close();
        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'i', altKey: true, bubbles: true }));
        expect(inspector.isOpen).toBe(false);
    });

    it('keeps application element styles unchanged while inspecting', async () => {
        const target = document.createElement('div');
        target.dataset.controller = 'search';
        document.body.appendChild(target);
        const style = target.getAttribute('style');
        await inspector.inspectElement(target);
        expect(target.getAttribute('style')).toBe(style);
    });

    it('monitors events from newly inserted controllers without a manual scan', async () => {
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));
        const record = vi.spyOn(EventMonitor.prototype, 'record');
        const target = document.createElement('div');
        target.dataset.controller = 'newwidget';
        document.body.append(target);
        await vi.waitFor(() => expect(inspector.getStatus().components.stimulus).toBe(1));

        target.dispatchEvent(new CustomEvent('newwidget:open', { bubbles: true }));

        expect(record).toHaveBeenCalledWith(expect.objectContaining({ event: 'newwidget:open', type: 'stimulus' }));
    });

    it('updates custom event subscriptions when action descriptors change', async () => {
        const target = document.createElement('div');
        target.dataset.controller = 'widget';
        document.body.append(target);
        inspector.scan();
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));
        const listen = vi.spyOn(document, 'addEventListener');
        const record = vi.spyOn(EventMonitor.prototype, 'record');
        target.dataset.action = 'widget:custom->widget#handle';
        await vi.waitFor(() => expect(listen).toHaveBeenCalledWith('widget:custom', expect.any(Function), true));

        target.dispatchEvent(new CustomEvent('widget:custom', { bubbles: true }));

        expect(record).toHaveBeenCalledWith(expect.objectContaining({ event: 'widget:custom' }));
    });

    it('removes dynamic subscriptions when the last controller disappears', async () => {
        const target = document.createElement('div');
        target.dataset.controller = 'widget';
        document.body.append(target);
        inspector.scan();
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));
        const unlisten = vi.spyOn(document, 'removeEventListener');
        target.remove();

        await vi.waitFor(() => expect(unlisten).toHaveBeenCalledWith('widget:open', expect.any(Function), true));
        const record = vi.spyOn(EventMonitor.prototype, 'record');
        document.dispatchEvent(new CustomEvent('widget:open'));
        expect(record).not.toHaveBeenCalled();
    });

    it('keeps detection and the open detail active after a cancelled Turbo visit', async () => {
        const target = document.createElement('div');
        target.dataset.controller = 'counter';
        target.dataset.counterCountValue = '1';
        document.body.append(target);
        inspector.scan();
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));
        inspector.inspectElement(target);
        document.addEventListener('turbo:before-visit', (event) => event.preventDefault(), { once: true });
        const visit = new CustomEvent('turbo:before-visit', { cancelable: true });

        document.dispatchEvent(visit);
        target.dataset.counterCountValue = '2';
        const added = document.createElement('div');
        added.dataset.controller = 'added';
        document.body.append(added);

        expect(visit.defaultPrevented).toBe(true);
        await vi.waitFor(() => {
            expect(inspector.getStatus().components.stimulus).toBe(2);
            expect(inspector.shadowRoot.querySelector('[data-field-key="count"] .value')?.textContent).toContain('2');
        });
    });

    it('resumes detection and event monitoring when Turbo moves the permanent inspector', async () => {
        const oldTarget = document.createElement('div');
        oldTarget.dataset.controller = 'old';
        document.body.append(oldTarget);
        inspector.scan();
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));

        document.dispatchEvent(new CustomEvent('turbo:before-cache'));
        inspector.remove();
        oldTarget.remove();
        const target = document.createElement('div');
        target.dataset.controller = 'next';
        document.body.append(target, inspector);
        await vi.waitFor(() => expect(inspector.getStatus().components.stimulus).toBe(1));
        const record = vi.spyOn(EventMonitor.prototype, 'record');
        target.dispatchEvent(new CustomEvent('next:open', { bubbles: true }));
        expect(record).toHaveBeenCalledWith(expect.objectContaining({ event: 'next:open' }));

        const added = document.createElement('div');
        added.dataset.controller = 'added';
        document.body.append(added);
        await vi.waitFor(() => expect(inspector.getStatus().components.stimulus).toBe(2));
    });

    it.each([true, false])('keeps detecting after an in-place Turbo render (cache=%s)', async (cache) => {
        const oldTarget = document.createElement('div');
        oldTarget.dataset.controller = 'old';
        document.body.append(oldTarget);
        inspector.scan();
        inspector.open();
        inspector.setPanelWidth(320);
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));

        if (cache) {
            document.dispatchEvent(new CustomEvent('turbo:before-cache'));
            expect(document.documentElement.hasAttribute('data-ux-inspector-open')).toBe(false);
            expect(document.querySelector('[data-ux-inspector-layout]')).toBeNull();
        } else {
            // Turbo merges the head even when caching is disabled.
            document.querySelector('[data-ux-inspector-layout]').remove();
        }
        oldTarget.remove();
        const nextTarget = document.createElement('div');
        nextTarget.dataset.controller = 'next';
        document.body.append(nextTarget);
        document.dispatchEvent(new CustomEvent('turbo:render', { detail: { renderMethod: 'morph' } }));

        expect(inspector.isConnected).toBe(true);
        expect(inspector.isOpen).toBe(true);
        expect(document.documentElement.style.getPropertyValue('--ux-inspector-width')).toBe('320px');
        expect(document.querySelectorAll('[data-ux-inspector-layout]')).toHaveLength(1);
        const added = document.createElement('div');
        added.dataset.controller = 'added';
        document.body.append(added);
        await vi.waitFor(() => expect(inspector.getStatus().components.stimulus).toBe(2));

        // Cached previews and final renders must not duplicate subscriptions.
        document.dispatchEvent(new CustomEvent('turbo:render'));
        const record = vi.spyOn(EventMonitor.prototype, 'record');
        nextTarget.dispatchEvent(new CustomEvent('next:open', { bubbles: true }));
        expect(record).toHaveBeenCalledTimes(1);
    });

    it('reports installed packages, used frameworks and detected components separately', async () => {
        inspector.remove();
        inspector = document.createElement('ux-inspector');
        inspector.setAttribute(
            'data-config',
            JSON.stringify({
                packages: { stimulus: true, livecomponent: true, turbo: false },
            })
        );
        document.body.appendChild(inspector);
        const live = document.createElement('div');
        live.dataset.controller = 'live foo-bar';
        live.dataset.liveNameValue = 'Search';
        document.body.appendChild(live);
        vi.stubGlobal('Turbo', {});

        inspector.scan();
        await vi.waitFor(() => expect(inspector.getStatus().components.livecomponent).toBe(1));
        expect(inspector.getStatus()).toEqual({
            installed: { stimulus: true, livecomponent: true, turbo: false },
            used: { stimulus: true, livecomponent: true, turbo: true },
            components: { livecomponent: 1, stimulus: 1 },
        });
    });

    it.each(['before mount', 'DOMContentLoaded', 'open', 'Turbo render'])(
        'discovers window.Stimulus at %s without importing an Inspector bridge',
        async (moment) => {
            const readyState = vi.spyOn(document, 'readyState', 'get').mockReturnValue('loading');
            inspector.remove();
            const target = document.createElement('div');
            target.dataset.controller = 'counter';
            document.body.append(target);
            class CounterController {
                static values = { count: { type: Number, default: 5 } };
            }
            const instance = new CounterController();
            const application = {
                getControllerForElementAndIdentifier: vi.fn((element, identifier) =>
                    element === target && identifier === 'counter' ? instance : null
                ),
            };
            if (moment === 'before mount') vi.stubGlobal('Stimulus', application);
            inspector = document.createElement('ux-inspector');
            document.body.append(inspector);
            await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));

            if (moment !== 'before mount') {
                vi.stubGlobal('Stimulus', application);
                if (moment === 'DOMContentLoaded') document.dispatchEvent(new Event('DOMContentLoaded'));
                if (moment === 'open') inspector.open();
                if (moment === 'Turbo render') document.dispatchEvent(new CustomEvent('turbo:render'));
            }
            // Check discovery before inspectElement(), which itself refreshes the runtime.
            expect(application.getControllerForElementAndIdentifier).toHaveBeenCalledWith(target, 'counter');
            inspector.inspectElement(target);
            expect(inspector.shadowRoot.textContent).toContain('default');
            expect(inspector.shadowRoot.textContent).toContain('5');
            readyState.mockRestore();
        }
    );

    it('ignores a window.Stimulus value that is not an application', () => {
        vi.stubGlobal('Stimulus', { getControllerForElementAndIdentifier: true });
        expect(() => inspector.open()).not.toThrow();
    });

    it('accepts the public Stimulus application bridge and exposes runtime declarations', async () => {
        class SearchController {
            static targets = ['results'];
        }
        const instance = new SearchController();
        const target = document.createElement('div');
        target.id = 'search-runtime';
        target.dataset.controller = 'search';
        document.body.appendChild(target);

        expect(
            connectStimulus({
                getControllerForElementAndIdentifier: (element, identifier) =>
                    element === target && identifier === 'search' ? instance : null,
            })
        ).toBe(true);
        await vi.waitFor(() => expect(inspector.shadowRoot.querySelector('.component-row')).not.toBeNull());
        inspector.shadowRoot.querySelector('.component-row').click();

        expect(inspector.shadowRoot.querySelector('[data-group="stimulus-context"]')).toBeNull();
        expect(inspector.shadowRoot.querySelector('[data-field-key="results target"]')).toBeNull();
    });

    it('leaves the open detail when the inspector is cleared', async () => {
        const target = document.createElement('div');
        target.dataset.controller = 'counter';
        target.dataset.counterCountValue = '1';
        document.body.append(target);
        inspector.scan();
        await vi.waitFor(() => expect(inspector.hasAttribute('ready')).toBe(true));
        inspector.inspectElement(target);
        expect(inspector.shadowRoot.querySelector('[data-field-key="count"]')).not.toBeNull();

        inspector.clear();

        // The detail was reading state that clear() just dropped: the panel has
        // to come back to the list rather than show an empty component.
        expect(inspector.shadowRoot.querySelector('[data-field-key="count"]')).toBeNull();
        expect(inspector.shadowRoot.querySelector('.component-row')).not.toBeNull();
    });
});
