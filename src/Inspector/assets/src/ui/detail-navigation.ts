import { ComponentDetail, type DetailUiState } from './component-detail';
import { DrillStack } from './drill-stack';
import { el } from './ui-helpers';
import type { ActivityDrawer } from './activity-drawer';
import type { VisualCallbacks } from './visual-target';
import type { StateManager } from '../core/state-manager';
import type { PluginRegistry } from '../core/plugin-registry';
import type { EventMonitor } from '../core/event-monitor';
import type { RelationshipEngine } from '../core/relationship-engine';
import type { ComponentDataMap } from '../types';

/** A component locator: [id, signature, index, total]. */
type ComponentLocator = [string, string, number, number];

interface DrillRecord {
    detail: ComponentDetail;
    stateKey: string;
    content: HTMLElement;
    locator: ComponentLocator;
}

interface NavigationCallbacks {
    showComponents(): void;
    open(): void;
    change(): void;
    select: NonNullable<VisualCallbacks['onSelect']>;
    clearSelection(): void;
    preview: NonNullable<VisualCallbacks['onPreview']>;
    clearPreview(): void;
}

/** Owns component detail navigation and restoration across page replacement. */
export class DetailNavigation {
    #drillStack: DrillStack;
    #drillDetails = new Map<string, DrillRecord>();
    #nextDrillId = 0;
    #detailStates = new Map<string, DetailUiState>();
    #pendingDetail: ComponentLocator | null = null;
    #restoreQueued = false;
    #destroyed = false;
    #lifetime = new AbortController();

    #state: StateManager;
    #registry: PluginRegistry;
    #eventMonitor: EventMonitor | null;
    #relationshipEngine: RelationshipEngine | null;
    #activity: ActivityDrawer;
    #callbacks: NavigationCallbacks;
    constructor(
        state: StateManager,
        registry: PluginRegistry,
        eventMonitor: EventMonitor | null,
        relationshipEngine: RelationshipEngine | null,
        root: HTMLElement,
        activity: ActivityDrawer,
        callbacks: NavigationCallbacks
    ) {
        this.#state = state;
        this.#registry = registry;
        this.#eventMonitor = eventMonitor;
        this.#relationshipEngine = relationshipEngine;
        this.#activity = activity;
        this.#callbacks = callbacks;
        this.#drillStack = new DrillStack(root);
        this.#drillStack.addEventListener('drill-push', () => callbacks.change());
        this.#drillStack.addEventListener('drill-pop', (event) => this.#onDrillPop((event as CustomEvent).detail));
        state.addEventListener(
            'component-removed',
            (event) => this.#onComponentRemoved((event as CustomEvent).detail?.element),
            { signal: this.#lifetime.signal }
        );
        state.addEventListener('components-cleared', () => this.#onComponentRemoved(this.focusedComponent), {
            signal: this.#lifetime.signal,
        });
    }
    get element(): HTMLElement {
        return this.#drillStack.element;
    }
    get focusedComponent(): Element | null {
        return this.#drillStack.current?.element ?? null;
    }
    get depth(): number {
        return this.#drillStack.depth;
    }
    setRootTitle(title: string): void {
        this.#drillStack.setRootTitle(title);
    }
    drillInto(element: Element, dataMap = this.#state.get(element)): void {
        if (!dataMap) return;
        this.#callbacks.showComponents();
        if (this.#drillStack.current?.element === element) {
            this.restoreActivity();
            this.#callbacks.open();
            return;
        }
        this.#activity.close(false);
        const locator = this.#componentLocator(element, dataMap);
        const stateKey = `${locator[1]}:${locator[2]}`;
        const detail = new ComponentDetail(
            {
                registry: this.#registry,
                eventMonitor: this.#eventMonitor,
                relationshipEngine: this.#relationshipEngine,
                state: this.#state,
            },
            (related) => this.drillInto(related),
            this.#detailStates.get(stateKey)
        );
        const content = el('div', { class: 'drill-detail pane' }, detail.render(element, dataMap));
        content.addEventListener('preview-element', this.#onDetailPreview);
        content.addEventListener('clear-element-preview', this.#onDetailClear);
        content.addEventListener('select-element', this.#onDetailSelect);
        let title = element.tagName.toLowerCase();
        const framework = dataMap.keys().next().value || 'default';
        const plugin = this.#registry.get(framework);
        if (plugin) title = plugin.getDisplayName(element);
        const id = `component-${++this.#nextDrillId}`;
        this.#drillDetails.set(id, { detail, stateKey, content, locator });
        this.#drillStack.push({
            id,
            title,
            typePill:
                framework === 'livecomponent'
                    ? undefined
                    : element.tagName.toLowerCase() + (element.id ? `#${element.id}` : ''),
            content,
            element,
            framework,
        });

        this.#callbacks.open();
        this.#callbacks.select({ element, framework });
        this.#activity.open(id, content, element, title);
    }

    drillBack(): boolean {
        return Boolean(this.#drillStack.pop());
    }

    restoreActivity(): void {
        const current = this.#drillStack.current;
        const record = current && this.#drillDetails.get(current.id);
        if (this.element.hidden || !current?.element || !record || this.#activity.id === current.id) return;
        this.#activity.open(current.id, record.content, current.element, current.title);
    }

    clearFocus(): boolean {
        let cleared = false;
        while (this.drillBack()) cleared = true;
        return cleared;
    }

    suspendForNavigation(): void {
        const current = this.#drillStack.current;
        const dataMap = current?.element ? this.#state.get(current.element) : null;
        if (dataMap && current?.element) this.#pendingDetail = this.#componentLocator(current.element, dataMap);
        while (this.drillBack()) {}
    }

    resumeAfterNavigation(): void {
        this.#restorePendingDetail();
    }

    destroy(): void {
        this.#destroyed = true;
        this.#lifetime.abort();
        for (const id of this.#drillDetails.keys()) this.#destroyDrillDetail(id);
    }

    #componentLocator(element: Element, dataMap: ComponentDataMap): ComponentLocator {
        const signature = this.#componentSignature(element, dataMap);
        const candidates = this.#state.elements.filter((candidate) => {
            const current = this.#state.get(candidate);
            return current && this.#componentSignature(candidate, current) === signature;
        });
        return [element.id, signature, Math.max(0, candidates.indexOf(element)), candidates.length];
    }

    #componentSignature(element: Element, dataMap: ComponentDataMap): string {
        return `${element.tagName.toLowerCase()}|${[...dataMap.keys()]
            .map((name) => {
                const plugin = this.#registry.get(name);
                return `${name}:${plugin?.getDisplayName(element) || element.tagName.toLowerCase()}`;
            })
            .join('|')}`;
    }

