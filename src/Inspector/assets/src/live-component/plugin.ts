import { ancestors, scopedChildren } from '../core/dom-query';
import { LiveObserver, type RuntimeInfo } from './observer';
import { parseActionDescriptor, parseActionParameters } from '../stimulus/attributes';
import { safeUrl, safeValue } from '../core/snapshot';
import type {
    ActivityDraft,
    ComponentData,
    EventRecorder,
    InspectorPlugin,
    MonitoredEvents,
    RelationshipEdge,
} from '../types';

interface ModelBinding {
    name: string;
    modifiers: string[];
    value: unknown;
    element: Element;
}

interface ActionBinding {
    event: string;
    method: string;
    args: Record<string, unknown>;
    element: Element;
}

interface ListenerBinding {
    event: string;
    action: string;
}

interface ComponentRef {
    element: Element;
    name: string;
}

export interface LiveData {
    name: string;
    url: string;
    fingerprint: string;
    props: Record<string, unknown>;
    propsFromParent: Record<string, unknown>;
    listeners: ListenerBinding[];
    polling: { duration: string } | null;
    models: ModelBinding[];
    actions: ActionBinding[];
    loading: Array<{ action: string; element: Element }>;
    children: ComponentRef[];
    parents: ComponentRef[];
    otherControllers: string[];
    runtime: RuntimeInfo;
    [key: string]: unknown;
}

export class LiveComponentPlugin implements InspectorPlugin<LiveData> {
    name = 'livecomponent';
    selectors = ['[data-controller~="live"]'];
    #observer = new LiveObserver();

    setEventRecorder(record: EventRecorder): void {
        this.#observer.setEventRecorder(record);
    }
    observe(element: Element): void {
        this.#observer.observe(element);
    }

    canHandle(element: Element): boolean {
        const controller = element.getAttribute('data-controller');
        return controller?.split(/\s+/).includes('live') ?? false;
    }

    parse(element: Element): ComponentData<LiveData> {
        const reference = (element: Element): ComponentRef => ({
            element,
            name: element.getAttribute('data-live-name-value') || '',
        });
        return {
            type: 'livecomponent',
            element,
            data: {
                name: this.#parseName(element),
                url: safeUrl(element.getAttribute('data-live-url-value')),
                fingerprint: element.getAttribute('data-live-fingerprint-value') || '',
                props: this.#parseProps(element),
                propsFromParent: this.#parsePropsFromParent(element),
                listeners: this.#parseListeners(element),
                polling: this.#parsePolling(element),
                models: this.#resolveModels(element),
                actions: this.#resolveActions(element),
                loading: this.#resolveLoading(element),
                children: scopedChildren(element, '[data-controller~="live"]').map(reference),
                parents: ancestors(element, '[data-controller~="live"]').map(reference),
                otherControllers: this.#otherControllers(element),
                runtime: this.#observer.read(element),
            },
        };
    }

    getDisplayName(element: Element): string {
        return this.#parseName(element) || 'LiveComponent';
    }

    getRelationships(element: Element, data: ComponentData<LiveData>): RelationshipEdge[] {
        const edges: RelationshipEdge[] = [];
        const d = data.data;

        for (const child of d.children) {
            if (child.element?.isConnected) {
                edges.push({
                    source: element,
                    target: child.element,
                    type: 'live-parent',
                    label: `parent of ${child.name || 'LiveComponent'}`,
                });
            }
        }

        return edges;
    }

    matchesAttribute(name: string): boolean {
        return (
            ['data-controller', 'data-model', 'data-loading', 'data-poll'].includes(name) ||
            name.startsWith('data-live-')
        );
    }

    getMonitoredEvents(): MonitoredEvents {
        return {
            static: ['live:connect', 'live:disconnect'],
        };
    }

    onEvent(entry: ActivityDraft, element: Element): void {
        if ('live:disconnect' === entry.event) {
            this.#observer.disconnected(element);
            const name = this.#parseName(element) || 'LiveComponent';
            entry.label = `${name}: disconnect`;
            entry.detail = null;
            return;
        }
        if ('live:connect' === entry.event) {
            this.#observer.connected(element);
            const name = this.#parseName(element) || 'LiveComponent';
            entry.label = `${name}: connect`;
            entry.detail = null;
        }
    }

    destroy(): void {
        this.#observer.destroy();
    }

    onElementRemoved(element: Element): void {
        this.#observer.remove(element);
    }

