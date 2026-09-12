import { describe, expect, it, vi } from 'vitest';
import {
    componentIdentity,
    componentLabel,
    createEmptyState,
    el,
    expandableText,
    formatElapsedTime,
    frameworkName,
    reconcileChildren,
} from '../../../src/ui/ui-helpers';

describe('ui helpers', () => {
    it('reorders retained children, inserts new ones, and removes obsolete ones', () => {
        const parent = el('div');
        const first = el('button', { text: 'first' });
        const second = el('button', { text: 'second' });
        const obsolete = el('button', { text: 'obsolete' });
        const added = el('button', { text: 'added' });
        parent.append(first, second, obsolete);
        document.body.append(parent);
        second.focus();

        reconcileChildren(parent, [second, added, first]);

        expect([...parent.children]).toEqual([second, added, first]);
        expect(document.activeElement).toBe(second);
        expect(obsolete.isConnected).toBe(false);
        parent.remove();
    });

    it('reorders retained children on engines without moveBefore', () => {
        // Safari and older engines have no Element.moveBefore: the reorder must
        // still happen through insertBefore rather than throwing.
        const native = Element.prototype.moveBefore;
        delete Element.prototype.moveBefore;
        try {
            const parent = document.createElement('div');
            const first = document.createElement('span');
            const second = document.createElement('span');
            parent.append(first, second);
            document.body.append(parent);

            reconcileChildren(parent, [second, first]);

            expect([...parent.children]).toEqual([second, first]);
            parent.remove();
        } finally {
            Element.prototype.moveBefore = native;
        }
    });

    it('builds elements with text and listeners', () => {
        const click = vi.fn();
        const button = el('button', { class: 'test', text: '<b>safe</b>', on: { click } });
        button.click();
        expect(button.textContent).toBe('<b>safe</b>');
        expect(button.querySelector('b')).toBeNull();
        expect(click).toHaveBeenCalledOnce();
    });

    it('builds a useful empty state', () => {
        expect(createEmptyState('None', 'Try again').textContent).toContain('Try again');
    });

    it('expands dynamic text with pointer and keyboard input', () => {
        const text = expandableText(el('span', { text: 'A long dynamic value' }));

        text.click();
        expect(text.classList.contains('expanded')).toBe(true);
        expect(text.getAttribute('aria-expanded')).toBe('true');

        text.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter' }));
        expect(text.classList.contains('expanded')).toBe(false);
        expect(text.getAttribute('aria-expanded')).toBe('false');
    });

    it('does not add a second keyboard activation contract to native buttons', () => {
        const button = expandableText(el('button', { type: 'button', text: 'A long value' }));

        expect(button.getAttribute('role')).toBeNull();
        expect(button.tabIndex).toBe(0);
        button.click();

        expect(button.classList.contains('expanded')).toBe(true);
        expect(button.getAttribute('aria-expanded')).toBe('true');
    });

    it('formats component and framework names', () => {
        const target = document.createElement('turbo-frame');
        target.id = 'cart';
        expect(componentLabel(target)).toBe('turbo-frame#cart');
        expect(frameworkName('livecomponent')).toBe('Live');
    });

    it('selects the first registered identity while keeping all frameworks and the raw id searchable', () => {
        const target = el('div', { id: 'live-12-0' });
        const registry = { get: (name) => (name === 'unknown' ? undefined : { getDisplayName: () => 'Cart' }) };
        const identity = componentIdentity(
            target,
            new Map([
                ['unknown', {}],
                ['livecomponent', {}],
            ]),
            registry
        );
        expect(identity).toEqual({
            name: 'Cart',
            framework: 'livecomponent',
            selector: 'div#live-12-0',
            search: 'div live-12-0 unknown livecomponent Cart',
        });
        expect(componentIdentity(target, undefined, registry)).toEqual({
            name: undefined,
            framework: 'default',
            selector: 'div#live-12-0',
            search: 'div live-12-0',
        });
    });

    it('formats elapsed session time without long decimal seconds', () => {
        expect(formatElapsedTime(1200)).toBe('1.20s');
        expect(formatElapsedTime(75600)).toBe('1m 15s');
        expect(formatElapsedTime(7_265_000)).toBe('121m 05s');
    });
});
