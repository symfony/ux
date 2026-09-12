import { ComponentList } from './component-list';
import type { VisualCallbacks } from './visual-target';
export type { VisualCallbacks, VisualTarget } from './visual-target';
import { createIconButton } from './icons';
import { DetailNavigation } from './detail-navigation';
import { ActivityDrawer } from './activity-drawer';
import { ResizeHandle } from './resize-handle';
import { el } from './ui-helpers';
import type { PluginRegistry } from '../core/plugin-registry';
import { activityElements, type ActivityListener, type EventMonitor } from '../core/event-monitor';
import type { RelationshipEngine } from '../core/relationship-engine';
import type { StateManager } from '../core/state-manager';
import type { Timeline } from './timeline';

const FRAMEWORKS: Array<[string, string]> = [
    ['stimulus', 'Stimulus'],
    ['livecomponent', 'Live'],
    ['turbo', 'Turbo'],
];

export interface ActionCallbacks {
    target?: () => void;
    overlay?: () => void;
    pause?: () => void;
    clearLog?: () => void;
}

export interface PanelHost extends HTMLElement {
    open(): void;
    close(): void;
    setPanelWidth(width: number): number;
}

export class Panel {
    #state: StateManager;
    #eventMonitor: EventMonitor | null;
    #timeline: Timeline;
    #host: PanelHost;
    #list: ComponentList;
    #navigation: DetailNavigation;
    #activity: ActivityDrawer;
    #element: HTMLElement;
    #components: HTMLElement;
    #componentPanel: HTMLElement;
    #activityPanel!: HTMLElement;
    #search!: HTMLInputElement;
    #filters: Set<string> = new Set(FRAMEWORKS.map(([name]) => name));
    #query = '';
    #monitorLabel!: HTMLElement;
    #actions: ActionCallbacks = {};
    #actionButtons: Record<string, HTMLButtonElement> = {};
    #logTools!: HTMLElement;
    #activitySearch!: HTMLInputElement;
    #activityFilters: Set<string> = new Set(FRAMEWORKS.map(([name]) => name));
    #activityFilterButtons: Record<string, HTMLButtonElement> = {};
    #lifetime = new AbortController();
    #eventListener: ActivityListener | null = null;
    #packages: Record<string, boolean>;
    #filterButtons: Record<string, HTMLButtonElement> = {};
    #refreshFrame: number | null = null;
    #resizeHandle: ResizeHandle;
    #view = 'components';
    #activityCount = 0;
    #pendingActivity = new Set<Element>();
    #listDirty = true;
    #onPreview: VisualCallbacks['onPreview'] | null = null;
    #onClearPreview: VisualCallbacks['onClearPreview'] | null = null;
    #onSelect: VisualCallbacks['onSelect'] | null = null;
    #onClearSelection: VisualCallbacks['onClearSelection'] | null = null;
    #selectedComponent: Element | null = null;

