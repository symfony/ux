import type {
    ActivityEvent,
    ActivityRow,
    LiveOperationRow,
    RepeatedActivityRow,
    TurboFetchRow,
    TurboFetchInfo,
} from '../types';

const REQUEST = 'turbo:before-fetch-request';
const RESPONSE = 'turbo:before-fetch-response';

type Bag = Record<string, unknown>;

export function projectActivity(entries: readonly ActivityEvent[], compact = true): ActivityRow[] {
    const open: TurboFetchRow[] = [];
    const live = new Map<Element | null, LiveOperationRow>();
    const pendingChanges = new Map<Element | null, ActivityEvent[]>();
    const rows: ActivityRow[] = [];
    for (const entry of entries) {
        const key = targetOf(entry);
        if (entry.type === 'livecomponent') {
            if (entry.event === 'live:model:set') {
                const operation = live.get(key);
                if (operation) appendLiveEntry(operation, entry);
                else {
                    let changes = pendingChanges.get(key);
                    if (!changes) pendingChanges.set(key, (changes = []));
                    changes.push(entry);
                }
                continue;
            }
            if (entry.event === 'live:request' || entry.event === 'live:render:started') {
                let operation = live.get(key);
                if (!operation || entry.event === 'live:request') {
                    operation = liveOperation(entry, pendingChanges.get(key) || []);
                    pendingChanges.delete(key);
                    rows.push(operation);
                    live.set(key, operation);
                } else appendLiveEntry(operation, entry);
                continue;
            }
            if (entry.event === 'live:render:finished' || entry.event === 'live:response:error') {
                const operation = live.get(key);
                if (operation) {
                    appendLiveEntry(operation, entry);
                    const info = operation.live;
                    info.status = entry.event === 'live:response:error' ? 'error' : 'complete';
                    info.duration = Math.max(0, entry.time - info.startedAt);
                    live.delete(key);
                    continue;
                }
                if (entry.event === 'live:render:finished') continue;
            }
        }
        if (isFetch(entry, REQUEST)) {
            const fetch = normalizeTurboFetchEntry(entry);
            const row: TurboFetchRow = {
                id: entry.id,
                type: 'turbo',
                event: 'turbo:fetch',
                activityKind: 'turbo-fetch',
                label: `${fetch.intent === 'prefetch' ? 'prefetch' : 'fetch'} ${fetch.method} ${compactUrl(fetch.url)}`.trim(),
                time: entry.time,
                target: entry.target,
                owner: entry.owner,
                relatedElements: entry.relatedElements || [],
                fetch: { ...fetch, pending: true, duration: null, status: null },
                rawEntries: [entry],
                rawCount: 1,
                occurrences: 1,
            };
            rows.push(row);
            open.push(row);
            continue;
        }
        if (isFetch(entry, RESPONSE)) {
            const response = normalizeTurboFetchEntry(entry);
            const index = open.findIndex(
                (row) => targetOf(row) === key && (!response.url || !row.fetch.url || response.url === row.fetch.url)
            );
            if (index >= 0) {
                const row = open.splice(index, 1)[0];
                const info = row.fetch;
                Object.assign(info, {
                    pending: false,
                    status: response.status,
                    duration: Math.max(0, entry.time - row.time),
                    responseUrl: response.url || info.url,
                });
                row.rawEntries.push(entry);
                row.rawCount++;
                continue;
            }
        }
        rows.push(entry);
    }

    for (const changes of pendingChanges.values()) rows.push(...changes);
    rows.sort((a, b) => a.time - b.time);
    if (!compact) return rows;

    const result: ActivityRow[] = [];
    for (const row of rows) {
        const previous = result.at(-1);
        if (
            previous?.activityKind === 'turbo-fetch' &&
            row.activityKind === 'turbo-fetch' &&
            sameCompleteFetch(previous, row)
        ) {
            previous.occurrences = (previous.occurrences ?? 1) + 1;
            previous.rawEntries.push(...row.rawEntries);
            previous.rawCount = previous.rawEntries.length;
            previous.time = row.time;
            previous.fetch.duration = row.fetch.duration;
        } else if (previous && sameRaw(previous, row)) {
            const group: RepeatedActivityRow =
                previous.activityKind === 'repeated'
                    ? previous
                    : {
                          ...previous,
                          activityKind: 'repeated',
                          fetch: undefined,
                          live: undefined,
                          rawEntries: [previous],
                          rawCount: 1,
                          occurrences: 1,
                      };
            group.rawEntries.push(row);
            group.rawCount = (group.rawCount ?? 0) + 1;
            group.occurrences = (group.occurrences ?? 1) + 1;
            result[result.length - 1] = group;
        } else {
            result.push(row);
        }
    }
    return result;
}