    #onComponentRemoved(element: Element | null | undefined): void {
        const current = this.#drillStack.current;
        if (!element || !current || current.element !== element) return;
        const record = this.#drillDetails.get(current.id);
        if (record) this.#pendingDetail = record.locator;
        while (this.drillBack()) {}
        if (this.#restoreQueued) return;
        this.#restoreQueued = true;
        queueMicrotask(() => {
            this.#restoreQueued = false;
            this.#restorePendingDetail();
        });
    }

    #restorePendingDetail(): boolean {
        if (this.#destroyed) return false;
        if (!this.#pendingDetail) return false;
        const [id, signature, ordinal, count] = this.#pendingDetail;
        this.#pendingDetail = null;
        let match: Element | null = null;
        if (id) {
            const candidate = document.getElementById(id);
            const dataMap = candidate ? this.#state.get(candidate) : null;
            if (candidate && dataMap && this.#componentSignature(candidate, dataMap) === signature) match = candidate;
        }
        if (!match) {
            const candidates = this.#state.elements.filter((element) => {
                const dataMap = this.#state.get(element);
                return dataMap && this.#componentSignature(element, dataMap) === signature;
            });
            if (candidates.length === count) match = candidates[ordinal] || null;
        }
        if (match) this.drillInto(match);
        return Boolean(match);
    }

    #onDrillPop({ removed, current }: { removed: { id: string }; current: { element?: Element } }): void {
        this.#destroyDrillDetail(removed.id);
        this.restoreActivity();
        this.#callbacks.change();
        if (current.element) {
            const dataMap = this.#state.get(current.element);
            this.#callbacks.select({ element: current.element, framework: dataMap?.keys().next().value || 'default' });
        } else {
            this.#callbacks.clearSelection();
            this.#callbacks.change();
        }
    }

    #destroyDrillDetail(id: string): void {
        const record = this.#drillDetails.get(id);
        if (!record) return;
        this.#detailStates.set(record.stateKey, record.detail.getUiState());
        record.content.removeEventListener('preview-element', this.#onDetailPreview);
        record.content.removeEventListener('clear-element-preview', this.#onDetailClear);
        record.content.removeEventListener('select-element', this.#onDetailSelect);
        if (this.#activity.id === id) this.#activity.close();
        record.detail.destroy();
        this.#drillDetails.delete(id);
    }

    #onDetailPreview = (event: Event) => this.#callbacks.preview((event as CustomEvent).detail);
    #onDetailClear = () => this.#callbacks.clearPreview();
    #onDetailSelect = (event: Event) => this.#callbacks.select((event as CustomEvent).detail);
}
