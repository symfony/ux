import type { PluginRegistry } from '../core/plugin-registry';
import type { StateManager } from '../core/state-manager';

type OverlayBox = HTMLDivElement & { _target?: Element };

interface EventBox {
    box: OverlayBox;
    caption: HTMLSpanElement;
    label: string;
    count: number;
    timer: ReturnType<typeof setTimeout> | null;
    raf: number | null;
}

export class Highlighter {
    static #EVENT_LIFETIME = 1200;
    static #BOX_GUTTER = 2;

    #container: HTMLDivElement;
    #hover: OverlayBox | null = null;
    #selected: OverlayBox | null = null;
    #all: Map<Element, OverlayBox> = new Map();
    #events: Map<Element, EventBox> = new Map();
    #allVisible = false;
    #state: StateManager | null;
    #registry: PluginRegistry | null;
    #lifetime = new AbortController();
    #resizeObserver: ResizeObserver;
    #observed = new Map<Element, number>();

    constructor(
        root: ParentNode = document.documentElement,
        state: StateManager | null = null,
        registry: PluginRegistry | null = null
    ) {
        this.#state = state;
        this.#registry = registry;
        this.#container = document.createElement('div');
        this.#container.className = 'overlay';
        this.#container.setAttribute('data-ux-inspector-overlay', '');
        root.appendChild(this.#container);
        // Scoped to the resized targets: a full pass would reposition every box on each
        // observation, including the initial one that observe() delivers for each new box.
        this.#resizeObserver = new ResizeObserver((entries) => {
            for (const entry of entries) this.#refreshTarget(entry.target);
        });
        const update = (event: Event) => {
            if (this.#allVisible) this.#renderAll((event as CustomEvent).detail?.element);
        };
        for (const event of ['component-added', 'component-updated', 'component-removed', 'components-cleared'])
            state?.addEventListener(event, update, { signal: this.#lifetime.signal });
    }

    get visible(): boolean {
        return this.#allVisible;
    }

    hover(element: Element, framework = 'default', label = ''): void {
        this.#hover = this.#box(element, framework, 'hover', label, this.#hover);
    }

    clearHover(): void {
        this.#removeBox(this.#hover);
        this.#hover = null;
    }

    select(element: Element, framework = 'default', label = ''): void {
        this.#selected = this.#box(element, framework, 'selected', label, this.#selected);
    }

    deselect(): void {
        this.#removeBox(this.#selected);
        this.#selected = null;
    }

    showAll(): void {
        if (this.#allVisible) return;
        this.#allVisible = true;
        this.#renderAll();
    }

    hideAll(): void {
        this.#allVisible = false;
        for (const box of this.#all.values()) this.#removeBox(box);
        this.#all.clear();
    }

    toggleAll(): boolean {
        if (this.#allVisible) this.hideAll();
        else this.showAll();
        return this.#allVisible;
    }

    pulse(element: Element | null | undefined, framework = 'default', label = ''): void {
        if (!element?.isConnected) return;
        let entry = this.#events.get(element);

        if (entry) {
            if (entry.timer) clearTimeout(entry.timer);
            if (entry.raf !== null) cancelAnimationFrame(entry.raf);
            entry.count = entry.label === label ? entry.count + 1 : 1;
            entry.label = label;
            entry.box.dataset.framework = framework;
            this.#position(entry.box, element.getBoundingClientRect());
        } else {
            const box = this.#box(element, framework, 'event');
            const caption = document.createElement('span');
            box.appendChild(caption);
            entry = { box, caption, label, count: 1, timer: null, raf: null };
            this.#events.set(element, entry);
        }

        const current = entry;
        current.caption.textContent = current.count > 1 ? `${label} ×${current.count}` : label;
        current.box.classList.remove('pulse');
        current.raf = requestAnimationFrame(() => {
            current.raf = null;
            current.box.classList.add('pulse');
        });
        current.timer = setTimeout(() => this.#removeEvent(element, current), Highlighter.#EVENT_LIFETIME);
    }

    refresh(): void {
        // Only a visited key is ever deleted here, which Map iteration tolerates.
        for (const element of this.#observed.keys()) this.#refreshTarget(element);
    }

    clearAll(): void {
        this.clearHover();
        this.deselect();
        this.hideAll();
        for (const [element, entry] of this.#events) this.#removeEvent(element, entry);
    }

    destroy(): void {
        this.clearAll();
        this.#lifetime.abort();
        this.#resizeObserver.disconnect();
        this.#container.remove();
    }

    #renderAll(changed?: Element): void {
        const elements = new Set(this.#state?.elements);
        for (const [element, box] of this.#all) {
            if (elements.has(element) && element.isConnected) continue;
            this.#removeBox(box);
            this.#all.delete(element);
        }
        for (const element of elements) {
            if (!element.isConnected || (this.#all.has(element) && element !== changed)) continue;
            const framework = this.#registry?.getForElement(element)[0]?.name ?? 'default';
            const label = this.#registry?.get(framework)?.getDisplayName(element) ?? '';
            this.#all.set(element, this.#box(element, framework, 'all', label, this.#all.get(element)));
        }
    }

    #box(element: Element, framework: string, mode: string, label = '', previous?: OverlayBox | null): OverlayBox {
        const box = previous ?? (document.createElement('div') as OverlayBox);
        if (box.dataset.framework !== framework) box.dataset.framework = framework;
        if (box._target !== element) {
            this.#removeBox(box, false);
            box._target = element;
            const count = this.#observed.get(element) ?? 0;
            if (!count) this.#resizeObserver.observe(element, { box: 'border-box' });
            this.#observed.set(element, count + 1);
        }
        if (label) {
            const caption = box.firstElementChild ?? box.appendChild(document.createElement('span'));
            if (caption.textContent !== label) caption.textContent = label;
        } else box.firstElementChild?.remove();
        this.#position(box, element.getBoundingClientRect());
        if (!previous) {
            box.className = 'box';
            box.dataset.mode = mode;
            this.#container.appendChild(box);
        }
        return box;
    }

    #refreshTarget(element: Element): void {
        if (!this.#observed.has(element)) return;
        const rect = element.isConnected ? element.getBoundingClientRect() : null;
        const event = this.#events.get(element);
        if (!rect && event) this.#removeEvent(element, event);
        for (const box of [this.#all.get(element), event?.box, this.#hover, this.#selected]) {
            if (box?._target !== element) continue;
            if (rect) this.#position(box, rect);
            else this.#removeBox(box);
        }
        if (!rect) this.#all.delete(element);
        if (!this.#hover?._target) this.#hover = null;
        if (!this.#selected?._target) this.#selected = null;
    }

    #removeBox(box: OverlayBox | null, remove = true): void {
        if (!box) return;
        const target = box._target;
        if (target) {
            const count = (this.#observed.get(target) ?? 1) - 1;
            if (count) this.#observed.set(target, count);
            else {
                this.#observed.delete(target);
                this.#resizeObserver.unobserve(target);
            }
            delete box._target;
        }
        if (remove) box.remove();
    }

    #removeEvent(element: Element, entry: EventBox): void {
        if (this.#events.get(element) !== entry) return;
        if (entry.timer) clearTimeout(entry.timer);
        if (entry.raf !== null) cancelAnimationFrame(entry.raf);
        this.#removeBox(entry.box);
        this.#events.delete(element);
    }

    #position(box: HTMLElement, rect: DOMRect): void {
        const gutter = Highlighter.#BOX_GUTTER;
        for (const [property, value] of Object.entries({
            translate: `${rect.left - gutter}px ${rect.top - gutter}px`,
            width: `${rect.width + gutter * 2}px`,
            height: `${rect.height + gutter * 2}px`,
        })) {
            if (box.style.getPropertyValue(property) !== value) box.style.setProperty(property, value);
        }
    }
}
