const NS = 'http://www.w3.org/2000/svg';

type IconShape = [tag: string, attrs: Record<string, string>];

const ICONS: Record<string, IconShape[]> = {
    back: [['polyline', { points: '15 6 9 12 15 18' }]],
    forward: [['polyline', { points: '9 6 15 12 9 18' }]],
    target: [
        ['circle', { cx: '12', cy: '12', r: '7' }],
        ['circle', { cx: '12', cy: '12', r: '2' }],
        ['path', { d: 'M12 2v3m0 14v3M2 12h3m14 0h3' }],
    ],
    components: [
        ['path', { d: 'm12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z' }],
        ['path', { d: 'm4 7.5 8 4.5 8-4.5M12 12v9' }],
    ],
    activity: [['path', { d: 'M22 12h-4l-3 9L9 3l-3 9H2' }]],
    overlay: [
        ['path', { d: 'm12 3 9 5-9 5-9-5 9-5Z' }],
        ['path', { d: 'm3 13 9 5 9-5' }],
    ],
    pause: [['path', { d: 'M9 5v14M15 5v14' }]],
    clear: [['path', { d: 'M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5' }]],
    'panel-right': [
        ['rect', { x: '3', y: '3', width: '18', height: '18', rx: '2' }],
        ['path', { d: 'M15 3v18M8 9l3 3-3 3' }],
    ],
    copy: [
        ['rect', { x: '8', y: '8', width: '12', height: '12', rx: '2' }],
        ['path', { d: 'M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2' }],
    ],
};

export function createIcon(name: string): SVGSVGElement {
    const svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('aria-hidden', 'true');
    for (const [tag, attrs] of ICONS[name] ?? []) {
        const child = document.createElementNS(NS, tag);
        for (const [key, value] of Object.entries(attrs)) child.setAttribute(key, value);
        svg.appendChild(child);
    }
    return svg;
}

export function createIconButton(
    name: string,
    label: string,
    onClick?: (event: MouseEvent) => void
): HTMLButtonElement {
    const button = document.createElement('button');
    button.type = 'button';
    button.title = label;
    button.setAttribute('aria-label', label);
    button.appendChild(createIcon(name));
    if (onClick) button.addEventListener('click', onClick);
    return button;
}
