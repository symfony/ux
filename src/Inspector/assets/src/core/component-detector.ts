import { createQueryCache, TOOLING_SELECTOR, type QueryElements } from './dom-query';
import type { PluginRegistry } from './plugin-registry';
import type { StateManager } from './state-manager';
import type { ComponentDataMap, MutationChange } from '../types';

const BASE_ATTRIBUTES = new Set([
    'data-controller',
    'data-action',
    'data-model',
    'data-loading',
    'data-poll',
    'data-live-props-value',
    'data-live-name-value',
    'data-live-url-value',
    'data-live-fingerprint-value',
    'data-live-listeners-value',
    'data-live-props-from-parent-value',
    'src',
    'loading',
    'disabled',
    'busy',
    'complete',
]);

export class ComponentDetector {
    #registry: PluginRegistry;
    #state: StateManager;
    #observer: MutationObserver | null = null;
    #inspectorElement: Element;
    #ignoreSelectors: string[];
    #pending: Set<Element> = new Set();
    #flushQueued = false;
    #mutations = new Map<
        Node,
        Map<string, Omit<MutationChange, 'addedNodes' | 'removedNodes'> & { addedNodes: Node[]; removedNodes: Node[] }>
    >();
    /** Plugins already reported as failing, so one broken plugin warns once. */
    #reportedPlugins: Set<string> = new Set();

