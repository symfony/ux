import { ComponentDetector } from '../core/component-detector';
import { EventMonitor } from '../core/event-monitor';
import { RelationshipEngine } from '../core/relationship-engine';
import { StateManager } from '../core/state-manager';
import { TargetSelector } from '../core/target-selector';
import { LiveComponentPlugin } from '../live-component/plugin';
import { PluginRegistry } from './plugin-registry';
import { StimulusPlugin } from '../stimulus/plugin';
import { TurboPlugin } from '../turbo/plugin';
import { Panel } from '../ui/panel';
import { Timeline } from '../ui/timeline';
import { bindOpenShortcut } from '../ui/open-shortcut';
import { createPullTab } from '../ui/pull-tab';
import { Highlighter } from '../visual/highlighter';
import type { ActivityEntry } from '../types';
import type { StimulusApplicationLike } from '../stimulus/plugin';

export interface InspectorConfig {
    pull_tab?: boolean;
    ignore_selectors?: string[];
    packages?: Record<string, boolean>;
}

export interface InspectorHost extends HTMLElement {
    readonly isOpen: boolean;
    open(): void;
    close(): void;
    setPanelWidth(width: number): number;
}

export class InspectorRuntime {
    #registry: PluginRegistry;
    #state = new StateManager();
    #detector: ComponentDetector;
    #targetSelector: TargetSelector;
    #visual: Highlighter;
    #panel: Panel;
    #pullTab: HTMLElement | null = null;
    #eventMonitor: EventMonitor;
    #timeline: Timeline;
    #relationshipEngine: RelationshipEngine;
    #lifetime = new AbortController();
    #refreshFrame: number | null = null;
    #readyFrame: number | null = null;
    #dynamicEventsQueued = false;
    #phase: 'observing' | 'suspended' | 'destroyed' = 'observing';
    #host: InspectorHost;
    #config: InspectorConfig;
    #application: () => StimulusApplicationLike | null;

