import { describe, expect, it, vi } from 'vitest';
import { DrillStack } from '../../../src/ui/drill-stack';

describe('DrillStack', () => {
    it('pushes details over the root and pops back without replacing it', () => {
        const root = document.createElement('div');
        const row = document.createElement('button');
        root.appendChild(row);
        const stack = new DrillStack(root);
        const detail = document.createElement('div');

        stack.push({ id: 'counter', title: 'counter', typePill: 'Stimulus', content: detail });

        expect(stack.depth).toBe(2);
        expect(root.parentElement.hidden).toBe(true);
        expect(stack.element.classList.contains('is-drilled')).toBe(true);
        stack.pop();
        expect(stack.depth).toBe(1);
        expect(root.parentElement.hidden).toBe(false);
        expect(root.firstElementChild).toBe(row);
    });

    it('lets a parent header pop nested detail levels', () => {
        const stack = new DrillStack(document.createElement('div'));
        const popped = vi.fn();
        stack.addEventListener('drill-pop', popped);
        stack.push({ id: 'parent', title: 'parent', content: document.createElement('div') });
        stack.push({ id: 'child', title: 'child', content: document.createElement('div') });

        stack.element.querySelectorAll('.stack-link')[1].click();

        expect(stack.depth).toBe(2);
        expect(stack.current.id).toBe('parent');
        expect(popped).toHaveBeenCalledOnce();
    });

    it('exposes only the immediate parent as back navigation', () => {
        const stack = new DrillStack(document.createElement('div'));
        stack.push({ id: 'parent', title: 'parent', content: document.createElement('div') });
        stack.push({ id: 'child', title: 'child', content: document.createElement('div') });

        const headers = [...stack.element.querySelectorAll('.stack-link')];

        expect(headers.filter((header) => header.classList.contains('previous'))).toEqual([headers[1]]);
        expect(headers[1].getAttribute('aria-label')).toBe('Back to parent');
        expect(headers[2].getAttribute('aria-current')).toBe('page');
    });

    it('opens details at the top and restores the parent scroll position', () => {
        const stack = new DrillStack(document.createElement('div'));
        const scroller = stack.element.querySelector('.stack-body');
        scroller.scrollTop = 180;

        stack.push({ id: 'detail', title: 'detail', content: document.createElement('div') });

        expect(scroller.scrollTop).toBe(0);
        scroller.scrollTop = 60;
        stack.pop();
        expect(scroller.scrollTop).toBe(180);
    });
});
