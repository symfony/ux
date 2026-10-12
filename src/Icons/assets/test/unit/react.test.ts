import { createElement } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, test, vi } from 'vitest';
import type { UXIconData } from '../../src/index';
import { UXIcon } from '../../src/react';

const icon: UXIconData = {
    name: 'lucide:trash-2',
    attributes: { viewBox: '0 0 24 24', class: 'icon', 'stroke-width': '1.5', style: 'color: red; --size: 2em' },
    children: [
        ['g', { 'stroke-linecap': 'round', class: 'inner' }, [['path', { d: 'M3 6h18' }]]],
        ['title', {}, ['Trash']],
    ],
};

describe('UXIcon', () => {
    test('converts HTML attribute names to React prop names', () => {
        const element = UXIcon({
            icon: {
                ...icon,
                attributes: {
                    class: 'icon',
                    'stroke-width': '1.5',
                    'xlink:href': '#a',
                    tabindex: '0',
                    'aria-label': 'Delete',
                    'data-id': '1',
                    style: 'background-color: red; --size: 2em',
                },
            },
        });

        expect(element.props).toMatchObject({
            className: 'icon',
            strokeWidth: '1.5',
            xlinkHref: '#a',
            tabIndex: '0',
            'aria-label': 'Delete',
            'data-id': '1',
            style: { backgroundColor: 'red', '--size': '2em' },
        });
    });

    test('renders the icon with dumped attributes', () => {
        const html = renderToStaticMarkup(createElement(UXIcon, { icon }));

        expect(html).toBe(
            '<svg viewBox="0 0 24 24" class="icon" stroke-width="1.5" style="color:red;--size:2em" aria-hidden="true"><g stroke-linecap="round" class="inner"><path d="M3 6h18"></path></g><title>Trash</title></svg>'
        );
    });

    test('React props override dumped attributes', () => {
        const html = renderToStaticMarkup(
            createElement(UXIcon, { icon, className: 'text-danger', strokeWidth: 2, 'aria-label': 'Delete' })
        );

        expect(html).toContain('class="text-danger"');
        expect(html).toContain('stroke-width="2"');
        expect(html).toContain('aria-label="Delete"');
        expect(html).not.toContain('aria-hidden');
    });

    test('renders children as React elements, without dangerouslySetInnerHTML', () => {
        const element = UXIcon({ icon });

        expect(element.props).not.toHaveProperty('dangerouslySetInnerHTML');
        expect(element.props.children[0].type).toBe('g');
        expect(element.props.children[0].props).toMatchObject({ strokeLinecap: 'round', className: 'inner' });
    });

    test('non-primitive props are passed as-is', () => {
        const onClick = vi.fn();
        const element = UXIcon({ icon, onClick, style: { color: 'blue' } });

        expect(element.props.onClick).toBe(onClick);
        expect(element.props.style).toEqual({ color: 'blue' });
    });
});
