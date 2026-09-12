import { describe, expect, it, vi } from 'vitest';
import { createIcon, createIconButton } from '../../../src/ui/icons';

describe('icons', () => {
    it('creates SVG nodes without parsing HTML', () => {
        const icon = createIcon('target');
        expect(icon.namespaceURI).toBe('http://www.w3.org/2000/svg');
        expect(icon.querySelectorAll('circle')).toHaveLength(2);
    });

    it('creates an accessible icon button', () => {
        const click = vi.fn();
        const button = createIconButton('overlay', 'Show all', click);
        button.click();
        expect(button.type).toBe('button');
        expect(button.getAttribute('aria-label')).toBe('Show all');
        expect(click).toHaveBeenCalledOnce();
    });
});
