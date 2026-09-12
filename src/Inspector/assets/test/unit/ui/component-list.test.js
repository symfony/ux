import { afterEach, describe, expect, it, vi } from 'vitest';
import { ComponentList } from '../../../src/ui/component-list';
import { StateManager } from '../../../src/core/state-manager';

const lists = [];
afterEach(() => {
    for (const list of lists.splice(0)) list.destroy();
    document.body.replaceChildren();
    vi.restoreAllMocks();
});

function setup(count = 1) {
    const state = new StateManager();
    const targets = Array.from({ length: count }, (_, index) => {
        const target = document.createElement('div');
        target.id = `component-${index}`;
        state.set(target, 'stimulus', { data: {} });
        return target;
    });
    const visual = { onPreview: vi.fn(), onClearPreview: vi.fn(), onSelect: vi.fn() };
    const registry = { get: () => ({ getDisplayName: (target) => target.id }), collectPageRules: () => [] };
    const list = new ComponentList(state, registry, null, visual);
    lists.push(list);
    document.body.append(list.element);
    const refresh = () => list.refresh(new Set(['stimulus', 'turbo']), '', null);
    refresh();
    const row = list.element.querySelector('.component-row');
    return { list, targets, visual, registry, state, row, refresh };
}

describe('ComponentList event delegation', () => {
    it('uses six listeners for 100 rows and none on the cards', () => {
        const spy = vi.spyOn(EventTarget.prototype, 'addEventListener');
        const { list } = setup(100);
        const elements = spy.mock.contexts.filter((target) => target instanceof Element);
        expect(elements).toHaveLength(6);
        expect(elements.every((target) => target === list.element)).toBe(true);
    });

    it('previews by pointer and keyboard without drilling, ignoring movement inside a row', () => {
        const { row, targets, visual, list } = setup();
        const drill = vi.fn();
        list.element.addEventListener('drill-into', drill);
        row.dispatchEvent(new MouseEvent('pointerover', { bubbles: true }));
        expect(visual.onPreview).toHaveBeenCalledWith({ element: targets[0], framework: 'stimulus' });
        row.querySelector('strong').dispatchEvent(new MouseEvent('pointerover', { bubbles: true, relatedTarget: row }));
        expect(visual.onPreview).toHaveBeenCalledOnce();
        row.focus();
        expect(visual.onPreview).toHaveBeenCalledTimes(2);
        expect(drill).not.toHaveBeenCalled();
        row.blur();
        row.dispatchEvent(new MouseEvent('pointerout', { bubbles: true }));
        expect(visual.onClearPreview).toHaveBeenCalledTimes(2);
    });

    it('drills from a nested icon and retains the row after a data update', () => {
        const { list, row, targets, state, refresh } = setup();
        const drill = vi.fn();
        list.element.addEventListener('drill-into', drill);
        state.set(targets[0], 'stimulus', { data: { count: 2 } });
        refresh();
        expect(list.element.querySelector('.component-row')).toBe(row);
        row.querySelector('svg').dispatchEvent(new MouseEvent('click', { bubbles: true }));
        expect(drill.mock.calls[0][0].detail.element).toBe(targets[0]);
    });

    it('refreshes the selector when an element id changes', () => {
        const { list, targets, refresh } = setup();
        targets[0].id = 'renamed';
        refresh();
        expect(list.element.querySelector('.selector').textContent).toBe('div#renamed');
    });

    it('reads each name once per refresh and searches secondary frameworks without stale labels', () => {
        const { list, targets, registry, state } = setup();
        let primary = 'search';
        const stimulus = vi.fn(() => primary);
        const live = vi.fn(() => 'AccountPanel');
        registry.get = (name) => ({ getDisplayName: name === 'stimulus' ? stimulus : live });
        state.set(targets[0], 'livecomponent', { data: {} });

        list.refresh(new Set(['stimulus']), 'accountpanel', null);

        expect(list.element.querySelector('strong').textContent).toBe('search');
        expect(stimulus).toHaveBeenCalledOnce();
        expect(live).toHaveBeenCalledOnce();
        primary = 'lookup';
        list.refresh(new Set(['stimulus']), 'lookup', null);
        expect(list.element.querySelector('strong').textContent).toBe('lookup');
        expect(list.element.querySelector('.component-row').getAttribute('aria-label')).toBe(
            'lookup, Stimulus component'
        );
        expect(stimulus).toHaveBeenCalledTimes(2);
        expect(live).toHaveBeenCalledTimes(2);
    });

    it('routes page-rule hover, focus and selection through the same listeners', () => {
        const { list, registry, targets, visual, refresh } = setup();
        const target = targets[0];
        target.scrollIntoView = vi.fn();
        registry.collectPageRules = () => [
            { element: target, kind: 'disabled', label: 'Turbo disabled', framework: 'turbo' },
        ];
        refresh();
        const rule = list.element.querySelector('.page-rule');
        rule.focus();
        expect(visual.onPreview).toHaveBeenCalledWith({ element: target, label: 'Turbo disabled', framework: 'turbo' });
        rule.querySelector('strong').click();
        expect(target.scrollIntoView).toHaveBeenCalledOnce();
        expect(rule.getAttribute('aria-pressed')).toBe('true');
        expect(visual.onSelect).toHaveBeenCalledOnce();
    });

    it('removes listeners and releases rendered rows on destruction', () => {
        const { list, row, visual } = setup();
        list.destroy();
        list.element.append(row);
        row.dispatchEvent(new MouseEvent('pointerover', { bubbles: true }));
        row.click();
        expect(visual.onPreview).not.toHaveBeenCalled();
        expect(visual.onSelect).not.toHaveBeenCalled();
    });
});