    constructor(
        host: InspectorHost,
        shadow: ShadowRoot,
        config: InspectorConfig,
        application: () => StimulusApplicationLike | null
    ) {
        this.#host = host;
        this.#config = config;
        this.#application = application;
        this.#registry = new PluginRegistry([
            new LiveComponentPlugin(),
            new TurboPlugin(),
            new StimulusPlugin(application()),
        ]);

        this.#eventMonitor = new EventMonitor(500, (draft) => {
            if (draft.target && this.#phase === 'observing') this.#registry.notifyEvent(draft, draft.target);
            draft.owner = this.#findNearestComponent(draft.target);
        });
        const monitor = this.#eventMonitor;
        this.#registry.setEventRecorder((entry) => monitor.record(entry));
        this.#detector = new ComponentDetector(this.#registry, this.#state, host, this.#config.ignore_selectors || []);
        this.#visual = new Highlighter(shadow, this.#state, this.#registry);
        this.#targetSelector = new TargetSelector(this.#visual, this.#registry, (active) =>
            this.#panel.setTargetModeActive(active)
        );
        this.#relationshipEngine = new RelationshipEngine(this.#registry, this.#state);
        this.#timeline = new Timeline(this.#eventMonitor, {
            onHighlight: (element, framework) =>
                element ? this.#visual.hover(element, framework) : this.#visual.clearHover(),
            onSelect: (element) => {
                const component = this.#findNearestComponent(element);
                if (component) this.#panel.drillInto(component);
            },
        });
        // Update detection and page feedback after the normalized event is published.
        this.#eventMonitor.addListener((entry) => this.#onEvent(entry));
        this.#panel = new Panel(
            this.#state,
            this.#registry,
            this.#eventMonitor,
            this.#timeline,
            host,
            this.#relationshipEngine,
            this.#config.packages || {}
        );
        this.#panel.setActionCallbacks({
            target: () => this.toggleTargetMode(),
            overlay: () => this.toggleOverlay(),
            pause: () => this.toggleLogPaused(),
            clearLog: () => this.clearLog(),
        });
        this.#panel.setVisualCallbacks({
            onPreview: ({ element, framework, label }) => this.#visual.hover(element, framework, label),
            onClearPreview: () => this.#visual.clearHover(),
            onSelect: ({ element, framework, label }) => this.#visual.select(element, framework, label),
            onClearSelection: () => this.#visual.deselect(),
        });
        shadow.append(this.#panel.element);
        this.#registerPluginStaticEvents();
        const timeline = this.#timeline;
        this.#eventMonitor.start((entry) => timeline.addEntry(entry));

        const { signal } = this.#lifetime;
        if (config.pull_tab !== false) {
            this.#pullTab = createPullTab(
                host,
                () => {
                    host.open();
                    this.#panel.element
                        .querySelector<HTMLButtonElement>('[aria-label="Hide inspector"]')
                        ?.focus({ preventScroll: true });
                },
                signal
            );
            shadow.append(this.#pullTab);
        }
        if (document.readyState === 'loading')
            document.addEventListener('DOMContentLoaded', () => this.scan(), { once: true, signal });
        bindOpenShortcut(() => host.open(), signal);
        document.addEventListener(
            'keydown',
            (event) => {
                if (event.key === 'Escape' && this.#panel.drillBack()) event.preventDefault();
            },
            { signal }
        );
        window.addEventListener('scroll', () => this.refreshVisual(), { capture: true, passive: true, signal });
        const updateDynamicEvents = () => {
            if (this.#dynamicEventsQueued) return;
            this.#dynamicEventsQueued = true;
            queueMicrotask(() => {
                this.#dynamicEventsQueued = false;
                if (host.isConnected && this.#phase !== 'destroyed') this.#registerPluginDynamicEvents();
            });
        };
        for (const type of ['component-added', 'component-updated', 'component-removed', 'components-cleared']) {
            this.#state.addEventListener(type, updateDynamicEvents, { signal });
        }

        this.scan();
        this.#detector.observe();
        this.#readyFrame = requestAnimationFrame(() => {
            this.#readyFrame = null;
            this.scan();
            host.setAttribute('ready', '');
        });
    }

    setStimulusApplication(application: StimulusApplicationLike | null): void {
        (this.#registry.get('stimulus') as StimulusPlugin | undefined)?.setApplication(application);
        this.scan();
    }

    getStatus(): Record<string, unknown> {
        const components = this.#state.countByPlugin();
        return {
            installed: { ...this.#config.packages },
            used: {
                stimulus: Boolean(document.querySelector('[data-controller]')),
                livecomponent: Boolean(document.querySelector('[data-controller~="live"]')),
                turbo: Boolean(
                    (globalThis as { Turbo?: unknown }).Turbo ||
                    customElements.get('turbo-frame') ||
                    document.querySelector('turbo-frame, turbo-stream-source')
                ),
            },
            components,
        };
    }

    close(): void {
        this.#targetSelector.disable();
        this.#visual.clearHover();
        this.#visual.deselect();
        if (this.#panel.element.contains(this.#host.shadowRoot?.activeElement ?? null))
            this.#pullTab?.querySelector('button')?.focus({ preventScroll: true });
    }

    refreshVisual(): void {
        if (this.#refreshFrame !== null || this.#phase !== 'observing') return;
        this.#refreshFrame = requestAnimationFrame(() => {
            this.#refreshFrame = null;
            this.#visual.refresh();
        });
    }

    scan(): void {
        if (this.#phase !== 'observing') return;
        const application = this.#application();
        if (application) (this.#registry.get('stimulus') as StimulusPlugin | undefined)?.setApplication(application);
        this.#detector.scan();
        this.#registerPluginDynamicEvents();
    }

    clear(): void {
        this.clearLog();
        this.#visual.clearAll();
        this.#panel.clearFocus();
        this.#panel.setOverlayActive(false);
        this.#panel.refresh();
    }

    clearLog(): void {
        this.#timeline.clear();
        this.#panel.clearActivities();
    }

    toggleLogPaused(): boolean {
        if (this.#timeline.paused) this.#timeline.resume();
        else this.#timeline.pause();
        return Boolean(this.#timeline.paused);
    }

    inspectElement(element: Element | null | undefined): void {
        if (!element || this.#phase !== 'observing') return;
        const data = this.#detector.inspect(element);
        if (!data) return;
        this.#visual.select(element, data.keys().next().value);
        this.#panel.drillInto(element, data);
    }

    toggleTargetMode(): boolean {
        return this.#targetSelector.toggle((element) => this.inspectElement(element));
    }

    toggleOverlay(): boolean {
        const visible = Boolean(this.#visual.toggleAll());
        this.#panel.setOverlayActive(visible);
        return visible;
    }

    #onEvent(entry: ActivityEntry): void {
        if (this.#phase !== 'observing') return;
        if (entry.type === 'livecomponent' && entry.target) this.#detector.refresh(entry.target);
        if (this.#host.isOpen && this.#panel.isComponentListVisible && entry.relatedElements?.length) {
            for (const element of entry.relatedElements) {
                this.#visual.pulse(element, entry.type, entry.label || entry.event);
            }
        }
        const component = this.#findNearestComponent(entry.target);
        if (!component) return;
        if (!this.#host.isOpen || !this.#panel.isComponentListVisible) return;
        const framework = this.#state.get(component)?.keys().next().value ?? entry.type;
        this.#visual.pulse(component, framework, entry.label || entry.event);
    }

    #findNearestComponent(element: Element | null | undefined): Element | null {
        let current: Element | null = element ?? null;
        while (current) {
            if (this.#state.get(current)) return current;
            current = current.parentElement;
        }
        return null;
    }

    #registerPluginStaticEvents(): void {
        const { staticEvents } = this.#registry.collectMonitoredEvents();
        for (const [pluginName, events] of staticEvents) this.#eventMonitor.monitorEvents(events, pluginName);
    }

    #registerPluginDynamicEvents(): void {
        const byPlugin = new Map<string, Element[]>();
        for (const element of this.#state.elements) {
            for (const pluginName of this.#state.get(element)?.keys() ?? []) {
                if (!byPlugin.has(pluginName)) byPlugin.set(pluginName, []);
                (byPlugin.get(pluginName) as Element[]).push(element);
            }
        }
        const { dynamicEvents } = this.#registry.collectMonitoredEvents(byPlugin);
        for (const [pluginName, events] of dynamicEvents) this.#eventMonitor.setDynamicEvents(events, pluginName);
    }

    suspend(): void {
        if (this.#phase !== 'observing') return;
        this.#phase = 'suspended';
        this.#pullTab?.removeAttribute('data-near');
        if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
        if (this.#readyFrame !== null) cancelAnimationFrame(this.#readyFrame);
        this.#refreshFrame = this.#readyFrame = null;
        this.#panel.suspendForNavigation();
        this.#targetSelector.disable();
        this.#visual.clearAll();
        this.#detector.disconnect();
    }

    resume(): void {
        if (this.#phase === 'destroyed') return;
        if (this.#phase === 'observing') {
            this.scan();
            return;
        }
        this.#phase = 'observing';
        this.#state.clear();
        this.#relationshipEngine.invalidate();
        this.#detector.observe();
        this.scan();
        this.#panel.resumeAfterNavigation();
        this.#host.setAttribute('ready', '');
    }
    destroy(): void {
        if (this.#phase === 'destroyed') return;
        this.#phase = 'destroyed';
        this.#lifetime.abort();
        if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
        if (this.#readyFrame !== null) cancelAnimationFrame(this.#readyFrame);
        this.#panel.destroy();
        this.#timeline.destroy();
        this.#targetSelector.destroy();
        this.#visual.destroy();
        this.#eventMonitor.destroy();
        this.#relationshipEngine.destroy();
        this.#detector.destroy();
        this.#registry.destroy();
    }
}
