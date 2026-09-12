import type { Highlighter } from '../visual/highlighter';
import { TOOLING_SELECTOR } from './dom-query';
import type { PluginRegistry } from './plugin-registry';
import type { InspectorPlugin } from '../types';

export type TargetSelectCallback = (element: Element, plugins: InspectorPlugin[], keepInspecting: boolean) => void;

export class TargetSelector {
    #highlighter: Highlighter;
    #registry: PluginRegistry;
    #onSelect: TargetSelectCallback | null = null;
    #onStateChange: ((active: boolean) => void) | null = null;

    #activation: AbortController | null = null;
    #enableTimeout: ReturnType<typeof setTimeout> | null = null;

    constructor(
        highlighter: Highlighter,
        registry: PluginRegistry,
        onStateChange: ((active: boolean) => void) | null = null
    ) {
        this.#highlighter = highlighter;
        this.#registry = registry;
        this.#onStateChange = onStateChange;
    }

    get active(): boolean {
        return this.#activation !== null;
    }

    enable(onSelect: TargetSelectCallback): void {
        if (this.active) return;
        const activation = (this.#activation = new AbortController());
        this.#onSelect = onSelect;
        this.#onStateChange?.(true);

        const options = { capture: true, signal: activation.signal };
        const target = (event: MouseEvent) => this.#onTarget(event);
        document.addEventListener('mouseover', target, options);
        document.addEventListener('mouseout', () => this.#highlighter.clearHover(), options);
        document.addEventListener('keydown', (event) => this.#onKeyDown(event), options);
        this.#enableTimeout = setTimeout(() => {
            this.#enableTimeout = null;
            if (this.active) {
                document.addEventListener('click', target, options);
            }
        }, 0);
    }

    disable(clearSelection = true): void {
        if (!this.#activation) return;
        this.#activation.abort();
        this.#activation = null;
        this.#onSelect = null;
        this.#onStateChange?.(false);

        if (this.#enableTimeout) {
            clearTimeout(this.#enableTimeout);
            this.#enableTimeout = null;
        }
        this.#highlighter.clearHover();
        if (clearSelection) this.#highlighter.deselect();
    }

    toggle(onSelect: TargetSelectCallback): boolean {
        if (this.active) {
            this.disable();
        } else {
            this.enable(onSelect);
        }
        return this.active;
    }

    destroy(): void {
        this.disable();
    }

    #onTarget(e: MouseEvent): void {
        if (!(e.target instanceof Element) || e.target.closest(TOOLING_SELECTOR)) return;
        const selector = this.#registry.combinedSelector;
        const target = selector ? e.target.closest(selector) : null;
        const plugins = target ? this.#registry.getForElement(target) : [];
        if (e.type === 'mouseover') {
            if (target) this.#highlighter.hover(target, plugins[0]?.name || 'default');
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        if (target) {
            const keepInspecting = e.shiftKey;
            this.#onSelect?.(target, plugins, keepInspecting);
            if (!keepInspecting) this.disable(false);
        }
    }

    #onKeyDown(e: Event): void {
        if ((e as KeyboardEvent).key !== 'Escape') return;
        // Inspect mode owns the interaction, as it already does for clicks. Without
        // this the same Escape would also pop a level off the panel's drill stack.
        e.preventDefault();
        e.stopPropagation();
        this.disable();
    }
}