    constructor(
        registry: PluginRegistry,
        state: StateManager,
        inspectorElement: Element,
        ignoreSelectors: string[] = []
    ) {
        this.#registry = registry;
        this.#state = state;
        this.#inspectorElement = inspectorElement;
        this.#ignoreSelectors = ignoreSelectors.filter((selector) => {
            try {
                document.createDocumentFragment().querySelector(selector);
                return true;
            } catch (e) {
                console.warn(`[ux-inspector] Ignoring invalid "ignore_selectors" entry "${selector}":`, e);
                return false;
            }
        });
    }

    scan(): void {
        const selector = this.#registry.combinedSelector;
        if (!selector) return;

        const seen = new Set(this.#components(document, selector));
        const query = createQueryCache();
        for (const element of seen) this.#reconcile(element, query);

        for (const existing of this.#state.elements) {
            if (!seen.has(existing) || !existing.isConnected) {
                this.#remove(existing);
            }
        }
    }

    /** Use the same exclusions, subscriptions and error handling for explicit inspection. */
    inspect(element: Element): ComponentDataMap | undefined {
        this.#reconcile(element, createQueryCache());
        return this.#state.get(element);
    }

    observe(): void {
        if (this.#observer) return;
        this.#observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;
                if (target && this.#isToolingElement(target)) continue;
                const attributeName = mutation.attributeName;
                let changes = this.#mutations.get(mutation.target);
                if (!changes) this.#mutations.set(mutation.target, (changes = new Map()));
                const key = mutation.type === 'attributes' ? `attribute:${attributeName}` : mutation.type;
                let change = changes.get(key);
                if (!change) {
                    change = {
                        type: mutation.type,
                        target: mutation.target,
                        attributeName,
                        addedNodes: [],
                        removedNodes: [],
                    };
                    changes.set(key, change);
                }
                change.addedNodes.push(...mutation.addedNodes);
                change.removedNodes.push(...mutation.removedNodes);
            }
            if (this.#mutations.size) this.#scheduleFlush();
        });

        this.#observer.observe(document.body || document.documentElement, {
            childList: true,
            subtree: true,
            attributes: true,
            characterData: true,
        });
    }

    disconnect(): void {
        for (const element of this.#state.elements) {
            this.#registry.notifyElementRemoved(element, this.#state.get(element)?.keys() ?? []);
        }
        this.#observer?.disconnect();
        this.#observer = null;
        this.#pending.clear();
        this.#mutations.clear();
        this.#flushQueued = false;
    }

    /** Refresh a component root after a framework runtime update. */
    refresh(element: Element): void {
        this.#queueOwners(element);
    }

    destroy(): void {
        this.disconnect();
    }

    #reconcile(element: Element, query: QueryElements): void {
        if (this.#isExcluded(element)) {
            if (this.#state.get(element)) this.#remove(element);
            return;
        }
        const previous = this.#state.get(element);
        const parsed: ComponentDataMap = new Map();
        for (const plugin of this.#registry.getForElement(element)) {
            try {
                plugin.observe?.(element);
                parsed.set(plugin.name, plugin.parse(element, query));
            } catch (e) {
                this.#registry.notifyElementRemoved(element, [plugin.name]);
                // A plugin failing on one element usually fails on every scan.
                if (!this.#reportedPlugins.has(plugin.name)) {
                    this.#reportedPlugins.add(plugin.name);
                    console.warn(`[ux-inspector] Plugin "${plugin.name}" parse() failed:`, e);
                }
            }
        }
        for (const name of previous?.keys() ?? []) {
            if (!parsed.has(name)) this.#registry.notifyElementRemoved(element, [name]);
        }
        this.#state.replace(element, parsed);
    }

    #queue(element: Node | null): void {
        if (!(element instanceof Element)) return;
        this.#pending.add(element);
        this.#scheduleFlush();
    }

    #scheduleFlush(): void {
        if (this.#flushQueued) return;
        this.#flushQueued = true;
        queueMicrotask(() => this.#flush());
    }

    #flush(): void {
        const mutations = [...this.#mutations.values()].flatMap((changes) => [...changes.values()]);
        this.#mutations.clear();
        let pageChanged = false;
        for (const mutation of mutations) {
            if (
                mutation.type === 'attributes' &&
                !BASE_ATTRIBUTES.has(mutation.attributeName ?? '') &&
                !this.#registry.isWatchedAttribute(mutation.attributeName ?? '')
            )
                continue;
            pageChanged = true;
            if (mutation.type === 'childList') {
                for (const node of new Set(mutation.removedNodes)) {
                    if (node instanceof Element && !node.isConnected) this.#removeSubtree(node);
                }
                for (const node of new Set(mutation.addedNodes)) {
                    if (node instanceof Element) {
                        for (const element of this.#components(node)) this.#queue(element);
                    }
                }
            } else if (mutation.target instanceof Element) {
                const selector = this.#registry.combinedSelector;
                if (this.#state.get(mutation.target) || (selector && mutation.target.matches(selector)))
                    this.#queue(mutation.target);
            }
            this.#queueOwners(mutation.target);
        }
        const query = createQueryCache();
        if (mutations.length) {
            for (const owner of this.#registry.collectExternalChanges(this.#state, query, this.#pending))
                this.#pending.add(owner);
        }
        const pending = [...this.#pending];
        this.#pending.clear();
        this.#flushQueued = false;
        for (const target of pending) this.#reconcile(target, query);
        if (pageChanged) this.#state.notifyPageUpdated();
    }

    #queueOwners(element: Node | null): void {
        let current: Element | null = element instanceof Element ? element : (element?.parentElement ?? null);
        while (current) {
            if (this.#state.get(current)) this.#queue(current);
            current = current.parentElement;
        }
    }

    *#components(root: Document | Element, selector = this.#registry.combinedSelector): Iterable<Element> {
        if (!selector || (root instanceof Element && this.#isExcluded(root))) return;
        if (root instanceof Element && root.matches(selector)) yield root;
        yield* root.querySelectorAll(selector);
    }

    #removeSubtree(root: Element): void {
        for (const element of this.#state.elements) {
            if (element === root || root.contains(element)) this.#remove(element);
        }
    }

    #remove(element: Element): void {
        this.#pending.delete(element);
        const names = [...(this.#state.get(element)?.keys() ?? [])];
        this.#registry.notifyElementRemoved(element, names);
        this.#state.remove(element);
    }

    #isToolingElement(element: Element): boolean {
        return this.#inspectorElement.contains(element) || !!element.closest(TOOLING_SELECTOR);
    }

    #isExcluded(element: Element): boolean {
        return (
            !element.isConnected ||
            this.#isToolingElement(element) ||
            this.#ignoreSelectors.some((selector) => element.closest(selector))
        );
    }
}
