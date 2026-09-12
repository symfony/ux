import { el, expandableText } from './ui-helpers';
import { TreeViewer } from './tree-viewer';
import type { ElChild } from './ui-helpers';
import { createIcon } from './icons';
import { safeValue, SENSITIVE_KEY } from '../core/snapshot';
export { safeValue, safeUrl } from '../core/snapshot';

export interface FieldOptions {
    multiline?: boolean;
    vertical?: boolean;
    status?: string;
    changed?: boolean;
    previous?: unknown;
}

export interface ElementFieldOptions {
    badge?: string;
    framework?: string;
    detail?: string | null;
    parameters?: Record<string, unknown>;
}

export interface GroupOptions {
    empty?: boolean;
    key?: string;
    icon?: string;
    open?: boolean;
    collapsed?: boolean;
    static?: boolean;
}

const MAX_DISPLAYED_CLASSES = 2;

export function makeField(key: string, value: unknown, options: FieldOptions = {}): HTMLElement {
    value = SENSITIVE_KEY.test(key) ? '[redacted]' : value;
    const structured = value !== null && typeof value === 'object';
    const row = el('dd', { class: 'value' });
    renderValue(row, value, options);
    if (!options.multiline && !options.vertical && typeof value === 'string' && value.length > 32) expandableText(row);
    if (!options.multiline && !options.vertical && typeof value === 'string' && value.length > 32)
        row.dataset.long = '';
    if (options.status) {
        row.prepend(
            el('span', {
                class: 'value-status',
                dataset: { status: options.status },
                text: options.status.replaceAll('-', ' '),
            })
        );
    }
    return el(
        'div',
        {
            class: 'key-value',
            dataset: {
                fieldKey: key,
                ...(structured ? { structured: '' } : {}),
                ...(options.vertical ? { vertical: '' } : {}),
                ...(options.changed ? { changed: '' } : {}),
            },
            title: options.changed ? `Changed from ${formatInline(safeValue(options.previous, key))}` : null,
        },
        String(key).length > 24
            ? expandableText(el('dt', { class: 'key', text: key }))
            : el('dt', { class: 'key', text: key }),
        structured ? el('dd', { class: 'value-meta', text: structureSummary(value as object) }) : null,
        row
    );
}

export function makeEmptyField(key: string): HTMLElement {
    return el(
        'div',
        {
            class: 'key-value',
            dataset: { fieldKey: key, empty: '' },
        },
        String(key).length > 24
            ? expandableText(el('dt', { class: 'key', text: key }))
            : el('dt', { class: 'key', text: key }),
        el('dd', { class: 'value', 'aria-hidden': 'true' })
    );
}

function renderValue(container: HTMLElement, value: unknown, options: FieldOptions = {}): void {
    container.textContent = '';
    container.className = 'value';
    if (options.multiline && typeof value === 'string') {
        const values = value
            .split(',')
            .map((item) => item.trim())
            .filter(Boolean);
        container.append(
            el(
                'span',
                { class: 'value-list' },
                ...values.map((item) => el('span', { class: 'value-list__item', text: item }))
            )
        );
    } else if (value === '') {
        container.append(el('span', { class: 'nul', text: '(empty)' }));
    } else if (typeof value === 'boolean') {
        container.append(el('span', { class: 'bool', text: String(value) }));
    } else if (value === null || value === undefined) {
        container.append(el('span', { class: 'nul', text: 'null' }));
    } else if (typeof value === 'object') {
        container.append(TreeViewer.render(value, 3));
    } else if (typeof value === 'number') {
        container.append(el('span', { class: 'num', text: String(value) }));
    } else {
        container.textContent = String(value);
        container.classList.add('string');
    }
}

