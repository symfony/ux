import { ComponentCard } from './component-card';
import { componentIdentity, createEmptyState, el, reconcileChildren } from './ui-helpers';
import type { PluginRegistry } from '../core/plugin-registry';
import type { EventMonitor } from '../core/event-monitor';
import type { StateManager } from '../core/state-manager';
import type { PageRule } from '../types';
import type { VisualCallbacks, VisualTarget } from './visual-target';

interface ComponentRow {
    element: HTMLElement;
    signature: string;
}

/** Renders and reconciles the searchable component list without replacing unchanged rows. */
export class ComponentList {
    readonly element = el('div', { class: 'components', 'aria-live': 'polite' });
    #state: StateManager;
    #registry: PluginRegistry;
    #eventMonitor: EventMonitor | null;
    #visual: VisualCallbacks;
    #cardRenderer: ComponentCard;
    #rows = new Map<Element, ComponentRow>();
    #targets = new WeakMap<Element, VisualTarget>();
    #lifetime = new AbortController();

    constructor(
        state: StateManager,
        registry: PluginRegistry,
        eventMonitor: EventMonitor | null,
        visual: VisualCallbacks
    ) {
        this.#state = state;
        this.#registry = registry;
        this.#eventMonitor = eventMonitor;
        this.#visual = visual;
        this.#cardRenderer = new ComponentCard(registry, eventMonitor);
        const { signal } = this.#lifetime;
        this.element.addEventListener('keydown', (event) => this.#onComponentKeydown(event), { signal });
        for (const type of ['click', 'pointerover', 'pointerout', 'focusin', 'focusout'])
            this.element.addEventListener(type, (event) => this.#interact(event), { signal });
    }

    refresh(filters: Set<string>, query: string, selected: Element | null): void {
        const nodes: HTMLElement[] = [];
        const pageRules = this.#pageRules(filters, query);
        if (pageRules) nodes.push(pageRules);
        const nextRows = new Map<Element, ComponentRow>();
        for (const element of this.#state.elements) {
            const dataMap = this.#state.get(element);
            if (!dataMap || ![...dataMap.keys()].some((name) => filters.has(name))) continue;
            const identity = componentIdentity(element, dataMap, this.#registry);
            const signature = identity.search;
            if (query && !signature.toLowerCase().includes(query)) continue;
            const previous = this.#rows.get(element);
            const row =
                previous?.signature === signature
                    ? previous
                    : { element: this.#cardRenderer.render(element, dataMap, identity), signature };
            this.#targets.set(row.element, { element, framework: dataMap.keys().next().value });
            nextRows.set(element, row);
            nodes.push(row.element);
        }
        this.#rows = nextRows;
        this.select(selected);
        if (!nodes.length)
            nodes.push(
                createEmptyState(
                    this.#state.size ? 'No matching components' : 'No UX components detected',
                    this.#state.size
                        ? 'Change the search or framework filters.'
                        : 'Pick a component from the page or interact with it to begin.'
                )
            );
        reconcileChildren(this.element, nodes);
    }

    clearActivities(): void {
        for (const row of this.#rows.values()) this.#cardRenderer.updateActivity(row.element, 0);
    }

    #pageRules(filters: Set<string>, query: string): HTMLElement | null {
        if (!filters.has('turbo')) return null;
        const rules = (this.#registry.collectPageRules?.() ?? []).filter((rule) =>
            `${rule.kind} ${rule.label} ${rule.detail ?? ''}`.toLowerCase().includes(query)
        );
        if (!rules.length) return null;
        return el(
            'section',
            { class: 'group', dataset: { pageRules: '' } },
            el('h2', { class: 'title', text: 'Turbo page rules' }),
            el('div', { class: 'content' }, ...rules.map((rule) => this.#pageRule(rule)))
        );
    }

    #pageRule(rule: PageRule): HTMLButtonElement {
        const target = { element: rule.element, framework: rule.framework, label: rule.label };
        const row = el(
            'button',
            { class: 'key-value page-rule', type: 'button', 'aria-pressed': 'false' },
            el('strong', { class: 'key', text: rule.label }),
            rule.detail ? el('span', { class: 'value', text: rule.detail }) : null
        );
        this.#targets.set(row, target);
        return row;
    }

    destroy(): void {
        this.#lifetime.abort();
        this.#cardRenderer.destroy();
        this.#rows.clear();
        this.#targets = new WeakMap();
        this.element.replaceChildren();
    }

    #interact(event: Event): void {
        const row = (event.target as Element).closest('.component, .page-rule');
        const target = row && this.#targets.get(row);
        if (!target) return;
        const related = (event as MouseEvent | FocusEvent).relatedTarget;
        if (related instanceof Node && row!.contains(related)) return;
        if (event.type === 'click') {
            if (row!.matches('.component'))
                this.element.dispatchEvent(new CustomEvent('drill-into', { detail: target }));
            else {
                target.element.scrollIntoView({ block: 'center', behavior: 'smooth' });
                row!.setAttribute('aria-pressed', 'true');
                this.#visual.onSelect?.(target);
            }
        } else if (event.type === 'pointerover' || event.type === 'focusin') this.#visual.onPreview?.(target);
        else this.#visual.onClearPreview?.();
    }

    updateActivity(component: Element): void {
        const row = this.#rows.get(component)?.element;
        if (!row) return;
        const entries = this.#eventMonitor?.project(component) ?? [];
        const latest = entries.at(-1);
        this.#cardRenderer.updateActivity(row, entries.length, latest?.label || latest?.event || '');
    }

    select(element: Element | null): void {
        for (const [candidate, { element: card }] of this.#rows) {
            const selected = candidate === element;
            card.classList.toggle('selected', selected);
            const action = card.querySelector('.component-row');
            if (selected) action?.setAttribute('aria-current', 'page');
            else action?.removeAttribute('aria-current');
        }
    }

    #onComponentKeydown(event: KeyboardEvent): void {
        if (!['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
        const rows = [...this.element.querySelectorAll<HTMLElement>('.component-row')];
        if (!rows.length) return;
        const current = rows.indexOf((event.target as Element)?.closest?.('.component-row') as HTMLElement);
        if (current < 0) return;
        const next =
            event.key === 'Home'
                ? 0
                : event.key === 'End'
                  ? rows.length - 1
                  : Math.min(rows.length - 1, Math.max(0, current + (event.key === 'ArrowDown' ? 1 : -1)));
        event.preventDefault();
        rows[next].focus();
    }
}
