import { el } from './ui-helpers';

interface ResizeOptions {
    target: HTMLElement;
    axis: 'width' | 'height';
    label: string;
    className: string;
    read: () => number;
    write: (size: number) => number;
}

/** Pointer and keyboard resizing, scoped to the lifetime of its panel or drawer. */
export class ResizeHandle {
    readonly element: HTMLDivElement;
    #lifetime = new AbortController();
    #drag: AbortController | null = null;
    #options: ResizeOptions;

    constructor(options: ResizeOptions) {
        this.#options = options;
        this.element = el('div', {
            class: options.className,
            role: 'separator',
            tabIndex: 0,
            'aria-label': options.label,
            'aria-orientation': options.axis === 'width' ? 'vertical' : 'horizontal',
        });
        const { signal } = this.#lifetime;
        this.element.addEventListener('pointerdown', (event) => this.#start(event), { signal });
        this.element.addEventListener(
            'keydown',
            (event) => {
                const keys = options.axis === 'width' ? ['ArrowLeft', 'ArrowRight'] : ['ArrowUp', 'ArrowDown'];
                const direction = keys.indexOf(event.key);
                if (direction < 0) return;
                event.preventDefault();
                this.#resize(options.read() + (direction === 0 ? 16 : -16));
            },
            { signal }
        );
    }

    destroy(): void {
        this.#stop();
        this.#lifetime.abort();
    }

    #resize(size: number): void {
        const value = this.#options.write(size);
        this.element.setAttribute('aria-valuenow', String(Math.round(value)));
    }

    #start(event: PointerEvent): void {
        if (event.button !== 0) return;
        event.preventDefault();
        this.#stop();
        this.#drag = new AbortController();
        const { signal } = this.#drag;
        const coordinate = this.#options.axis === 'width' ? 'clientX' : 'clientY';
        const start = event[coordinate];
        const size = this.#options.read();
        this.#options.target.toggleAttribute('data-resizing', true);
        this.element.setPointerCapture?.(event.pointerId);
        this.element.addEventListener(
            'pointermove',
            (move) => {
                if (move.pointerId === event.pointerId) this.#resize(size + start - move[coordinate]);
            },
            { signal }
        );
        for (const type of ['pointerup', 'pointercancel', 'lostpointercapture']) {
            this.element.addEventListener(type, () => this.#stop(), { signal });
        }
    }

    #stop(): void {
        this.#drag?.abort();
        this.#drag = null;
        this.#options.target.removeAttribute('data-resizing');
    }
}
