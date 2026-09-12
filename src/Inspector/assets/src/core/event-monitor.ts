import type { ActivityEntry, ActivityInput, ActivityDraft, ActivityRow } from '../types';
import { projectActivity } from './activity-projector';
import { snapshotEvent, freezeSnapshot } from './snapshot';

export type ActivityListener = (entry: ActivityEntry, removed: readonly ActivityEntry[]) => void;

/** The exact scopes used both by history queries and by UI invalidation. */
export function activityElements(entry: ActivityEntry): Set<Element> {
    return new Set(
        [entry.owner, entry.target, ...(entry.relatedElements ?? [])].filter(
            (element): element is Element => element instanceof Element
        )
    );
}

export class EventMonitor {
    #entries: ActivityEntry[] = [];
    #sequence = 0;
    #byElement = new WeakMap<Element, ActivityEntry[]>();
    #revision = 0;
    #projections = new WeakMap<Element, Map<boolean, readonly ActivityRow[]>>();
    #globalProjections = new Map<boolean, readonly ActivityRow[]>();
    /** Owned snapshots are immutable; never traverse them again when projecting history. */
    #frozen = new WeakSet<object>();
    #normalize: (draft: ActivityDraft) => void;

    get revision(): number {
        return this.#revision;
    }

    project(element: Element | null = null, compact = true): readonly ActivityRow[] {
        let scoped = element ? this.#projections.get(element) : this.#globalProjections;
        if (!scoped) this.#projections.set(element!, (scoped = new Map()));
        let rows = scoped.get(compact);
        if (!rows) {
            rows = projectActivity(element ? (this.#byElement.get(element) ?? []) : this.#entries, compact);
            freezeSnapshot(rows, this.#frozen);
            scoped.set(compact, rows);
        }
        return rows;
    }

    #changed(): void {
        this.#revision++;
        this.#globalProjections.clear();
    }

    #index(entry: ActivityEntry, remove = false): void {
        for (const element of activityElements(entry)) {
            const entries = this.#byElement.get(element) ?? [];
            if (remove) {
                const index = entries.indexOf(entry);
                if (index !== -1) entries.splice(index, 1);
            } else {
                entries.push(entry);
            }
            if (entries.length) this.#byElement.set(element, entries);
            else this.#byElement.delete(element);
            this.#projections.delete(element);
        }
    }

    #maxEntries: number;
    #active = false;
    #listeners: ActivityListener[] = [];
    #categoryMap: Map<string, string> = new Map();
    /** Events registered as "static" (never removed by setDynamicEvents). */
    #staticEvents: Set<string> = new Set();

    constructor(maxEntries = 500, normalize: (draft: ActivityDraft) => void = () => {}) {
        this.#normalize = normalize;
        this.#maxEntries = maxEntries;
        this.#boundHandler = this.#handleEvent.bind(this) as EventListener;
    }

    #boundHandler: EventListener;

    get entries(): ActivityEntry[] {
        return [...this.#entries];
    }

    get active(): boolean {
        return this.#active;
    }

    record({
        type = 'unknown',
        event,
        target = null,
        owner = null,
        detail = null,
        label = null,
        relatedElements = [],
    }: ActivityInput): ActivityEntry {
        const draft: ActivityDraft = {
            id: ++this.#sequence,
            time: performance.now(),
            type,
            event,
            target: target instanceof Element ? target : null,
            owner: owner instanceof Element ? owner : null,
            detail,
            relatedElements: relatedElements
                .filter((element): element is Element => element instanceof Element)
                .slice(0, 50),
        };
        if (label) draft.label = label;
        this.#normalize(draft);
        const entry: ActivityEntry = Object.freeze({
            ...draft,
            detail: freezeSnapshot(snapshotEvent(draft.detail), this.#frozen),
            relatedElements: Object.freeze([...draft.relatedElements]),
        });
        this.#frozen.add(entry);

        this.#entries.push(entry);
        this.#index(entry);
        const removed = Object.freeze(this.#entries.splice(0, Math.max(0, this.#entries.length - this.#maxEntries)));
        for (const expired of removed) this.#index(expired, true);

        this.#changed();
        // Registration replaces the array, preserving this delivery during subscription changes.
        for (const listener of this.#listeners) {
            try {
                listener(entry, removed);
            } catch (error) {
                console.warn('[ux-inspector] Activity listener failed:', error);
            }
        }
        return entry;
    }

    start(onEntry: ActivityListener | null = null): void {
        if (onEntry) this.addListener(onEntry);
        if (this.#active) return;
        this.#active = true;

        for (const eventName of this.#categoryMap.keys()) {
            document.addEventListener(eventName, this.#boundHandler, true);
        }
    }

    stop(): void {
        if (!this.#active) return;
        this.#active = false;

        for (const eventName of this.#categoryMap.keys()) {
            document.removeEventListener(eventName, this.#boundHandler, true);
        }
    }

    addListener(fn: ActivityListener): void {
        this.#listeners = [...this.#listeners, fn];
    }

    removeListener(fn: ActivityListener): void {
        const idx = this.#listeners.indexOf(fn);
        if (idx !== -1) this.#listeners = this.#listeners.toSpliced(idx, 1);
    }

    /**
     * Register event names to monitor, with an optional category for classification.
     * Events registered here are considered "static" and won't be removed by setDynamicEvents.
     * Safe to call multiple times -- duplicate event names are ignored.
     */
    monitorEvents(eventNames: string[], category = 'unknown'): void {
        for (const name of eventNames) this.#staticEvents.add(name);
        this.#registerEvents(eventNames, category);
    }

    /**
     * Replace dynamic events for a given category. Removes previously registered
     * dynamic events for this category that are no longer in the new set.
     * Static events (registered via monitorEvents) are never removed.
     */
    setDynamicEvents(eventNames: string[], category: string): void {
        const newSet = new Set(eventNames);
        for (const [name, cat] of this.#categoryMap) {
            if (cat !== category || newSet.has(name) || this.#staticEvents.has(name)) continue;
            this.#categoryMap.delete(name);
            if (this.#active) {
                document.removeEventListener(name, this.#boundHandler, true);
            }
        }
        this.#registerEvents(eventNames, category);
    }

    #registerEvents(eventNames: string[], category: string): void {
        for (const name of eventNames) {
            if (this.#categoryMap.has(name)) continue;
            this.#categoryMap.set(name, category);
            if (this.#active) {
                document.addEventListener(name, this.#boundHandler, true);
            }
        }
    }

    clear(): void {
        this.#entries = [];
        this.#byElement = new WeakMap();
        this.#projections = new WeakMap();
        this.#frozen = new WeakSet();
        this.#changed();
    }

    destroy(): void {
        this.stop();
        this.clear();
        this.#listeners = [];
        this.#categoryMap.clear();
        this.#staticEvents.clear();
    }

    getEntriesForElement(
        element: Element,
        { includeDescendants = false }: { includeDescendants?: boolean } = {}
    ): ActivityEntry[] {
        if (!includeDescendants) return [...(this.#byElement.get(element) ?? [])];
        return this.#entries.filter((entry) => {
            if (entry.owner === element || entry.target === element) return true;
            if (entry.relatedElements?.includes(element)) return true;
            if (entry.owner && element.contains(entry.owner)) return true;
            if (entry.target && element.contains(entry.target)) return true;
            return entry.relatedElements?.some((related) => element.contains(related)) ?? false;
        });
    }

    #handleEvent(event: Event): void {
        this.record({
            type: this.#categoryMap.get(event.type) || 'unknown',
            event: event.type,
            target: event.target instanceof Element ? event.target : null,
            detail: (event as CustomEvent).detail,
        });
    }
}
