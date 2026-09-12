import { renderComponent } from './render-component';
import { sameSnapshot } from '../core/snapshot';
import { componentIdentity, componentLabel, el, expandableText, frameworkName } from './ui-helpers';
import { createIcon } from './icons';
import { makeGroup } from './fields';
import type { PluginRegistry } from '../core/plugin-registry';
import type { EventMonitor } from '../core/event-monitor';
import type { GraphEdge, RelationshipEngine } from '../core/relationship-engine';
import type { StateManager } from '../core/state-manager';
import type { ActivityEntry, ComponentData, ComponentDataMap } from '../types';

export interface ComponentDetailServices {
    registry: PluginRegistry;
    eventMonitor: EventMonitor | null;
    relationshipEngine: RelationshipEngine | null;
    state: StateManager;
    render?: typeof renderComponent;
}

export interface DetailUiState {
    groups?: Record<string, boolean>;
}

interface FieldChange {
    previous: unknown;
    current: unknown;
}

type RelationKind = 'parents' | 'children' | 'outlets' | 'related';

export class ComponentDetail {
    #registry: PluginRegistry;
    #render: typeof renderComponent;
    #eventMonitor: EventMonitor | null;
    #relationshipEngine: RelationshipEngine | null;
    #state: StateManager;
    #onDrillInto: ((element: Element) => void) | null;
    #element: HTMLElement | null = null;
    #target: Element | null = null;
    #identityKey = '';
    #eventListener: ((entry: ActivityEntry) => void) | null = null;
    #lifetime = new AbortController();
    #previousData: ComponentDataMap | null = null;
    #recentChanges: Map<string, FieldChange> = new Map();
    #changeTimer: ReturnType<typeof setTimeout> | undefined;
    #savedGroups: Map<string, boolean> = new Map();

    constructor(
        { registry, eventMonitor, relationshipEngine, state, render = renderComponent }: ComponentDetailServices,
        onDrillInto: ((element: Element) => void) | null,
        uiState: DetailUiState = {}
    ) {
        this.#registry = registry;
        this.#render = render;
        this.#eventMonitor = eventMonitor;
        this.#relationshipEngine = relationshipEngine;
        this.#state = state;
        this.#onDrillInto = onDrillInto;
        this.#savedGroups = new Map(Object.entries(uiState.groups || {}));
    }

