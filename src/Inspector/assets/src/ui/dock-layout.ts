const OPEN_ATTRIBUTE = 'data-ux-inspector-open';
const WIDTH_PROPERTY = '--ux-inspector-width';

/** The right-hand dock reserves page space on desktop and fills narrow viewports. */
export class DockLayout {
    #host: HTMLElement;
    #style: HTMLStyleElement | null = null;
    #width: number | null = null;

    constructor(host: HTMLElement) {
        this.#host = host;
    }

    resize(width: number): number {
        this.#width = Math.round(Math.min(Math.max(208, width), Math.max(208, window.innerWidth * 0.8)));
        this.#host.style.setProperty('--panel-width', `${this.#width}px`);
        this.sync();
        return this.#width;
    }

    sync(): void {
        if (!this.#host.isConnected || !this.#host.hasAttribute('open')) {
            this.detach();
            return;
        }
        if (!this.#style) {
            this.#style = document.createElement('style');
            this.#style.dataset.uxInspectorLayout = '';
            this.#style.textContent = `@media (min-width:42.5rem){html[${OPEN_ATTRIBUTE}]{box-sizing:border-box!important;padding-right:var(${WIDTH_PROPERTY},21.25rem)!important}}`;
        }
        if (!this.#style.isConnected) document.head.append(this.#style);
        if (this.#width !== null) {
            this.#width = Math.min(this.#width, Math.max(208, window.innerWidth * 0.8));
            this.#host.style.setProperty('--panel-width', `${this.#width}px`);
            document.documentElement.style.setProperty(WIDTH_PROPERTY, `${this.#width}px`);
        }
        document.documentElement.setAttribute(OPEN_ATTRIBUTE, '');
    }

    detach(): void {
        document.documentElement.removeAttribute(OPEN_ATTRIBUTE);
        document.documentElement.style.removeProperty(WIDTH_PROPERTY);
        this.#style?.remove();
    }
}
