import { projectActivity } from '../../../src/core/activity-projector';
import { describe, expect, it, vi } from 'vitest';
import { ComponentCard } from '../../../src/ui/component-card';

function setup(entries = [{ event: 'search:connect', label: 'Connected' }]) {
    const registry = {
        get: vi.fn(() => ({
            getDisplayName: () => 'search',
            renderCard: vi.fn(),
        })),
    };
    const monitor = { project: vi.fn(() => projectActivity(entries)) };
    const target = document.createElement('div');
    target.id = 'lookup';
    const data = new Map([['stimulus', { data: { query: 'hello' } }]]);
    const renderer = new ComponentCard(registry, monitor);
    return { card: renderer.render(target, data), renderer, registry, target };
}

describe('ComponentCard', () => {
    it('shows identity, selector, event count and a framework dot without visible framework text', () => {
        const { card } = setup();
        expect(card.textContent).toContain('search');
        expect(card.textContent).toContain('div#lookup');
        expect(card.textContent).not.toContain('Stimulus');
        expect(card.dataset.framework).toBe('stimulus');
        expect(card.querySelector('.activity').textContent).toBe('1');
        expect(card.querySelector('.component-row').getAttribute('aria-label')).toContain('Stimulus component');
    });

    it('has one semantic primary action and no listbox role', () => {
        const { card, registry } = setup();
        expect(card.querySelectorAll('button')).toHaveLength(1);
        expect(card.querySelector('.component-row').tagName).toBe('BUTTON');
        expect(card.hasAttribute('role')).toBe(false);
        expect(card.hasAttribute('tabindex')).toBe(false);
        expect(registry.get.mock.results[0].value.renderCard).not.toHaveBeenCalled();
    });

    it('shows a framework dot anchor without exposing generated LiveComponent ids', () => {
        const registry = { get: vi.fn(() => ({ getDisplayName: () => 'SearchDashboard' })) };
        const target = document.createElement('div');
        target.id = 'live-2893674948-0';
        const card = new ComponentCard(registry, { project: () => [] }).render(
            target,
            new Map([['livecomponent', { data: {} }]])
        );

        expect(card.textContent).toContain('SearchDashboard');
        expect(card.querySelector('.selector').textContent).toBe('div');
        expect(card.textContent).not.toContain('div#live-');
    });

    it.each(['live-42', 'live-42-3', 'checkout'])('preserves Live selector rules for %s', (id) => {
        const registry = { get: () => ({ getDisplayName: () => 'Cart' }) };
        const target = document.createElement('div');
        target.id = id;
        const card = new ComponentCard(registry, null).render(target, new Map([['livecomponent', {}]]));
        expect(card.querySelector('.selector').textContent).toBe(id === 'checkout' ? 'div#checkout' : 'div');
        expect(card.querySelector('.component-row').getAttribute('aria-label')).toBe('Cart, Live component');
    });

    it('keeps bare-tag fallback labels when no registered plugin exists', () => {
        const target = document.createElement('div');
        target.id = 'example';
        const card = new ComponentCard({ get: () => undefined }, null).render(target, new Map([['unknown', {}]]));
        expect(card.querySelector('strong').textContent).toBe('div');
        expect(card.querySelector('.selector').textContent).toBe('div');
        expect(card.querySelector('.component-row').getAttribute('aria-label')).toBe('div, Unknown component');
    });

    it('updates event activity in place', () => {
        const { card, renderer } = setup([]);
        expect(card.querySelector('.activity')).toBeNull();
        expect(renderer.updateActivity(card, 3, 'Submitted')).toBe(true);
        expect(card.querySelector('.activity').textContent).toBe('3');
        expect(card.querySelector('.activity').title).toContain('Latest: Submitted');
        expect(card.querySelector('.component-row').getAttribute('aria-label')).toContain('3 captured events');
    });

    it('does not mutate or schedule animation for an unchanged counter', () => {
        const { card, renderer } = setup();
        const frame = vi.spyOn(window, 'requestAnimationFrame');
        const observer = new MutationObserver(() => {});
        observer.observe(card, { subtree: true, attributes: true, childList: true, characterData: true });
        renderer.updateActivity(card, 1, 'Connected');
        expect(observer.takeRecords()).toHaveLength(0);
        expect(frame).not.toHaveBeenCalled();
        observer.disconnect();
        frame.mockRestore();
    });

    it('coalesces pending pulses and cancels them on clear or destruction', () => {
        vi.useFakeTimers();
        const { card, renderer } = setup([]);
        document.body.append(card);
        const action = card.querySelector('.component-row');
        renderer.updateActivity(card, 1);
        renderer.updateActivity(card, 2);
        expect(vi.getTimerCount()).toBe(1);
        renderer.updateActivity(card, 0);
        expect(vi.getTimerCount()).toBe(0);
        renderer.updateActivity(card, 3);
        renderer.destroy();
        expect(vi.getTimerCount()).toBe(0);
        vi.runAllTimers();
        expect(action.classList.contains('activity-pulse')).toBe(false);
        card.remove();
        vi.useRealTimers();
    });

    it('removes stale activity when the log is cleared', () => {
        const { card, renderer } = setup();
        renderer.updateActivity(card, 0);
        expect(card.querySelector('.activity')).toBeNull();
        expect(card.querySelector('.component-row').getAttribute('aria-label')).toBe('search, Stimulus component');
    });
});
