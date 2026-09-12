import { InspectorRuntime, type InspectorConfig } from './core/inspector-runtime';
import { DockLayout } from './ui/dock-layout';
import type { StimulusApplicationLike } from './stimulus/plugin';

interface StylesheetSource {
    text?: string;
    url?: string;
}

let stylesheet: StylesheetSource | undefined;
let stimulusApplication: StimulusApplicationLike | null = null;

function availableStimulusApplication(): StimulusApplicationLike | null {
    const application = stimulusApplication ?? (globalThis as { Stimulus?: StimulusApplicationLike }).Stimulus;
    return typeof application?.getControllerForElementAndIdentifier === 'function' ? application : null;
}

export class UXInspector extends HTMLElement {
    #runtime: InspectorRuntime | null = null;
    #config: InspectorConfig = {};
    #layout = new DockLayout(this);
    #lifetime = new AbortController();
    #teardown: ReturnType<typeof setTimeout> | null = null;

    connectedCallback(): void {
        if (this.#teardown !== null) clearTimeout(this.#teardown);
        this.#teardown = null;
        if (this.#runtime) {
            this.#runtime.resume();
            this.#layout.sync();
            return;
        }
        this.#readConfig();
        const shadow = this.shadowRoot ?? this.attachShadow({ mode: 'open' });
        shadow.replaceChildren();
        if (stylesheet?.text) {
            const sheet = new CSSStyleSheet();
            sheet.replaceSync(stylesheet.text);
            shadow.adoptedStyleSheets = [sheet];
        } else {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = this.getAttribute('data-css-url') || stylesheet?.url || '';
            shadow.append(link);
        }
        this.#lifetime = new AbortController();
        const { signal } = this.#lifetime;
        this.#runtime = new InspectorRuntime(this, shadow, this.#config, availableStimulusApplication);
        document.addEventListener(
            'turbo:before-cache',
            () => {
                this.#runtime?.suspend();
                this.#layout.detach();
            },
            { signal }
        );
        document.addEventListener(
            'turbo:render',
            () => {
                if (!this.isConnected) return;
                this.#runtime?.resume();
                this.#layout.sync();
            },
            { signal }
        );
        window.addEventListener(
            'resize',
            () => {
                this.#layout.sync();
                this.#runtime?.refreshVisual();
            },
            { signal, passive: true }
        );
        this.#layout.sync();
    }

    disconnectedCallback(): void {
        this.#layout.detach();
        this.#runtime?.suspend();
        this.#teardown = setTimeout(() => {
            this.#runtime?.destroy();
            this.#runtime = null;
            this.#lifetime.abort();
            this.#teardown = null;
        }, 1000);
    }

    get isOpen(): boolean {
        return this.hasAttribute('open');
    }
    getStatus(): Record<string, unknown> {
        return this.#runtime?.getStatus() ?? {};
    }
    setStimulusApplication(application: StimulusApplicationLike | null): void {
        this.#runtime?.setStimulusApplication(application);
    }
    open(): void {
        this.scan();
        this.setAttribute('open', '');
        this.#layout.sync();
    }
    close(): void {
        this.removeAttribute('open');
        this.#layout.sync();
        this.#runtime?.close();
    }
    toggle(): void {
        if (this.isOpen) this.close();
        else this.open();
    }
    setPanelWidth(width: number): number {
        const value = this.#layout.resize(width);
        this.#runtime?.refreshVisual();
        return value;
    }
    scan(): void {
        this.#runtime?.scan();
    }
    clear(): void {
        this.#runtime?.clear();
    }
    clearLog(): void {
        this.#runtime?.clearLog();
    }
    toggleLogPaused(): boolean {
        return this.#runtime?.toggleLogPaused() ?? false;
    }
    inspectElement(element: Element | null | undefined): void {
        this.#runtime?.inspectElement(element);
    }
    toggleTargetMode(): boolean {
        return this.#runtime?.toggleTargetMode() ?? false;
    }
    toggleOverlay(): boolean {
        return this.#runtime?.toggleOverlay() ?? false;
    }
    #readConfig(): void {
        const raw = this.getAttribute('data-config');
        if (!raw) return;
        try {
            this.#config = JSON.parse(raw);
        } catch (error) {
            console.warn('[ux-inspector] Invalid config JSON:', (error as Error).message);
        }
    }
}

export function registerUXInspector(styles: StylesheetSource): void {
    stylesheet = styles;
    if (!customElements.get('ux-inspector')) customElements.define('ux-inspector', UXInspector);
}

export function connectStimulus(application: StimulusApplicationLike | null): boolean {
    const valid = Boolean(application?.getControllerForElementAndIdentifier);
    stimulusApplication = valid ? application : null;
    (document.querySelector('ux-inspector') as UXInspector | null)?.setStimulusApplication(stimulusApplication);
    return valid;
}
