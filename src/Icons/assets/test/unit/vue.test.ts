import { describe, expect, test, vi } from 'vitest';
import { createApp, h } from 'vue';
import type { UXIconData } from '../../src/index';
import { UXIcon } from '../../src/vue';

const icon: UXIconData = {
    name: 'lucide:trash-2',
    attributes: { viewBox: '0 0 24 24', class: 'icon', 'stroke-width': '1.5' },
    children: [
        ['g', { 'stroke-linecap': 'round' }, [['path', { d: 'M3 6h18' }]]],
        ['title', {}, ['Trash']],
    ],
};

function mount(attrs: Record<string, unknown> = {}): SVGSVGElement {
    const container = document.createElement('div');
    createApp({ render: () => h(UXIcon, { icon, ...attrs }) }).mount(container);

    return container.querySelector('svg') as SVGSVGElement;
}

describe('UXIcon', () => {
    test('renders the icon with dumped attributes', () => {
        const svg = mount();

        expect(svg.getAttribute('viewBox')).toBe('0 0 24 24');
        expect(svg.getAttribute('class')).toBe('icon');
        expect(svg.getAttribute('stroke-width')).toBe('1.5');
        expect(svg.getAttribute('aria-hidden')).toBe('true');
        expect(svg.innerHTML).toBe('<g stroke-linecap="round"><path d="M3 6h18"></path></g><title>Trash</title>');
        expect((svg.querySelector('path') as Element).namespaceURI).toBe('http://www.w3.org/2000/svg');
    });

    test('attributes override dumped ones', () => {
        const svg = mount({ class: 'text-danger', 'stroke-width': 2, 'aria-label': 'Delete' });

        expect(svg.getAttribute('class')).toBe('text-danger');
        expect(svg.getAttribute('stroke-width')).toBe('2');
        expect(svg.hasAttribute('aria-hidden')).toBe(false);
    });

    test('class arrays and objects keep the Vue semantics', () => {
        const svg = mount({ class: ['a', { b: true, c: false }] });

        expect(svg.getAttribute('class')).toBe('a b');
    });

    test('listeners are passed as-is', () => {
        const onClick = vi.fn();
        mount({ onClick }).dispatchEvent(new MouseEvent('click'));

        expect(onClick).toHaveBeenCalledOnce();
    });
});