export function makeElementField(labelStr: string, element: Element, options: ElementFieldOptions = {}): HTMLElement {
    const label = options.badge || labelStr;
    const emit = (target: EventTarget | null, name: string) =>
        target?.dispatchEvent(
            new CustomEvent(name, {
                bubbles: true,
                composed: true,
                detail: { element, framework: options.framework, label },
            })
        );
    const pill = expandableText(
        el(
            'button',
            {
                class: 'target-pill',
                type: 'button',
                'aria-label': `${labelStr}: show element on page`,
                'aria-pressed': 'false',
                on: {
                    pointerenter: (event: Event) => emit(event.currentTarget, 'preview-element'),
                    pointerleave: (event: Event) => emit(event.currentTarget, 'clear-element-preview'),
                    focus: (event: Event) => emit(event.currentTarget, 'preview-element'),
                    blur: (event: Event) => emit(event.currentTarget, 'clear-element-preview'),
                    click: (event: Event) => {
                        event.stopPropagation();
                        element.scrollIntoView({ block: 'center', behavior: 'smooth' });
                        (event.currentTarget as HTMLElement | null)?.setAttribute('aria-pressed', 'true');
                        emit(event.currentTarget, 'select-element');
                    },
                },
            },
            describeElementPill(element)
        )
    );
    const field = el(
        'div',
        { class: 'key-value', dataset: { fieldKey: `element:${labelStr}`, element: '' } },
        labelStr.length > 24
            ? expandableText(el('dt', { class: 'key', text: labelStr }))
            : el('dt', { class: 'key', text: labelStr }),
        el(
            'dd',
            { class: 'value' },
            options.detail && options.detail !== 'connected'
                ? expandableText(el('span', { class: 'value-note', text: options.detail }))
                : null,
            pill
        )
    );
    const parameters = Object.entries(options.parameters || {});
    if (!parameters.length) return field;
    const parameterFields = parameters.map(([key, value]) => {
        const parameter = makeField(key, value);
        parameter.classList.add('action-parameter');
        return parameter;
    });
    return el('dl', { class: 'compound-field' }, field, ...parameterFields);
}

export function makeKeyValueList(children: ElChild[], options: { class?: string } = {}): HTMLElement | null {
    const nodes = children.filter(Boolean) as ElChild[];
    if (!nodes.length) return null;
    return el(
        'dl',
        {
            class: ['key-values', options.class].filter(Boolean).join(' '),
        },
        ...nodes
    );
}

function formatInline(value: unknown): string {
    const safe = safeValue(value);
    if (safe && typeof safe === 'object') return JSON.stringify(safe);
    return String(safe ?? 'null');
}

function structureSummary(value: object): string {
    const count = Array.isArray(value) ? value.length : Object.keys(value).length;
    return `${Array.isArray(value) ? 'Array' : 'Object'} · ${count}`;
}

export function makeGroup(title: string, children: ElChild[], options: GroupOptions = {}): HTMLElement | null {
    const nodes = children.filter(Boolean) as ElChild[];
    const empty = !nodes.length;
    if (empty && !options.empty) return null;
    const body = empty ? null : el('div', { class: 'content' }, ...nodes);
    const key =
        options.key ||
        title
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-|-$/g, '');
    if (options.static) {
        return el(
            'section',
            {
                class: 'group',
                dataset: { group: key, static: '' },
            },
            el(
                'div',
                { class: 'title' },
                options.icon ? el('span', { class: 'icon' }, createIcon(options.icon)) : null,
                el('span', { class: 'name', text: title })
            ),
            body
        );
    }
    return el(
        'details',
        {
            class: 'group',
            open: empty ? false : (options.open ?? !options.collapsed),
            dataset: { group: key, ...(empty ? { empty: '' } : {}) },
        },
        el(
            'summary',
            {
                class: 'title',
                'aria-disabled': empty ? 'true' : null,
                'aria-label': empty ? `${title}, none` : null,
                tabindex: empty ? -1 : null,
                on: empty ? { click: (event: Event) => event.preventDefault() } : null,
            },
            options.icon ? el('span', { class: 'icon' }, createIcon(options.icon)) : null,
            el('span', { class: 'name', text: title })
        ),
        body
    );
}

function describeElementPill(element: Element): DocumentFragment {
    const tag = element.tagName.toLowerCase();
    const id = element.id ? '#' + element.id : '';
    const cls =
        element.className && typeof element.className === 'string'
            ? '.' + element.className.trim().split(/\s+/).slice(0, MAX_DISPLAYED_CLASSES).join('.')
            : '';
    const frag = document.createDocumentFragment();
    frag.append(el('span', { class: 'tag', text: tag }), el('span', { class: 'cls', text: id + cls }));
    return frag;
}
