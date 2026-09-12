import { createEmptyState, el, frameworkName, reconcileChildren } from './ui-helpers';
import { createIcon } from './icons';
import { makeField, makeKeyValueList } from './fields';
import { projectActivity } from '../core/activity-projector';
import type { EventMonitor } from '../core/event-monitor';
import type { ActivityEntry, ActivityEvent, ActivityRow } from '../types';

const MAX_ENTRIES = 100;

type TimelineItem = HTMLLIElement & { _inspectorEntry?: ActivityRow; _navigationTarget?: Element | null };

export interface TimelineCallbacks {
    onHighlight?: (element: Element | null, framework?: string) => void;
    onSelect?: (element: Element) => void;
}

export class Timeline {
    #monitor: EventMonitor;
    #element: HTMLElement;
    #list: HTMLOListElement;
    #filterEmpty: HTMLElement;
    #onHighlight: TimelineCallbacks['onHighlight'] | null = null;
    #onSelect: TimelineCallbacks['onSelect'] | null = null;
    #rafId: number | null = null;
    #paused = false;
    #query = '';
    #elementFilter: Element | null = null;
    #frameworks: Set<string> | null = null;
    #entries: ActivityEntry[] = [];
    #rows = new Map<number | ActivityEvent, TimelineItem>();
    #nextDetailId = 0;
    #selectedEntry: ActivityRow | null = null;
    #contextual = false;

