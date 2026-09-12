import { sameSnapshot } from './snapshot';
import type { ComponentData, ComponentDataMap } from '../types';

export class StateManager extends EventTarget {
    #components: Map<Element, ComponentDataMap> = new Map();

    set(element: Element, pluginName: string, data: ComponentData): void {
        let byPlugin = this.#components.get(element);
        const isNew = !byPlugin;

        if (!byPlugin) {
            byPlugin = new Map();
            this.#components.set(element, byPlugin);
        }

        byPlugin.set(pluginName, data);

        this.dispatchEvent(
            new CustomEvent(isNew ? 'component-added' : 'component-updated', {
                detail: { element, pluginName, data, previous: null, current: byPlugin },
            })
        );
    }

    /**
     * Replace all plugin data for an element in one state transition.
     */
    replace(element: Element, byPlugin: ComponentDataMap): void {
        if (!byPlugin.size) {
            this.remove(element);
            return;
        }

        const previous = this.#components.get(element);
        if (
            previous?.size === byPlugin.size &&
            [...byPlugin].every(([name, value]) => {
                const old = previous.get(name);
                return old?.type === value.type && sameSnapshot(old, value);
            })
        )
            return;
        this.#components.set(element, byPlugin);
        const [pluginName, data] = byPlugin.entries().next().value as [string, ComponentData];
        this.dispatchEvent(
            new CustomEvent(previous ? 'component-updated' : 'component-added', {
                detail:
                    byPlugin.size === 1
                        ? { element, pluginName, data, previous, current: byPlugin }
                        : { element, previous, current: byPlugin },
            })
        );
    }

    remove(element: Element): void {
        if (this.#components.delete(element)) {
            this.dispatchEvent(
                new CustomEvent('component-removed', {
                    detail: { element },
                })
            );
        }
    }

    get(element: Element): ComponentDataMap | undefined {
        return this.#components.get(element);
    }

    get elements(): Element[] {
        return [...this.#components.keys()];
    }

    get size(): number {
        return this.#components.size;
    }

    countByPlugin(): Record<string, number> {
        const counts: Record<string, number> = {};
        for (const byPlugin of this.#components.values()) {
            for (const name of byPlugin.keys()) {
                counts[name] = (counts[name] || 0) + 1;
            }
        }
        return counts;
    }

    clear(): void {
        this.#components.clear();
        this.dispatchEvent(new CustomEvent('components-cleared'));
    }

    /** Notify UI that inspected page-level rules changed without changing component counts. */
    notifyPageUpdated(): void {
        this.dispatchEvent(new CustomEvent('page-updated'));
    }
}