function liveOperation(entry: ActivityEvent, changes: readonly ActivityEvent[]): LiveOperationRow {
    const detail = record(entry.detail);
    const actions = Array.isArray(detail.actions) ? detail.actions : [];
    const models = Array.isArray(detail.models) ? detail.models : [];
    const trigger = actions.length
        ? `${actions.join(', ')}()`
        : models.length
          ? `model ${models.join(', ')}`
          : 'render';
    const operation: LiveOperationRow = {
        id: changes[0]?.id ?? entry.id,
        type: 'livecomponent',
        event: 'live:rerender',
        activityKind: 'live-rerender',
        label: trigger === 'render' ? 'rerender' : `rerender · ${trigger}`,
        time: changes[0]?.time ?? entry.time,
        target: entry.target,
        owner: entry.owner,
        relatedElements: entry.relatedElements || [],
        rawEntries: [],
        rawCount: 0,
        occurrences: 1,
        live: {
            trigger,
            actions,
            models,
            changes: [],
            hooks: [],
            status: 'pending',
            startedAt: entry.time,
            duration: null,
        },
    };
    for (const change of changes) appendLiveEntry(operation, change);
    appendLiveEntry(operation, entry);
    return operation;
}

function appendLiveEntry(operation: LiveOperationRow, entry: ActivityEvent): void {
    operation.rawEntries.push(entry);
    operation.rawCount = (operation.rawCount ?? 0) + 1;
    const info = operation.live;
    if (entry.event === 'live:model:set') {
        const detail = record(entry.detail);
        const previous = info.changes.find((change) => change.model === detail.model);
        if (previous) previous.value = detail.value;
        else info.changes.push({ ...detail });
    } else info.hooks.push(entry.event.replace(/^live:/, ''));
}

export function activityCounts(entries: readonly ActivityEvent[]): Record<string, number> {
    const counts: Record<string, number> = {};
    for (const row of projectActivity(entries)) counts[row.type] = (counts[row.type] || 0) + 1;
    return counts;
}

export function normalizeTurboFetchEntry(entry: ActivityEvent | null | undefined): TurboFetchInfo {
    const detail = record(entry?.detail);
    const request = record(detail.request || detail.fetchRequest);
    const options = record(detail.fetchOptions || request.fetchOptions || request.options);
    const fetchResponse = record(detail.fetchResponse || detail.response);
    const response = record(fetchResponse.response || fetchResponse);
    const headers = record(options.headers || request.headers);
    const purpose = Object.entries(headers).find(([key]) => key.toLowerCase() === 'x-sec-purpose')?.[1];
    return {
        intent: String(purpose || '').toLowerCase() === 'prefetch' ? 'prefetch' : 'fetch',
        method: String(options.method || request.method || (entry?.event === REQUEST ? 'GET' : '')).toUpperCase(),
        url: url(
            detail.url || request.url || request.location || options.url || response.url || fetchResponse.location
        ),
        priority:
            typeof (options.priority || request.priority) === 'string'
                ? String(options.priority || request.priority)
                : '',
        status: number(response.status ?? fetchResponse.statusCode),
    };
}

function isFetch(entry: ActivityEvent | null | undefined, name: string): boolean {
    return entry?.type === 'turbo' && entry.event === name;
}

function sameCompleteFetch(a: TurboFetchRow, b: TurboFetchRow): boolean {
    const left = a.fetch;
    const right = b.fetch;
    return (
        !left.pending &&
        !right.pending &&
        targetOf(a) === targetOf(b) &&
        left.intent === right.intent &&
        left.method === right.method &&
        left.url === right.url &&
        left.status === right.status &&
        left.priority === right.priority
    );
}

function sameRaw(a: ActivityRow | undefined, b: ActivityRow): boolean {
    if (!a || (a.activityKind && a.activityKind !== 'repeated') || b.activityKind) return false;
    const source = a.rawEntries?.[0] || a;
    return (
        source.type === b.type &&
        source.event === b.event &&
        (source.label || '') === (b.label || '') &&
        source.target === b.target
    );
}

function record(value: unknown): Bag {
    return value && typeof value === 'object' && !Array.isArray(value) ? (value as Bag) : {};
}

function url(value: unknown): string {
    if (typeof value === 'string') return value;
    const object = record(value);
    return typeof (object.href || object.url) === 'string' ? String(object.href || object.url) : '';
}

function number(value: unknown): number | null {
    const result = Number(value);
    return Number.isFinite(result) ? result : null;
}

function compactUrl(value: string): string {
    if (!value) return '';
    try {
        const parsed = new URL(value, document.baseURI);
        return parsed.pathname + parsed.search + parsed.hash;
    } catch {
        return value;
    }
}

function targetOf(entry: ActivityEvent): Element | null {
    return entry.target instanceof Element ? entry.target : null;
}
