import { ResizeHandle } from './resize-handle';
import { el } from './ui-helpers';
import type { Timeline } from './timeline';

/** Moves the single Activity view between the global panel and a resizable detail drawer. */
export class ActivityDrawer {
    #timeline: Timeline;
    #home: HTMLElement;
    #restore: () => void;
    #id: string | null = null;
    #drawer: HTMLElement | null = null;
    #resizeHandle: ResizeHandle | null = null;
    #sizes = new WeakMap<HTMLElement, number>();
    constructor(timeline: Timeline, home: HTMLElement, restore: () => void) {
        this.#timeline = timeline;
        this.#home = home;
        this.#restore = restore;
    }
    get id(): string | null {
        return this.#id;
    }
    open(id: string, content: HTMLElement, element: Element | null, query: string): void {
        this.close(false);
        const drawerId = `component-activity-${id}`;
        const drawer: HTMLElement = el(
            'section',
            {
                class: 'pane drawer',
                id: drawerId,
                'aria-label': `Activity for ${query || 'component'}`,
            },
            this.#timeline.element
        );
        content.appendChild(drawer);
        const savedHeight = this.#sizes.get(content);
        if (savedHeight !== undefined) {
            drawer.style.height = `${savedHeight}px`;
            drawer.toggleAttribute('data-sized', true);
        }
        this.#resizeHandle = new ResizeHandle({
            target: drawer,
            axis: 'height',
            label: 'Resize Activity',
            className: 'drawer-resize',
            read: () => drawer.getBoundingClientRect().height,
            write: (height) => {
                const minimum = Number.parseFloat(getComputedStyle(drawer).minHeight) || 40;
                const available = drawer.parentElement?.clientHeight || height;
                const detailMinimum =
                    6 * (Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16);
                const value = Math.min(Math.max(minimum, available - detailMinimum), Math.max(minimum, height));
                drawer.toggleAttribute('data-sized', true);
                drawer.style.height = `${value}px`;
                this.#sizes.set(content, value);
                return value;
            },
        });
        drawer.prepend(this.#resizeHandle.element);
        this.#drawer = drawer;
        this.#id = id;
        this.#timeline.configure({ contextual: true, frameworks: null, element, query: element ? '' : query });
        this.#timeline.expandFirstVisible();
    }

    close(restore = true): boolean {
        if (!this.#id) return false;
        this.#home.appendChild(this.#timeline.element);
        this.#resizeHandle?.destroy();
        this.#drawer?.remove();
        this.#drawer = null;
        this.#id = null;
        if (restore) this.#restore();
        return true;
    }
}