    constructor(monitor: EventMonitor, callbacks: TimelineCallbacks = {}) {
        this.#monitor = monitor;
        this.#onHighlight = callbacks.onHighlight || null;
        this.#onSelect = callbacks.onSelect || null;
        this.#entries = monitor.entries || [];
        this.#list = el('ol', {
            class: 'events',
            'aria-label': 'Captured UX events',
            'aria-live': 'polite',
            on: { keydown: (event: Event) => this.#onKeydown(event as KeyboardEvent) },
        });
        this.#filterEmpty = createEmptyState('No matches.');
        this.#filterEmpty.hidden = true;
        this.#element = el('div', { class: 'timeline' }, this.#list, this.#filterEmpty);
    }

    get element(): HTMLElement {
        return this.#element;
    }
    get paused(): boolean {
        return this.#paused;
    }
    get projectedEntries(): readonly ActivityRow[] {
        return this.#monitor.project();
    }

    configure({
        query = this.#query,
        element = this.#elementFilter,
        frameworks = this.#frameworks,
        contextual = this.#contextual,
    }: {
        query?: string;
        element?: Element | null;
        frameworks?: Iterable<string> | null;
        contextual?: boolean;
    }): void {
        const contextChanged = contextual !== this.#contextual;
        const render = contextChanged || element !== this.#elementFilter;
        this.#query = query.trim().toLowerCase();
        this.#elementFilter = element;
        this.#frameworks = frameworks ? new Set(frameworks) : null;
        this.#contextual = contextual;
        if (contextChanged) {
            this.#selectedEntry = null;
            this.#rows.clear();
        }
        if (render) {
            if (this.#paused) this.#renderEntries();
            else this.#renderSnapshot();
        } else this.#applyFilter();
    }

    async copySelected(): Promise<boolean> {
        const clipboard = navigator.clipboard;
        const entry = this.#selectedEntry;
        if (!entry || !clipboard?.writeText) return false;
        const diagnostic = this.#diagnostic(entry);
        try {
            await clipboard.writeText(JSON.stringify(diagnostic));
            return true;
        } catch {
            return false;
        }
    }

    addEntry(_entry: ActivityEntry): void {
        if (this.#paused || this.#rafId !== null) return;
        this.#rafId = requestAnimationFrame(() => this.#flushEntries());
    }

    #flushEntries(): void {
        this.#rafId = null;
        if (!this.#paused) this.#renderSnapshot();
    }

    flush(): void {
        if (!this.#rafId) return;
        cancelAnimationFrame(this.#rafId);
        this.#flushEntries();
    }

    destroy(): void {
        if (this.#rafId !== null) cancelAnimationFrame(this.#rafId);
        this.#rafId = null;
        this.#paused = true;
        this.#rows.clear();
        this.#entries = [];
        this.#selectedEntry = null;
        this.#onHighlight = null;
        this.#onSelect = null;
        this.#element.remove();
    }

    pause(): boolean {
        if (this.#paused) return false;
        this.#paused = true;
        if (this.#rafId) cancelAnimationFrame(this.#rafId);
        this.#rafId = null;
        this.#onHighlight?.(null);
        return true;
    }

    resume(): boolean {
        if (!this.#paused) return false;
        this.#paused = false;
        this.#renderSnapshot();
        return true;
    }

    clear(): void {
        if (this.#rafId) cancelAnimationFrame(this.#rafId);
        this.#rafId = null;
        this.#selectedEntry = null;
        this.#monitor.clear();
        this.#entries = [];
        this.#renderEntries();
        this.#element.dispatchEvent(new CustomEvent('activity-selected', { detail: { entry: null } }));
    }

    refresh(): void {
        if (this.#paused) return;
        this.#renderSnapshot();
    }

    expandFirstVisible(): void {
        const item = [...this.#list.querySelectorAll('.event:not([hidden])')].find((candidate) =>
            this.#hasDetail((candidate as TimelineItem)._inspectorEntry)
        );
        const disclosure = item?.querySelector('.disclosure') as HTMLElement | null;
        if (disclosure?.getAttribute('aria-expanded') !== 'true') disclosure?.click();
    }

    #renderSnapshot(): void {
        if (this.#rafId !== null) cancelAnimationFrame(this.#rafId);
        this.#rafId = null;
        this.#entries = this.#monitor.entries || [];
        this.#renderEntries();
    }

    #renderEntries(): void {
        const selectedAnchor = this.#selectedEntry && this.#anchor(this.#selectedEntry);
        const root = this.#element.getRootNode() as Document | ShadowRoot;
        const focused = root.activeElement as HTMLElement | null;
        const focusedItem = focused?.closest('.event') as TimelineItem | null;
        const focusedAnchor = focusedItem?._inspectorEntry && this.#anchor(focusedItem._inspectorEntry);
        // Controls come and go between renders (a row gains its disclosure once it has a
        // detail), so restore focus by the identity of the control, not by its position.
        const focusedControl = focused?.dataset.control;
        this.#onHighlight?.(null);

        const entries = (
            this.#paused
                ? projectActivity(this.#entries, !this.#contextual)
                : this.#monitor.project(null, !this.#contextual)
        )
            .slice(-MAX_ENTRIES)
            .map((entry) => {
                const previous = this.#rows.get(this.#anchor(entry))?._inspectorEntry;
                return previous && this.#sameEntries(previous, entry) ? previous : entry;
            });
        this.#selectedEntry = selectedAnchor
            ? entries.find((entry) => this.#anchor(entry) === selectedAnchor) || null
            : null;
        this.#rows = new Map(
            entries.map((entry) => {
                const anchor = this.#anchor(entry);
                const previous = this.#rows.get(anchor);
                const owner = this.#owner(entry);
                const reusable =
                    previous?._inspectorEntry === entry && previous._navigationTarget === this.#navigationTarget(owner);
                return [anchor, reusable ? previous : this.#renderEntry(entry, owner)];
            })
        );
        reconcileChildren(
            this.#list,
            this.#rows.size ? [...this.#rows.values()].reverse() : [createEmptyState('No events captured yet.')]
        );
        this.#applyFilter();

        if (focusedAnchor && root.activeElement !== focused) {
            const row = this.#rows.get(focusedAnchor);
            const control = focusedControl
                ? row?.querySelector<HTMLElement>(`[data-control="${focusedControl}"]:not([hidden])`)
                : null;
            const fallback = this.#list.querySelector<HTMLElement>('.event:not([hidden]) button');
            (control && !row?.hidden ? control : fallback)?.focus({ preventScroll: true });
        }
    }

    #owner(entry: ActivityRow): Element | null {
        return entry.owner || entry.target?.closest?.('[data-controller], turbo-frame') || null;
    }

    #navigationTarget(owner: Element | null): Element | null {
        return owner?.isConnected ? owner : null;
    }

    #anchor(entry: ActivityEvent & { rawEntries?: readonly ActivityEvent[] }): number | ActivityEvent {
        return entry.id ?? entry.rawEntries?.[0] ?? entry;
    }

    #sameEntries(previous: ActivityRow, current: ActivityRow): boolean {
        if (previous === current) return true;
        return (
            !!previous.rawEntries &&
            !!current.rawEntries &&
            previous.rawEntries.length === current.rawEntries.length &&
            previous.rawEntries.every((entry, index) => entry === current.rawEntries?.[index])
        );
    }

    #renderEntry(entry: ActivityRow, owner: Element | null): TimelineItem {
        const type = entry.type || 'unknown';
        const eventName = this.#eventIdentity(entry);
        const targetIdentity = this.#targetIdentity(entry.target ?? null);
        const visibleTarget = this.#visibleContext(entry, targetIdentity, owner);
        const navigationTarget = this.#navigationTarget(owner);
        const hasDetail = this.#hasDetail(entry);
        const selected = hasDetail && this.#selectedEntry === entry;
        const detailId = `uxli-activity-detail-${++this.#nextDetailId}`;
        const disclosure = el(
            hasDetail ? 'button' : 'div',
            {
                class: 'disclosure',
                ...(hasDetail
                    ? {
                          type: 'button',
                          'aria-expanded': String(selected),
                          'aria-controls': detailId,
                          dataset: { control: 'disclosure' },
                      }
                    : {}),
                'aria-label': [
                    `${frameworkName(type)} activity`,
                    eventName,
                    targetIdentity,
                    `${(entry.time / 1000).toFixed(3)} seconds`,
                ]
                    .filter(Boolean)
                    .join(', '),
            },
            el('span', { class: 'event-dot', 'aria-hidden': 'true' }),
            el('strong', { class: 'name', text: eventName }),
            visibleTarget ? el('span', { class: 'event-target', text: visibleTarget }) : null
        );

        const navigationIdentity = this.#targetIdentity(navigationTarget);
        const goLabel = navigationIdentity
            ? `Go to component ${navigationIdentity}`
            : `Go to component for ${entry.event}`;
        const goBtn = el(
            'button',
            {
                class: 'icon-button event-go',
                type: 'button',
                title: goLabel,
                'aria-label': goLabel,
                hidden: !navigationTarget,
                dataset: { control: 'go' },
            },
            createIcon('target')
        );

        const row = el(
            'div',
            {
                class: `event-row ${type}${selected ? ' selected' : ''}${hasDetail ? '' : ' compact'}`,
                dataset: { framework: type },
            },
            disclosure,
            (entry.occurrences ?? 0) > 1
                ? el('span', {
                      class: 'event-count',
                      text: String(entry.occurrences),
                      title: `${entry.occurrences} identical operations`,
                  })
                : null,
            goBtn
        );
        const detail = hasDetail ? el('div', { class: 'event-detail', id: detailId, hidden: !selected }) : null;
        if (selected && detail) detail.appendChild(this.renderDetail(entry));
        const item = el(
            'li',
            {
                class: 'event',
                dataset: { search: this.#searchText(entry, eventName, targetIdentity, owner) },
            },
            row,
            detail
        ) as TimelineItem;
        item._inspectorEntry = entry;
        item._navigationTarget = navigationTarget;

        if (hasDetail && detail) disclosure.addEventListener('click', () => this.#select(entry, row, detail));

        if (navigationTarget) {
            row.addEventListener('mouseenter', () => {
                goBtn.hidden = !navigationTarget.isConnected;
                if (navigationTarget.isConnected) this.#onHighlight?.(navigationTarget, type);
            });
            row.addEventListener('mouseleave', () => this.#onHighlight?.(null));
        }

        goBtn.addEventListener('click', () => {
            goBtn.hidden = !navigationTarget?.isConnected;
            if (goBtn.hidden) return;
            this.#onHighlight?.(null);
            if (navigationTarget) this.#onSelect?.(navigationTarget);
        });

        return item;
    }

    #hasDetail(entry: ActivityRow | undefined | null): boolean {
        if (!entry) return false;
        if (entry.activityKind === 'turbo-fetch' || entry.activityKind === 'live-rerender') return true;
        if (entry.detail == null) return false;
        if (Array.isArray(entry.detail)) return entry.detail.length > 0;
        if (typeof entry.detail === 'object') return Object.keys(entry.detail as object).length > 0;
        return true;
    }

    renderDetail(entry: ActivityRow | null): HTMLElement {
        if (!entry) return createEmptyState('Select an activity to inspect.');
        const copy = el(
            'button',
            {
                class: 'icon-button event-copy',
                type: 'button',
                title: 'Copy activity',
                'aria-label': 'Copy activity',
                dataset: { control: 'copy' },
                on: {
                    click: async () => {
                        const copied = await this.copySelected();
                        const label = copied ? 'Activity copied' : 'Copy failed';
                        copy.classList.toggle('copied', copied);
                        copy.setAttribute('aria-label', label);
                        copy.title = label;
                        setTimeout(() => {
                            copy.classList.remove('copied');
                            copy.setAttribute('aria-label', 'Copy activity');
                            copy.title = 'Copy activity';
                        }, 900);
                    },
                },
            },
            createIcon('copy')
        );
        const fields: HTMLElement[] = [];
        if (entry.activityKind === 'turbo-fetch') {
            const fetch = entry.fetch;
            fields.push(
                makeField('Intent', fetch.intent === 'prefetch' ? 'Prefetch' : 'Fetch'),
                makeField('Request', [fetch.method, fetch.url].filter(Boolean).join(' ')),
                makeField('Status', fetch.pending ? 'Pending' : (fetch.status ?? 'Completed')),
                makeField('Duration', fetch.pending ? 'Pending' : this.#formatDuration(fetch.duration))
            );
            if (fetch.priority) fields.push(makeField('Priority', fetch.priority));
            if ((entry.occurrences ?? 0) > 1) fields.push(makeField('Operations', entry.occurrences));
            fields.push(makeField('Raw hooks', entry.rawEntries!.length));
        } else if (entry.activityKind === 'live-rerender') {
            const live = entry.live;
            const changes = Object.fromEntries(live.changes.map((change) => [change.model, change.value]));
            fields.push(makeField('Trigger', live.trigger));
            if (live.actions.length) fields.push(makeField('Calls', live.actions));
            if (Object.keys(changes).length) fields.push(makeField('Changes', changes));
            fields.push(makeField('Hooks', live.hooks));
            fields.push(makeField('Status', live.status));
            if (live.duration != null) fields.push(makeField('Duration', this.#formatDuration(live.duration)));
        } else if (entry.detail != null) {
            const values: Array<[string, unknown]> =
                typeof entry.detail === 'object' && !Array.isArray(entry.detail)
                    ? Object.entries(entry.detail as object)
                    : [['value', entry.detail]];
            for (const [name, value] of values) fields.push(makeField(name, value));
        }
        return el(
            'section',
            { class: 'activity-detail', dataset: { framework: entry.type || 'default' } },
            el('div', { class: 'event-actions' }, copy),
            makeKeyValueList(fields),
            entry.activityKind === 'turbo-fetch' ? this.#renderRawEvents(entry.rawEntries!) : null
        );
    }

    #renderRawEvents(entries: readonly ActivityEvent[]): HTMLElement {
        return el(
            'details',
            { class: 'raw-events', open: false },
            el(
                'summary',
                {},
                el('span', { text: 'Raw events' }),
                el('span', { class: 'raw-count', text: String(entries.length) })
            ),
            el(
                'ol',
                { class: 'raw-list' },
                ...entries.map((entry) =>
                    el(
                        'li',
                        {},
                        el('strong', { text: entry.event }),
                        el('span', { text: `${(entry.time / 1000).toFixed(3)}s` })
                    )
                )
            )
        );
    }

    #select(entry: ActivityRow, row: HTMLElement, detail: HTMLElement): void {
        const open = this.#selectedEntry !== entry || !row.classList.contains('selected');
        this.#selectedEntry = open ? entry : null;
        for (const item of this.#list.querySelectorAll('.event')) {
            const candidate = item.querySelector('.event-row');
            const candidateDetail = item.querySelector('.event-detail') as HTMLElement | null;
            const selected = open && candidate === row;
            candidate?.classList.toggle('selected', selected);
            candidate?.querySelector('.disclosure')?.setAttribute('aria-expanded', String(selected));
            if (candidateDetail) {
                candidateDetail.hidden = !selected;
                if (!selected) candidateDetail.replaceChildren();
            }
        }
        if (open && !detail.firstChild) detail.appendChild(this.renderDetail(entry));
        this.#element.dispatchEvent(new CustomEvent('activity-selected', { detail: { entry: this.#selectedEntry } }));
    }

    #applyFilter(): void {
        const items = Array.from(this.#list.querySelectorAll('.event')) as TimelineItem[];
        let visible = 0;
        for (const item of items) {
            const matchesFramework = !this.#frameworks || this.#frameworks.has(item._inspectorEntry?.type ?? '');
            const matchesElement =
                !this.#elementFilter || this.#matchesElement(item._inspectorEntry, this.#elementFilter);
            const matches =
                matchesFramework &&
                matchesElement &&
                (!this.#query || (item.dataset.search ?? '').includes(this.#query));
            item.hidden = !matches;
            if (matches) visible++;
        }
        this.#filterEmpty.hidden = items.length === 0 || visible > 0;
        const selected = this.#list.querySelector('.event-row.selected')?.closest('.event') as HTMLElement | null;
        if (this.#selectedEntry && (!selected || selected.hidden)) {
            selected?.querySelector('.event-row')?.classList.remove('selected');
            selected?.querySelector('.disclosure')?.setAttribute('aria-expanded', 'false');
            const detail = selected?.querySelector('.event-detail') as HTMLElement | null;
            if (detail) {
                detail.hidden = true;
                detail.replaceChildren();
            }
            this.#selectedEntry = null;
            this.#element.dispatchEvent(new CustomEvent('activity-selected', { detail: { entry: null } }));
        }
    }

    #matchesElement(entry: ActivityRow | undefined, element: Element): boolean {
        return (
            entry?.owner === element ||
            entry?.target === element ||
            (entry?.relatedElements?.includes(element) ?? false) ||
            (entry?.rawEntries?.some(
                (raw) => raw.owner === element || raw.target === element || raw.relatedElements?.includes(element)
            ) ??
                false)
        );
    }

    #onKeydown(event: KeyboardEvent): void {
        if (!['ArrowUp', 'ArrowDown', 'Home', 'End'].includes(event.key)) return;
        const buttons = Array.from(this.#list.querySelectorAll('button.disclosure')) as HTMLElement[];
        if (!buttons.length) return;
        const current = buttons.indexOf((event.target as Element)?.closest?.('.disclosure') as HTMLElement);
        const next =
            event.key === 'Home'
                ? 0
                : event.key === 'End'
                  ? buttons.length - 1
                  : Math.min(buttons.length - 1, Math.max(0, current + (event.key === 'ArrowDown' ? 1 : -1)));
        event.preventDefault();
        buttons[next].focus();
    }

    #targetIdentity(target: Element | null | undefined): string {
        if (!target?.tagName) return '';
        const tag = target.tagName.toLowerCase();
        return tag + (target.id ? `#${target.id}` : '');
    }

    #eventIdentity(entry: ActivityRow): string {
        if (entry.activityKind && entry.activityKind !== 'repeated') return entry.label || entry.event;
        if (this.#contextual) return entry.event;
        const label = entry.label || entry.event;
        if (entry.type !== 'livecomponent') return label;
        const component = (entry.target as HTMLElement | null | undefined)?.dataset?.liveNameValue;
        return component && label.startsWith(`${component}: `)
            ? label.slice(component.length + 2)
            : entry.event?.replace(/^live:/, '') || label;
    }

    #visibleContext(entry: ActivityRow, targetIdentity: string, owner: Element | null): string {
        if (entry.type === 'livecomponent')
            return (entry.target as HTMLElement | null | undefined)?.dataset?.liveNameValue || '';
        if (!owner || ['html', 'body'].includes(targetIdentity)) return '';
        return targetIdentity;
    }

    #detailedTargetIdentity(target: Element): string {
        const identity = this.#targetIdentity(target);
        if (!identity) return '';
        const cls =
            target.className && typeof target.className === 'string'
                ? '.' + target.className.trim().split(/\s+/).filter(Boolean).slice(0, 3).join('.')
                : '';
        return identity + cls;
    }

    #searchText(entry: ActivityRow, eventName: string, targetIdentity: string, owner: Element | null): string {
        const rawEvents = (entry.rawEntries || []).map((raw) => raw.event).join(' ');
        const fetch = entry.fetch
            ? `${entry.fetch.intent} ${entry.fetch.method} ${entry.fetch.url} ${entry.fetch.status ?? ''} ${entry.fetch.priority}`
            : '';
        const dataset = (owner as HTMLElement | null)?.dataset;
        return `${eventName} ${entry.type || 'unknown'} ${targetIdentity} ${owner?.id || ''} ${dataset?.controller || ''} ${dataset?.liveNameValue || ''} ${rawEvents} ${fetch}`.toLowerCase();
    }

    #formatDuration(milliseconds: number | null | undefined): string {
        if (typeof milliseconds !== 'number' || !Number.isFinite(milliseconds)) return 'Unknown';
        if (milliseconds < 1) return '<1 ms';
        if (milliseconds < 1000) return `${Math.round(milliseconds)} ms`;
        return `${(milliseconds / 1000).toFixed(2)} s`;
    }

    #diagnostic(entry: ActivityRow): Record<string, unknown> {
        const base = {
            event: entry.event,
            framework: entry.type,
            time: Number((entry.time / 1000).toFixed(3)),
            label: entry.label,
            target: entry.target instanceof Element ? this.#detailedTargetIdentity(entry.target) : undefined,
        };
        if (entry.activityKind !== 'turbo-fetch') return { ...base, detail: entry.detail };
        return {
            ...base,
            operation: {
                ...entry.fetch,
                occurrences: entry.occurrences,
                rawHooks: entry.rawEntries!.length,
            },
            rawEvents: entry.rawEntries!.map((raw) => ({
                event: raw.event,
                time: Number((raw.time / 1000).toFixed(3)),
                target: raw.target instanceof Element ? this.#detailedTargetIdentity(raw.target) : undefined,
                detail: raw.detail,
            })),
        };
    }
}
