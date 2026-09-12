import type { PluginRegistry } from './plugin-registry';
import type { StateManager } from './state-manager';
import type { RelationshipEdge } from '../types';

export interface GraphEdge extends RelationshipEdge {
    declared: boolean;
    evidence: string;
}

export class RelationshipEngine {
    #registry: PluginRegistry;
    #state: StateManager;
    #edges: GraphEdge[] = [];
    #dirty = true;
    #lifetime = new AbortController();

    constructor(registry: PluginRegistry, state: StateManager) {
        this.#registry = registry;
        this.#state = state;

        const invalidate = () => {
            this.#dirty = true;
        };
        for (const event of ['component-added', 'component-updated', 'component-removed', 'components-cleared']) {
            state.addEventListener(event, invalidate, { signal: this.#lifetime.signal });
        }
    }

    invalidate(): void {
        this.#dirty = true;
    }

    /** Get all edges (rebuilds if dirty). */
    getEdges(): GraphEdge[] {
        if (this.#dirty) this.#rebuild();
        return [...this.#edges];
    }

    getRelatedTo(element: Element): GraphEdge[] {
        if (this.#dirty) this.#rebuild();
        return this.#edges.filter((e) => e.source === element || e.target === element);
    }

    getEdgesBetween(a: Element, b: Element): GraphEdge[] {
        if (this.#dirty) this.#rebuild();
        return this.#edges.filter((e) => (e.source === a && e.target === b) || (e.source === b && e.target === a));
    }

    getConnectedElements(element: Element): Element[] {
        const related = this.getRelatedTo(element);
        const connected = new Set<Element>();
        for (const edge of related) {
            if (edge.source !== element) connected.add(edge.source);
            if (edge.target !== element) connected.add(edge.target);
        }
        return [...connected];
    }

    getEdgeTypes(): string[] {
        if (this.#dirty) this.#rebuild();
        return [...new Set(this.#edges.map((e) => e.type))];
    }

    destroy(): void {
        this.#lifetime.abort();
        this.#edges = [];
    }

    #rebuild(): void {
        this.#edges = [];
        const elements = new Set(this.#state.elements.filter((el) => el.isConnected));

        for (const element of elements) {
            const dataMap = this.#state.get(element);
            if (!dataMap) continue;

            for (const [pluginName, data] of dataMap) {
                const pluginEdges = this.#registry.collectRelationships(element, data, pluginName);
                for (const edge of pluginEdges) {
                    if (edge.source?.isConnected && edge.target?.isConnected) {
                        this.#edges.push({
                            ...edge,
                            evidence: (edge.evidence as string) || 'declared',
                            declared: true,
                        });
                    }
                }
            }

            this.#addStructuralEdges(element, elements);
        }

        this.#deduplicateEdges();
        this.#dirty = false;
    }

    #addStructuralEdges(element: Element, allElements: Set<Element>): void {
        let parent: Element | null = element.parentElement;
        while (parent) {
            if (allElements.has(parent)) {
                this.#edges.push({
                    source: parent,
                    target: element,
                    type: 'dom-parent',
                    label: 'contains',
                    evidence: 'structural',
                    declared: false,
                });
                break;
            }
            parent = parent.parentElement;
        }
    }

    #deduplicateEdges(): void {
        const seen = new Set<string>();
        const ids = new WeakMap<Element, number>();
        let nextId = 0;
        const elementId = (element: Element): number => {
            if (!ids.has(element)) ids.set(element, ++nextId);
            return ids.get(element) as number;
        };
        this.#edges = this.#edges.filter((edge) => {
            // Prefer declared over structural for same source+target+type
            const key = `${edge.type}:${elementId(edge.source)}:${elementId(edge.target)}`;
            if (seen.has(key)) return false;
            seen.add(key);
            return true;
        });
    }
}
