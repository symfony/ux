import type { PluginRegistry } from '../core/plugin-registry';
import type { ComponentDataMap } from '../types';

export type ElChild = Node | string | null | undefined;

export interface ElProps {
    class?: string | null;
    text?: string | null;
    dataset?: Record<string, string> | null;
    style?: Partial<CSSStyleDeclaration> | null;
    on?: Record<string, EventListener> | null;
    [key: string]: unknown;
}

export function el<K extends keyof HTMLElementTagNameMap>(
    tag: K,
    props: ElProps = {},
    ...children: ElChild[]
): HTMLElementTagNameMap[K] {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(props)) {
        if (value == null) continue;
        if (key === 'class') node.className = value as string;
        else if (key === 'text') node.textContent = value as string;
        else if (key === 'dataset' || key === 'style') Object.assign(node[key], value);
        else if (key === 'on') {
            for (const [type, listener] of Object.entries(value as Record<string, EventListener>)) {
                node.addEventListener(type, listener);
            }
        } else if (key in node) (node as unknown as Record<string, unknown>)[key] = value;
        else node.setAttribute(key, String(value));
    }
    node.append(...children.filter((child): child is Node | string => child != null));
    return node;
}

export function expandableText<T extends HTMLElement>(node: T): T {
    node.classList.add('expandable-text');
    node.setAttribute('aria-expanded', 'false');
    const toggle = () => {
        const expanded = node.classList.toggle('expanded');
        node.setAttribute('aria-expanded', String(expanded));
    };
    node.addEventListener('click', toggle);
    if (!node.matches('button, a, input, select, textarea, [contenteditable="true"]')) {
        node.tabIndex = 0;
        node.setAttribute('role', 'button');
        node.addEventListener('keydown', (event: KeyboardEvent) => {
            if (!['Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            toggle();
        });
    }
    return node;
}

export function createEmptyState(message: string, hint?: string): HTMLDivElement {
    return el('div', { class: 'empty' }, el('strong', { text: message }), hint ? el('small', { text: hint }) : null);
}

export function componentLabel(element: Element): string {
    const id = element.id ? `#${element.id}` : '';
    return element.tagName.toLowerCase() + id;
}

export function componentIdentity(element: Element, dataMap: ComponentDataMap | undefined, registry: PluginRegistry) {
    const labels = [element.localName, element.id];
    let framework = 'default';
    let name: string | undefined;
    for (const key of dataMap?.keys() ?? []) {
        const plugin = registry.get(key);
        const label = plugin?.getDisplayName(element);
        labels.push(key, label ?? '');
        if (plugin && name === undefined) {
            framework = key;
            name = label;
        }
    }
    return { name, framework, selector: componentLabel(element), search: labels.filter(Boolean).join(' ') };
}

export function frameworkName(name: string): string {
    return name === 'livecomponent' ? 'Live' : name[0].toUpperCase() + name.slice(1);
}

export function formatElapsedTime(milliseconds: number): string {
    const seconds = Math.max(0, milliseconds) / 1000;
    if (seconds < 10) return `${seconds.toFixed(2)}s`;
    if (seconds < 60) return `${Math.round(seconds)}s`;
    const minutes = Math.floor(seconds / 60);
    const remainder = Math.floor(seconds % 60);
    return `${minutes}m ${String(remainder).padStart(2, '0')}s`;
}

export function reconcileChildren(parent: Element, desired: Node[]): void {
    let current: ChildNode | null = parent.firstChild;
    for (const node of desired) {
        if (node === current) {
            current = current.nextSibling;
            continue;
        }
        // moveBefore keeps the node's state (focus, iframes, media) across the
        // reorder. Engines without it fall back to a plain move.
        if (node.parentNode === parent && parent.moveBefore) parent.moveBefore(node, current);
        else parent.insertBefore(node, current);
    }
    while (current) {
        const next = current.nextSibling;
        current.remove();
        current = next;
    }
}