    render(target: Element, dataMap: ComponentDataMap): HTMLElement {
        this.destroy();
        this.#lifetime = new AbortController();
        this.#target = target;
        this.#previousData = dataMap;
        this.#element = el('div', { class: 'detail pane' });
        this.#replaceContent(dataMap, null);

        if (this.#eventMonitor) {
            this.#eventListener = (entry: ActivityEntry) => {
                if (entry.target !== target && !(entry.target && target.contains(entry.target))) return;
                const footer = this.#element?.querySelector('.activity-label') as HTMLElement | null;
                if (footer) footer.dataset.framework = entry.type || 'default';
            };
            this.#eventMonitor.addListener(this.#eventListener);
        }
        const onUpdate = (event: Event) => {
            const detail = (event as CustomEvent).detail as
                | { element?: Element; previous?: ComponentDataMap }
                | undefined;
            if (detail?.element !== target) return;
            const current = this.#state.get(target);
            if (!current) return;
            const previous = detail.previous || this.#previousData;
            this.#previousData = current;
            this.#replaceContent(current, previous ?? null);
        };
        this.#state.addEventListener?.('component-updated', onUpdate, { signal: this.#lifetime.signal });
        return this.#element;
    }

    destroy(): void {
        this.#rememberGroups();
        if (this.#eventListener) this.#eventMonitor?.removeListener(this.#eventListener);
        this.#eventListener = null;
        this.#lifetime.abort();
        clearTimeout(this.#changeTimer);
        this.#recentChanges.clear();
        this.#element = this.#target = this.#previousData = null;
        this.#identityKey = '';
    }

    getUiState(): DetailUiState {
        this.#rememberGroups();
        return {
            groups: Object.fromEntries(this.#savedGroups),
        };
    }

    get activityCount(): number {
        const target = this.#target;
        return (target ? (this.#eventMonitor?.project(target) ?? []) : []).length;
    }

    #identity(target: Element, dataMap: ComponentDataMap): HTMLElement {
        const { name, framework, selector } = componentIdentity(target, dataMap, this.#registry);
        const identity = name ?? selector;
        const key = JSON.stringify([identity, framework, selector]);
        if (key === this.#identityKey) return this.#element?.firstElementChild as HTMLElement;
        this.#identityKey = key;
        const meaningfulSelector = framework !== 'livecomponent' && selector !== target.localName;
        const title = el('h2', { text: identity });
        const subtitle = meaningfulSelector ? el('p', { class: 'detail-selector', text: selector }) : null;
        return el(
            'section',
            { class: 'detail-head' },
            identity.length > 24 ? expandableText(title) : title,
            el('span', { class: 'framework', dataset: { framework }, text: frameworkName(framework) }),
            subtitle && selector.length > 32 ? expandableText(subtitle) : subtitle
        );
    }

    #data(dataMap: ComponentDataMap, previousData: ComponentDataMap | null): HTMLElement {
        const body = el('div', {
            class: 'detail-body',
            id: 'component-detail-controller',
            role: 'region',
            'aria-label': 'Component details',
        });
        const target = this.#target as Element;
        const events = this.#eventMonitor?.getEntriesForElement(target) ?? [];
        let primary = true;
        for (const [name, data] of dataMap) {
            const changes = this.#changes(previousData?.get(name), data);
            const plugin = this.#registry.get(name);
            let rendered = this.#render(name, data, { changes, framework: name, events }) as
                | Element
                | DocumentFragment
                | null
                | undefined;
            let groups = (
                (rendered as Element)?.matches?.('.groups') ? rendered : rendered?.querySelector?.('.groups')
            ) as HTMLElement | null | undefined;
            if (!groups && rendered && (rendered.childNodes.length || rendered.textContent?.trim())) {
                groups = el(
                    'div',
                    { class: 'groups' },
                    makeGroup('Details', [rendered], {
                        key: `${name}-details`,
                        icon: 'components',
                    })
                );
                rendered = groups;
            }
            if (!groups) groups = el('div', { class: 'groups' });
            if (primary) this.#mergeGroups(groups, this.#relationships(target, name));
            if (!groups.children.length) {
                primary = false;
                continue;
            }
            const detail = el(
                'div',
                { class: 'framework-detail', dataset: { framework: name } },
                expandableText(
                    el('h3', {
                        text: `${frameworkName(name)}: ${plugin?.getDisplayName(target) || componentLabel(target)}`,
                    })
                ),
                groups
            );
            body.appendChild(detail);
            primary = false;
        }
        body.dataset.frameworks = String(body.children.length);
        return body;
    }

    #mergeGroups(groups: HTMLElement, additions: HTMLElement[]): void {
        for (const addition of additions) {
            const existing = groups.querySelector(`:scope > [data-group="${addition.dataset.group}"]`);
            if (!existing) {
                groups.appendChild(addition);
                continue;
            }
            const source = addition.querySelector(':scope > .content');
            const destination = existing.querySelector(':scope > .content');
            if (source && destination) destination.append(...source.childNodes);
        }
    }

    #relationships(target: Element, framework: string): HTMLElement[] {
        const edges = this.#relationshipEngine?.getRelatedTo(target) ?? [];
        const groups: Record<RelationKind, HTMLElement[]> = {
            parents: [],
            children: [],
            outlets: [],
            related: [],
        };
        const seen: Record<string, Set<Element>> = Object.fromEntries(
            Object.keys(groups).map((key) => [key, new Set<Element>()])
        );
        for (const edge of edges) {
            const related = edge.source === target ? edge.target : edge.source;
            const name =
                componentIdentity(related, this.#state.get(related), this.#registry).name ?? componentLabel(related);
            const [kind, detail] = this.#describeRelationship(edge, target);
            if (seen[kind].has(related)) continue;
            seen[kind].add(related);
            const selector = related.id ? `#${related.id}` : componentLabel(related);
            const secondary =
                edge.type === 'outlet' ? selector : selector === related.localName ? detail : `${detail} · ${selector}`;
            groups[kind].push(
                el(
                    'button',
                    {
                        class: 'key-value relation',
                        type: 'button',
                        'aria-label': `${detail} ${name} ${selector}`,
                        on: { click: () => this.#onDrillInto?.(related) },
                    },
                    el('strong', { class: 'key', text: name }),
                    el('small', { class: 'value', text: secondary }),
                    createIcon('forward')
                )
            );
        }
        const definitions: Array<[RelationKind, string]> = [
            ['parents', 'Parent'],
            ['children', 'Children'],
            ['outlets', 'Outlets'],
            ['related', 'Related'],
        ];
        return definitions
            .filter(([key]) => key !== 'outlets' || framework !== 'turbo')
            .map(([key, title]) =>
                groups[key].length
                    ? makeGroup(title, [el('div', { class: 'relations' }, ...groups[key])], {
                          key: `${framework}-${key === 'parents' ? 'parent' : key}`,
                          icon: key === 'outlets' ? 'overlay' : 'components',
                      })
                    : null
            )
            .filter((group): group is HTMLElement => Boolean(group));
    }

    #describeRelationship(edge: GraphEdge, target: Element): [RelationKind, string] {
        const outgoing = edge.source === target;
        if (edge.type === 'outlet') {
            const outlet = edge.label?.split(' -> ').at(-1) || 'component';
            return ['outlets', outgoing ? `${outlet} outlet` : `used by ${outlet} outlet`];
        }
        if (['controller-parent', 'dom-parent', 'live-parent', 'frame-nesting'].includes(edge.type)) {
            return [
                outgoing ? 'children' : 'parents',
                `${outgoing ? 'child' : 'parent'} ${edge.type === 'frame-nesting' ? 'frame' : 'component'}`,
            ];
        }
        return ['related', edge.label || edge.type.replaceAll('-', ' ')];
    }

    #activityFooter(): HTMLElement {
        const target = this.#target;
        const latest = target ? this.#eventMonitor?.getEntriesForElement(target)?.at(-1) : undefined;
        return el(
            'div',
            {
                class: 'activity-label group',
                dataset: { framework: latest?.type || 'default' },
            },
            el(
                'span',
                { class: 'title' },
                el('span', { class: 'icon' }, createIcon('activity')),
                el('span', { class: 'name', text: 'Activity' })
            )
        );
    }

    #replaceContent(dataMap: ComponentDataMap, previousData: ComponentDataMap | null): void {
        const root = this.#element?.getRootNode() as Document | ShadowRoot | undefined;
        const active = root?.activeElement ?? null;
        const activeKey =
            active && this.#element?.contains(active)
                ? (active.closest('.key-value') as HTMLElement | null)?.dataset.fieldKey
                : null;
        this.#rememberGroups();
        const content = this.#data(dataMap, previousData);
        if (!this.#savedGroups.size) {
            const groups = content.querySelectorAll('details.group');
            groups.forEach((group) => {
                (group as HTMLDetailsElement).open = !group.hasAttribute('data-empty');
            });
        }
        const identity = this.#identity(this.#target as Element, dataMap);
        const current = this.#element?.querySelector<HTMLElement>('.detail-body');
        if (current) {
            if (identity !== this.#element?.firstElementChild) this.#element?.firstElementChild?.replaceWith(identity);
            current.replaceChildren(...content.childNodes);
            current.dataset.frameworks = content.dataset.frameworks;
        } else this.#element?.append(identity, content, this.#activityFooter());
        for (const group of this.#element?.querySelectorAll('details.group[data-group]') ?? []) {
            const details = group as HTMLDetailsElement;
            const key = details.dataset.group ?? '';
            if (details.hasAttribute('data-empty')) details.open = false;
            else if (this.#savedGroups.has(key)) details.open = this.#savedGroups.get(key) as boolean;
        }
        if (activeKey) {
            const field = [...(this.#element?.querySelectorAll<HTMLElement>('.key-value') ?? [])].find(
                (candidate) => candidate.dataset.fieldKey === activeKey
            );
            (field?.querySelector('button, [tabindex]') as HTMLElement | null)?.focus({ preventScroll: true });
        }
    }

    #rememberGroups(): void {
        for (const group of this.#element?.querySelectorAll('details.group[data-group]') ?? []) {
            const details = group as HTMLDetailsElement;
            this.#savedGroups.set(details.dataset.group ?? '', details.open);
        }
    }

    #changes(previous: ComponentData | undefined, current: ComponentData): Map<string, FieldChange> {
        if (!previous) return this.#recentChanges;
        let changed = false;
        const compare = (before: unknown, after: unknown, path: string, nested = false) => {
            const a = (before || {}) as Record<string, unknown>;
            const b = (after || {}) as Record<string, unknown>;
            for (const key of new Set([...Object.keys(a), ...Object.keys(b)])) {
                const field = `${path}.${key}`;
                if (nested) compare(a[key], b[key], field);
                else if (!sameSnapshot(a[key], b[key])) {
                    this.#recentChanges.set(field, { previous: a[key], current: b[key] });
                    changed = true;
                }
            }
        };
        for (const group of ['props', 'propsFromParent', 'values']) {
            compare(previous.data?.[group], current.data?.[group], group, group === 'values');
        }
        if (changed) {
            clearTimeout(this.#changeTimer);
            this.#changeTimer = setTimeout(() => {
                this.#recentChanges.clear();
                for (const field of this.#element?.querySelectorAll('[data-changed]') ?? []) {
                    field.removeAttribute('data-changed');
                    field.removeAttribute('title');
                }
            }, 1800);
        }
        return this.#recentChanges;
    }
}