    #parseName(element: Element): string {
        return (element.getAttribute('data-live-name-value') || '').slice(0, 200);
    }

    #parseProps(element: Element): Record<string, unknown> {
        const raw = element.getAttribute('data-live-props-value');
        if (!raw) return {};
        try {
            return safeValue(JSON.parse(raw)) as Record<string, unknown>;
        } catch {
            return { _raw: safeValue(raw) };
        }
    }

    #parsePropsFromParent(element: Element): Record<string, unknown> {
        const raw = element.getAttribute('data-live-props-from-parent-value');
        if (!raw) return {};
        try {
            return safeValue(JSON.parse(raw)) as Record<string, unknown>;
        } catch {
            return {};
        }
    }

    #parseListeners(element: Element): ListenerBinding[] {
        const raw = element.getAttribute('data-live-listeners-value');
        if (!raw) return [];
        try {
            const parsed = safeValue(JSON.parse(raw));
            return Array.isArray(parsed) ? (parsed as ListenerBinding[]) : [];
        } catch {
            return [];
        }
    }

    #parsePolling(element: Element): { duration: string } | null {
        const polling = element.getAttribute('data-poll');
        if (!polling && !element.hasAttribute('data-poll')) return null;
        const match = polling?.match(/delay\((\d+)\)/);
        return {
            duration: match ? `${match[1]}ms` : '2000ms',
        };
    }

    /**
     * Find data-model bindings within the component scope.
     * data-model="fieldName" or data-model="on(change)|fieldName"
     */
    #resolveModels(element: Element): ModelBinding[] {
        return Array.from(element.querySelectorAll('[data-model]'))
            .filter((c) => this.#isInLiveScope(c, element) && c.getAttribute('data-model'))
            .map((c) => {
                const model = this.#parseModelValue(c.getAttribute('data-model') ?? '');
                return { ...model, value: this.#readModelValue(c, model.name), element: c };
            });
    }

    /**
     * Find live#action references within the component scope.
     * data-action="click->live#save" or data-action="live#save"
     */
    #resolveActions(element: Element): ActionBinding[] {
        const actions: ActionBinding[] = [];
        const candidates = element.querySelectorAll('[data-action]');
        const allElements = [element, ...candidates];

        for (const candidate of allElements) {
            const raw = candidate.getAttribute('data-action');
            if (!raw) continue;
            if (!this.#isInLiveScope(candidate, element)) continue;

            const descriptors = raw.split(/\s+/).filter(Boolean);
            for (const descriptor of descriptors) {
                const parsed = parseActionDescriptor(descriptor);
                if (parsed && parsed.controller === 'live') {
                    actions.push({
                        event: parsed.event || 'click',
                        method:
                            parsed.method === 'action'
                                ? candidate.getAttribute('data-live-action-param') || parsed.method
                                : parsed.method,
                        args: parseActionParameters(candidate, 'live', ['', 'action']),
                        element: candidate,
                    });
                }
            }
        }

        return actions;
    }

    #readModelValue(element: Element, name: string): unknown {
        const input = element as HTMLInputElement;
        if (/password|secret|token|csrf/i.test(name) || input.type === 'password') return '[redacted]';
        if (element instanceof HTMLInputElement && ['checkbox', 'radio'].includes(element.type)) return element.checked;
        if ('value' in element) return safeValue(input.value, name);
        return null;
    }

    /**
     * Find data-loading directives within the component scope.
     * data-loading="show", data-loading="hide", data-loading="addClass(loading)"
     */
    #resolveLoading(element: Element): Array<{ action: string; element: Element }> {
        return Array.from(element.querySelectorAll('[data-loading]'))
            .filter((c) => this.#isInLiveScope(c, element))
            .map((c) => ({ action: c.getAttribute('data-loading') || 'show', element: c }));
    }

    /**
     * Check if a candidate element is within this live component's scope
     * (not inside a nested live component).
     */
    #isInLiveScope(candidate: Element, root: Element): boolean {
        if (candidate === root) return true;
        const nearestLive = candidate.closest?.('[data-controller~="live"]');
        return nearestLive === root;
    }

    /**
     * Parse a data-model value: "on(change)|fieldName" -> { name, modifiers }
     */
    #parseModelValue(raw: string): { name: string; modifiers: string[] } {
        const parts = raw.split('|');
        if (parts.length === 1) {
            return { name: parts[0].trim(), modifiers: [] };
        }
        const name = (parts.pop() as string).trim();
        const modifiers = parts.map((p) => p.trim()).filter(Boolean);
        return { name, modifiers };
    }

    #otherControllers(element: Element): string[] {
        return (element.getAttribute('data-controller') || '').split(/\s+/).filter((c) => c && c !== 'live');
    }
}
