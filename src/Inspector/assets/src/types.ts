import type { QueryElements } from './core/dom-query';

export interface ComponentData<T extends Record<string, unknown> = Record<string, unknown>> {
    type: string;
    element: Element;
    data: T;
}

export type ComponentDataMap = Map<string, ComponentData>;

export interface RelationshipEdge {
    source: Element;
    target: Element;
    /** e.g. 'outlet', 'parent-child', 'frame-nesting', 'listener' */
    type: string;
    label: string;
    [key: string]: unknown;
}

export interface MonitoredEvents {
    /** Lifecycle events to always listen for (registered once). */
    static: string[];
    /** Event names derived from detected elements, recomputed on each scan. */
    dynamic?: (elements: Element[]) => string[];
}

export interface PageRule {
    kind: string;
    label: string;
    detail?: string;
    element: Element;
    framework?: string;
    [key: string]: unknown;
}

export type ActivityDraft = {
    id?: number;
    time: number;
    type: string;
    event: string;
    target: Element | null;
    owner: Element | null;
    detail: unknown;
    relatedElements: Element[];
    label?: string;
};

/** Published entries are immutable; adapters only receive the unpublished draft. */
export type ActivityEntry = Readonly<Omit<ActivityDraft, 'relatedElements'>> & {
    readonly relatedElements: readonly Element[];
};

export interface ActivityInput {
    type?: string;
    event: string;
    target?: Element | null;
    owner?: Element | null;
    detail?: unknown;
    label?: string | null;
    relatedElements?: unknown[];
}

export type EventRecorder = (entry: ActivityInput) => void;

/** DOM records coalesced for one detection transaction. */
export interface MutationChange {
    type: 'attributes' | 'childList' | 'characterData';
    target: Node;
    attributeName: string | null;
    addedNodes: readonly Node[];
    removedNodes: readonly Node[];
}

/** Internal framework adapters: component reads, observation, and relationships. */
export interface InspectorPlugin<T extends Record<string, unknown> = Record<string, unknown>> {
    name: string;
    selectors: string[];
    canHandle(element: Element): boolean;
    parse(element: Element, query?: QueryElements): ComponentData<T>;
    getDisplayName(element: Element): string;
    onEvent?(entry: ActivityDraft, element: Element): void;
    observe?(element: Element): void;
    onElementRemoved?(element: Element): void;
    /** Receive a safe internal event sink. */
    setEventRecorder?(record: EventRecorder): void;
    getRelationships?(element: Element, data: ComponentData<T>): RelationshipEdge[];
    getWatchedAttributes?(): string[];
    matchesAttribute?(name: string): boolean;
    getPageRules?(doc: Document): PageRule[];
    /** Compare dependencies outside the component against the current DOM snapshot. */
    hasExternalChanges?(element: Element, data: ComponentData<T>, query: QueryElements): boolean;
    getMonitoredEvents?(): MonitoredEvents;
    destroy?(): void;
}

export interface RenderContext {
    events?: ActivityRow[];
    [key: string]: unknown;
}

export interface CollectedEvents {
    /** pluginName -> static event names */
    staticEvents: Map<string, string[]>;
    /** pluginName -> dynamic event names */
    dynamicEvents: Map<string, string[]>;
}

export interface TurboFetchInfo {
    intent: string;
    method: string;
    url: string;
    priority: string;
    status: number | null;
    pending?: boolean;
    duration?: number | null;
    responseUrl?: string;
}

export interface LiveModelChange {
    model?: string;
    value?: unknown;
    [key: string]: unknown;
}

export interface LiveOperationInfo {
    trigger: string;
    actions: string[];
    models: string[];
    changes: LiveModelChange[];
    hooks: string[];
    status: string;
    startedAt: number;
    duration: number | null;
}

/** Raw event shape accepted by pure projection, including imported diagnostics. */
export interface ActivityEvent {
    readonly id?: number;
    readonly type: string;
    readonly event: string;
    readonly time: number;
    readonly target?: Element | null;
    readonly owner?: Element | null;
    readonly detail?: unknown;
    readonly label?: string;
    readonly relatedElements?: readonly Element[];
}

interface RowBase extends ActivityEvent {
    time: number;
    rawEntries?: readonly ActivityEvent[];
    rawCount?: number;
    occurrences?: number;
}

export interface RawActivityRow extends RowBase {
    activityKind?: undefined;
    fetch?: never;
    live?: never;
}

export interface RepeatedActivityRow extends RowBase {
    activityKind: 'repeated';
    rawEntries: ActivityEvent[];
    rawCount: number;
    occurrences: number;
    fetch?: never;
    live?: never;
}

export interface TurboFetchRow extends RowBase {
    activityKind: 'turbo-fetch';
    rawEntries: ActivityEvent[];
    rawCount: number;
    occurrences: number;
    fetch: TurboFetchInfo;
    live?: never;
}

export interface LiveOperationRow extends RowBase {
    activityKind: 'live-rerender';
    rawEntries: ActivityEvent[];
    rawCount: number;
    occurrences: number;
    live: LiveOperationInfo;
    fetch?: never;
}

export type ActivityRow = RawActivityRow | RepeatedActivityRow | TurboFetchRow | LiveOperationRow;