    constructor(
        state: StateManager,
        registry: PluginRegistry,
        eventMonitor: EventMonitor | null,
        timeline: Timeline,
        host: PanelHost,
        relationshipEngine: RelationshipEngine | null,
        packages: Record<string, boolean> = {}
    ) {
        this.#state = state;
        this.#eventMonitor = eventMonitor;
        this.#timeline = timeline;
        this.#host = host;
        this.#packages = packages;
        this.#list = new ComponentList(state, registry, eventMonitor, {
            onPreview: (target) => this.#onPreview?.(target),
            onClearPreview: () => this.#onClearPreview?.(),
            onSelect: (target) => this.#onSelect?.(target),
        });

        this.#element = el('aside', { class: 'inspector pane', 'aria-label': 'Symfony UX Inspector' });
        const tools = this.#tools();
        this.#resizeHandle = new ResizeHandle({
            target: this.#element,
            axis: 'width',
            label: 'Resize inspector',
            className: 'panel-resize',
            read: () => this.#element.getBoundingClientRect().width,
            write: (width) => this.#host.setPanelWidth(width),
        });
        this.#element.append(this.#resizeHandle.element, this.#header(), tools, this.#filterBar());

        this.#components = this.#list.element;
        this.#activityPanel = el(
            'section',
            {
                id: 'panel-activity',
                hidden: true,
                role: 'region',
                tabindex: '0',
                'aria-label': 'Activity',
            },
            this.#timeline.element
        );
        this.#activity = new ActivityDrawer(timeline, this.#activityPanel, () => {
            timeline.configure({
                contextual: false,
                element: null,
                frameworks: this.#activityFilters,
                query: this.#activitySearch.value,
            });
        });
        this.#navigation = new DetailNavigation(
            state,
            registry,
            eventMonitor,
            relationshipEngine,
            this.#components,
            this.#activity,
            {
                showComponents: () => this.#switchTab('components', false),
                open: () => this.open(),
                change: () => {
                    this.#updateDrillUi();
                    this.refresh();
                },
                select: (target) => {
                    this.#selectedComponent = target.element;
                    this.#list.select(target.element);
                    this.#onSelect?.(target);
                },
                clearSelection: () => {
                    this.#selectedComponent = null;
                    this.#list.select(null);
                    this.#onClearSelection?.();
                },
                preview: (target) => this.#onPreview?.(target),
                clearPreview: () => this.#onClearPreview?.(),
            }
        );
        this.#componentPanel = this.#navigation.element;
        this.#componentPanel.id = 'panel-components';
        this.#componentPanel.setAttribute('role', 'region');
        this.#componentPanel.setAttribute('tabindex', '0');
        this.#componentPanel.setAttribute('aria-label', 'Components');
        this.#element.append(el('main', {}, this.#componentPanel, this.#activityPanel), this.#footer());

        this.#components.addEventListener('drill-into', (event) =>
            this.drillInto((event as CustomEvent).detail.element)
        );
        const refresh = () => {
            if (this.#refreshFrame !== null) return;
            this.#refreshFrame = requestAnimationFrame(() => {
                this.#refreshFrame = null;
                this.#flush();
            });
        };
        for (const name of [
            'component-added',
            'component-updated',
            'component-removed',
            'components-cleared',
            'page-updated',
        ]) {
            const listener = () => {
                this.#listDirty = true;
                refresh();
            };
            state.addEventListener(name, listener, { signal: this.#lifetime.signal });
        }
        this.#eventListener = (entry, removed = []) => {
            for (const changed of [entry, ...removed]) {
                for (const element of activityElements(changed)) this.#pendingActivity.add(element);
            }
            refresh();
        };
        eventMonitor?.addListener(this.#eventListener);
        this.refresh();
    }

    get element(): HTMLElement {
        return this.#element;
    }
    get isComponentListVisible(): boolean {
        return this.#view === 'components';
    }
    get focusedComponent(): Element | null {
        return this.#navigation.focusedComponent;
    }

    setActionCallbacks(callbacks: ActionCallbacks): void {
        this.#actions = { ...callbacks };
    }
    setVisualCallbacks({ onPreview, onClearPreview, onSelect, onClearSelection }: VisualCallbacks): void {
        this.#onPreview = onPreview ?? null;
        this.#onClearPreview = onClearPreview ?? null;
        this.#onSelect = onSelect ?? null;
        this.#onClearSelection = onClearSelection ?? null;
    }
    setTargetModeActive(active: boolean): void {
        const button = this.#actionButtons.target;
        const label = active ? 'Stop inspecting' : 'Inspect page components';
        button.classList.toggle('active', Boolean(active));
        button.setAttribute('aria-pressed', String(Boolean(active)));
        button.setAttribute('aria-label', label);
        button.title = active ? 'Click to inspect. Shift-click to continue.' : label;
        this.#monitorLabel.textContent = active ? 'Inspecting' : 'Watching';
        this.#monitorLabel.parentElement?.classList.toggle('inspecting', Boolean(active));
    }
    setOverlayActive(active: boolean): void {
        const button = this.#actionButtons.overlay;
        const label = active ? 'Hide all components' : 'Show all components';
        button.classList.toggle('active', Boolean(active));
        button.setAttribute('aria-pressed', String(Boolean(active)));
        button.setAttribute('aria-label', label);
        this.#monitorLabel.textContent = active ? 'Overlay enabled' : 'Watching';
    }
    setLogPaused(paused: boolean): void {
        this.#monitorLabel.textContent = paused ? 'Activity paused' : 'Watching';
        this.#monitorLabel.parentElement?.classList.toggle('paused', Boolean(paused));
    }
    clearActivities(): void {
        this.#pendingActivity.clear();
        this.#list.clearActivities();
        this.#updateCounts();
    }
    open(): void {
        this.#host?.open();
    }
    close(): void {
        this.#host?.close();
    }
    navigate(view: string): void {
        this.#switchTab(view === 'log' ? 'log' : 'components');
        this.open();
        if (view === 'search') {
            while (this.drillBack()) {}
            this.#search.focus();
        }
    }

    refresh(): void {
        this.#listDirty = true;
        this.#flush();
    }

    #flush(): void {
        // A public refresh can consume an already scheduled update.
        if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
        this.#refreshFrame = null;
        this.#updateCounts();
        if (this.#listDirty && this.isComponentListVisible) {
            this.#list.refresh(this.#filters, this.#query, this.#selectedComponent);
            this.#navigation.setRootTitle(`Components (${this.#state.size})`);
            this.#listDirty = false;
        }
        for (const element of this.#pendingActivity) this.#list.updateActivity(element);
        this.#pendingActivity.clear();
    }

    drillInto(element: Element, dataMap = this.#state.get(element)): void {
        this.#navigation.drillInto(element, dataMap);
    }
    drillBack(): boolean {
        return this.#navigation.drillBack();
    }
    clearFocus(): boolean {
        return this.#navigation.clearFocus();
    }
    suspendForNavigation(): void {
        this.#navigation.suspendForNavigation();
    }
    resumeAfterNavigation(): void {
        this.#navigation.resumeAfterNavigation();
    }
    destroy(): void {
        this.#resizeHandle.destroy();
        if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
        this.#navigation.destroy();
        this.#list.destroy();
        this.#pendingActivity.clear();
        this.#lifetime.abort();
        if (this.#eventListener) this.#eventMonitor?.removeListener(this.#eventListener);
    }

    #header() {
        this.#actionButtons.target = this.#toggleButton('target', 'target', 'Inspect page components', () =>
            this.#actions.target?.()
        );
        this.#actionButtons.overlay = this.#toggleButton('overlay', 'overlay', 'Show all components', () =>
            this.#actions.overlay?.()
        );
        this.#actionButtons.activity = this.#toggleButton('activity', 'activity', 'Show activity', () =>
            this.#toggleActivity()
        );
        this.#actionButtons.activity.appendChild(el('b', { text: '0' }));
        const close = createIconButton('panel-right', 'Hide inspector', () => this.close());
        close.className = 'icon-button';
        return el(
            'header',
            {},
            el('div', { class: 'header-actions' }, this.#actionButtons.target, this.#actionButtons.overlay),
            el('strong', { id: 'ux-inspector-title', text: 'UX Inspector' }),
            el('div', { class: 'header-actions header-end' }, this.#actionButtons.activity, close)
        );
    }

    #switchTab(name: string, restoreActivity = true): void {
        this.#view = name === 'log' ? 'log' : 'components';
        const activityVisible = this.#view === 'log';
        const restored = activityVisible && this.#activity.close();
        this.#componentPanel.hidden = activityVisible;
        this.#activityPanel.hidden = !activityVisible;
        this.#updateDrillUi();
        this.#logTools.hidden = !activityVisible;
        if (activityVisible && !restored) this.#timeline.refresh();
        else if (!activityVisible) {
            if (restoreActivity) this.#navigation.restoreActivity();
            if (this.#listDirty) this.#flush();
        }
    }

    #toggleActivity(): void {
        if (this.#view === 'log') {
            this.#switchTab('components');
            return;
        }
        this.#openGlobalActivity();
    }

    #syncActivityControl(): void {
        const active = this.#view === 'log';
        const count = this.#activityCount;
        const label = active ? 'Show components' : 'Show activity';
        const accessibleLabel = count ? `${label}, ${count} activit${count === 1 ? 'y' : 'ies'}` : label;
        const button = this.#actionButtons.activity;
        button.classList.toggle('active', active);
        button.setAttribute('aria-pressed', String(active));
        button.setAttribute('aria-label', accessibleLabel);
        button.title = label;
        const badge = button.querySelector('b') as HTMLElement | null;
        if (badge) {
            badge.textContent = String(count);
            badge.hidden = !count;
        }
    }

    #tools(): HTMLElement {
        const buttons = this.#frameworkButtons(this.#activityFilters, this.#activityFilterButtons, () =>
            this.#timeline.configure({ frameworks: this.#activityFilters })
        );
        this.#activitySearch = el('input', {
            type: 'search',
            placeholder: 'Filter activity…',
            'aria-label': 'Filter activity',
            on: { input: () => this.#timeline.configure({ query: this.#activitySearch.value }) },
        });
        this.#logTools = el(
            'div',
            { class: 'activity-tools toolbar--activity', hidden: true },
            el(
                'div',
                {
                    class: 'filter-list activity-filter-list',
                    role: 'group',
                    'aria-label': 'Filter activity by framework',
                },
                ...buttons
            ),
            this.#activitySearch
        );

        return this.#logTools;
    }

    #toggleButton(name: string, icon: string, label: string, callback: () => void): HTMLButtonElement {
        const button = createIconButton(icon, label, callback);
        button.className = 'icon-button';
        button.dataset.action = name;
        button.setAttribute('aria-pressed', 'false');
        return button;
    }

    #filterBar() {
        this.#search = el('input', {
            type: 'search',
            placeholder: 'Find a component…',
            'aria-label': 'Find a component',
            on: {
                input: () => {
                    this.#query = this.#search.value.trim().toLowerCase();
                    this.refresh();
                },
            },
        });
        const buttons = this.#frameworkButtons(this.#filters, this.#filterButtons, () => this.refresh());
        return el('div', { class: 'filters' }, el('div', { class: 'filter-list' }, ...buttons), this.#search);
    }

    #frameworkButtons(
        filters: Set<string>,
        buttons: Record<string, HTMLButtonElement>,
        update: () => void
    ): HTMLButtonElement[] {
        return FRAMEWORKS.map(([name, label]) => {
            const button = el(
                'button',
                {
                    class: 'filter active',
                    type: 'button',
                    'aria-pressed': 'true',
                    dataset: { framework: name },
                    on: {
                        click: (event: Event) => {
                            const target = event.currentTarget as HTMLElement;
                            const active = target.classList.toggle('active');
                            target.setAttribute('aria-pressed', String(active));
                            if (active) filters.add(name);
                            else filters.delete(name);
                            update();
                        },
                    },
                },
                el('span', { text: label }),
                el('b', { text: '0' })
            );
            buttons[name] = button;
            return button;
        });
    }

    #footer(): HTMLElement {
        this.#monitorLabel = el('span', { text: 'Watching' });
        return el(
            'footer',
            {},
            el(
                'span',
                { class: 'monitor-status', role: 'status' },
                el('span', { class: 'live-dot', 'aria-hidden': 'true' }),
                this.#monitorLabel
            )
        );
    }

    #updateCounts(): void {
        const byPlugin = this.#state.countByPlugin();
        const activityByPlugin: Record<string, number> = {
            ...Object.fromEntries(FRAMEWORKS.map(([name]) => [name, 0])),
            ...Object.fromEntries(
                Object.entries(Object.groupBy(this.#eventMonitor?.project() ?? [], (entry) => entry.type)).map(
                    ([type, entries]) => [type, entries!.length]
                )
            ),
        };
        for (const [name, label] of FRAMEWORKS) {
            const button = this.#filterButtons[name];
            const count = byPlugin[name] || 0;
            const installed = this.#packages[name];
            const status = count
                ? 'detected'
                : installed === false
                  ? 'not-installed'
                  : installed === true
                    ? 'idle'
                    : 'unknown';
            const badge = button.querySelector('b');
            if (badge) badge.textContent = installed === false ? '-' : String(count);
            button.classList.toggle('unavailable', installed === false);
            button.dataset.status = status;
            button.title = count
                ? `${count} detected on this page`
                : installed === false
                  ? `${label} package not installed`
                  : installed === true
                    ? `${label} installed; none detected on this page`
                    : 'None detected on this page';
            button.setAttribute('aria-label', `${label}: ${button.title}`);
            const activityButton = this.#activityFilterButtons[name];
            const activityBadge = activityButton.querySelector('b');
            if (activityBadge) activityBadge.textContent = String(activityByPlugin[name]);
            activityButton.title = `${activityByPlugin[name]} ${label} activit${activityByPlugin[name] === 1 ? 'y' : 'ies'}`;
            activityButton.setAttribute('aria-label', activityButton.title);
        }
        this.#activityCount = Object.values(activityByPlugin).reduce((sum, count) => sum + count, 0);
        this.#syncActivityControl();
    }

    #updateDrillUi(): void {
        const componentsVisible = this.#view === 'components';
        const filters = this.#element.querySelector('.filters') as HTMLElement | null;
        if (filters) filters.hidden = !componentsVisible || this.#navigation.depth > 1;
        this.#syncActivityControl();
    }

    #openGlobalActivity(): void {
        this.#activitySearch.value = '';
        this.#timeline.configure({ query: '' });
        this.#switchTab('log');
    }
}
