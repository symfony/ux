import type { QueryElements } from './dom-query';
import type {
    ActivityEntry,
    ActivityDraft,
    ActivityInput,
    CollectedEvents,
    ComponentData,
    EventRecorder,
    InspectorPlugin,
    PageRule,
    RelationshipEdge,
} from '../types';
import type { StateManager } from './state-manager';

export type { CollectedEvents, ComponentData, InspectorPlugin, RelationshipEdge };

type HookName = Exclude<keyof InspectorPlugin, 'name' | 'selectors'>;
type Hook<K extends HookName> = NonNullable<InspectorPlugin[K]>;

function safeCallHook<K extends HookName>(
    plugin: InspectorPlugin,
    hookName: K,
    ...args: Parameters<Hook<K>>
): ReturnType<Hook<K>> | undefined {
    const hook = plugin[hookName] as ((...args: Parameters<Hook<K>>) => ReturnType<Hook<K>>) | undefined;
    if (typeof hook !== 'function') return undefined;
    try {
        return hook.apply(plugin, args);
    } catch (error) {
        console.warn(`[ux-inspector] Plugin "${plugin.name}" hook "${hookName}" threw:`, error);
        return undefined;
    }
}

export class PluginRegistry {
    #plugins: Map<string, InspectorPlugin> = new Map();
    #order = ['livecomponent', 'turbo', 'stimulus'];
    #watchedAttributes: Set<string> | null = null;

    constructor(plugins: readonly InspectorPlugin[] = []) {
        this.#plugins = new Map(plugins.map((plugin) => [plugin.name, plugin]));
    }

    get(name: string): InspectorPlugin | undefined {
        return this.#plugins.get(name);
    }

    getAll(): InspectorPlugin[] {
        const rank = (p: InspectorPlugin) => {
            const i = this.#order.indexOf(p.name);
            return i === -1 ? 99 : i;
        };
        return [...this.#plugins.values()].sort((a, b) => rank(a) - rank(b));
    }

    getForElement(element: Element): InspectorPlugin[] {
        return this.getAll().filter((plugin) => safeCallHook(plugin, 'canHandle', element));
    }

    get combinedSelector(): string | null {
        const selectors = this.getAll().flatMap((p) => p.selectors);
        return selectors.length ? selectors.join(', ') : null;
    }

    notifyEvent(entry: ActivityDraft, element: Element): void {
        const plugins = new Set(this.getForElement(element));
        const categoryPlugin = this.#plugins.get(entry.type);
        if (categoryPlugin) plugins.add(categoryPlugin);
        for (const plugin of plugins) {
            safeCallHook(plugin, 'onEvent', entry, element);
        }
    }

    setEventRecorder(record: EventRecorder): void {
        for (const plugin of this.getAll()) safeCallHook(plugin, 'setEventRecorder', record);
    }

    notifyElementRemoved(element: Element, pluginNames: Iterable<string>): void {
        for (const name of pluginNames) {
            const plugin = this.#plugins.get(name);
            if (plugin) safeCallHook(plugin, 'onElementRemoved', element);
        }
    }

    destroy(): void {
        for (const plugin of this.getAll()) safeCallHook(plugin, 'destroy');
    }

    collectRelationships(element: Element, data: ComponentData, pluginName: string | null = null): RelationshipEdge[] {
        const edges: RelationshipEdge[] = [];
        const plugins = pluginName
            ? [this.#plugins.get(pluginName)].filter((p): p is InspectorPlugin => Boolean(p))
            : this.getForElement(element);
        for (const plugin of plugins) {
            const pluginEdges = safeCallHook(plugin, 'getRelationships', element, data);
            if (Array.isArray(pluginEdges)) edges.push(...pluginEdges);
        }
        return edges;
    }

    collectWatchedAttributes(): string[] {
        if (this.#watchedAttributes) return [...this.#watchedAttributes];
        const attrs = new Set<string>();
        for (const plugin of this.#plugins.values()) {
            const extra = safeCallHook(plugin, 'getWatchedAttributes');
            if (Array.isArray(extra)) extra.forEach((a) => attrs.add(a));
        }
        this.#watchedAttributes = attrs;
        return [...attrs];
    }

    isWatchedAttribute(name: string): boolean {
        if (!this.#watchedAttributes) this.collectWatchedAttributes();
        if (this.#watchedAttributes?.has(name)) return true;
        for (const plugin of this.#plugins.values()) {
            if (safeCallHook(plugin, 'matchesAttribute', name) === true) return true;
        }
        return false;
    }

    collectPageRules(doc: Document = document): PageRule[] {
        const rules: PageRule[] = [];
        for (const plugin of this.getAll()) {
            const result = safeCallHook(plugin, 'getPageRules', doc);
            if (Array.isArray(result)) {
                rules.push(...result.map((rule) => ({ ...rule, framework: plugin.name })));
            }
        }
        return rules;
    }

    collectExternalChanges(state: StateManager, query: QueryElements, pending: ReadonlySet<Element>): Element[] {
        const owners: Element[] = [];
        for (const element of state.elements) {
            if (pending.has(element)) continue;
            for (const [name, data] of state.get(element) ?? []) {
                const plugin = this.#plugins.get(name);
                if (plugin && safeCallHook(plugin, 'hasExternalChanges', element, data, query)) {
                    owners.push(element);
                    break;
                }
            }
        }
        return owners;
    }

    /**
     * Collect monitored events from all plugins.
     *
     * @param elementsByPlugin pluginName -> elements handled by that plugin
     */
    collectMonitoredEvents(elementsByPlugin: Map<string, Element[]> = new Map()): CollectedEvents {
        const staticEvents = new Map<string, string[]>();
        const dynamicEvents = new Map<string, string[]>();

        for (const plugin of this.getAll()) {
            const monitored = safeCallHook(plugin, 'getMonitoredEvents');
            if (!monitored) continue;

            if (Array.isArray(monitored.static) && monitored.static.length) {
                staticEvents.set(plugin.name, monitored.static);
            }

            if (typeof monitored.dynamic === 'function') {
                const elements = elementsByPlugin.get(plugin.name) || [];
                try {
                    const names = monitored.dynamic(elements);
                    if (Array.isArray(names)) {
                        dynamicEvents.set(plugin.name, names);
                    }
                } catch (e) {
                    console.warn(`[ux-inspector] Plugin "${plugin.name}" dynamic events threw:`, e);
                }
            }
        }

        return { staticEvents, dynamicEvents };
    }
}

export type { ActivityEntry, ActivityInput, EventRecorder, PageRule };
