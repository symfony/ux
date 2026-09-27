import { SENSITIVE_KEY } from '../core/snapshot';
import { el } from './ui-helpers';

export class TreeViewer {
    static render(data: unknown, maxDepth = 4): HTMLElement {
        const container = el('div', { class: 'tree' });
        TreeViewer.#buildNodes(container, data, 0, maxDepth, new WeakSet());
        return container;
    }

    static #buildNodes(
        parent: HTMLElement,
        data: unknown,
        depth: number,
        maxDepth: number,
        seen: WeakSet<object>
    ): void {
        if (data === null || data === undefined) {
            parent.append(el('div', { class: 'row' }, el('span', { class: 'v-nul', text: 'null' })));
            return;
        }
        if (typeof data !== 'object') {
            parent.append(
                el(
                    'div',
                    { class: 'row' },
                    el('span', { class: TreeViewer.#valueClass(data), text: TreeViewer.#formatValue(data) })
                )
            );
            return;
        }
        if (seen.has(data)) {
            parent.append(el('div', { class: 'row' }, el('span', { class: 'type', text: '[circular]' })));
            return;
        }
        seen.add(data);
        if (Array.isArray(data)) {
            parent.classList.add('array');
            for (const rawValue of data) {
                const isObject = rawValue !== null && typeof rawValue === 'object';
                if (isObject && depth < maxDepth) {
                    const nest = el('div', { class: 'nest' });
                    TreeViewer.#buildNodes(nest, rawValue, depth + 1, maxDepth, seen);
                    parent.append(
                        el(
                            'details',
                            { class: 'array-item', open: false },
                            el(
                                'summary',
                                {},
                                el('span', {
                                    class: 'type',
                                    text: Array.isArray(rawValue) ? `Array(${rawValue.length})` : '{...}',
                                })
                            ),
                            nest
                        )
                    );
                } else {
                    const value = isObject
                        ? el('span', { class: 'type', text: '[max depth]' })
                        : el('span', {
                              class: TreeViewer.#valueClass(rawValue),
                              text: TreeViewer.#formatValue(rawValue),
                          });
                    parent.append(el('div', { class: 'row array-item' }, value));
                }
            }
            return;
        }

        const entries = Object.entries(data);

        for (const [key, rawValue] of entries) {
            const value = SENSITIVE_KEY.test(key) ? '[redacted]' : rawValue;
            const isObject = value !== null && typeof value === 'object';

            if (isObject && depth < maxDepth) {
                const nest = el('div', { class: 'nest' });
                TreeViewer.#buildNodes(nest, value, depth + 1, maxDepth, seen);
                parent.append(
                    el(
                        'details',
                        { open: false },
                        el(
                            'summary',
                            {},
                            el('span', { class: 'key', text: key }),
                            el('span', { class: 'colon', text: ':' }),
                            el('span', {
                                class: 'type',
                                text: Array.isArray(value) ? 'Array(' + value.length + ')' : '{...}',
                            })
                        ),
                        nest
                    )
                );
            } else {
                const val = isObject
                    ? el('span', { class: 'type', text: '[max depth]' })
                    : el('span', { class: TreeViewer.#valueClass(value), text: TreeViewer.#formatValue(value) });
                parent.append(
                    el(
                        'div',
                        { class: 'row' },
                        el('span', { class: 'key', text: key }),
                        el('span', { class: 'colon', text: ':' }),
                        val
                    )
                );
            }
        }
    }

    static #valueClass(value: unknown): string {
        if (value === null || value === undefined) return 'v-nul';
        if (typeof value === 'string') return 'v-str';
        if (typeof value === 'number') return 'v-num';
        if (typeof value === 'boolean') return 'v-bool';
        return 'type';
    }

    static #formatValue(value: unknown): string {
        if (value === null || value === undefined) return 'null';
        if (typeof value === 'string') return '"' + value + '"';
        return String(value);
    }
}
