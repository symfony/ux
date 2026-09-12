/** Bounded snapshots and redaction shared by detection and activity. */
export const SENSITIVE_KEY = /password|secret|token|authorization|cookie|csrf/i;
const MAX_STRING_LENGTH = 500;
const MAX_ITEMS = 20;
type Bag = Record<string, unknown>;

/** Descent into a nested value, with the key it was found under ('' for array items). */
type Descend = (value: unknown, key: string, depth: number) => unknown;

/** The parts of a container copy that differ between the two snapshots. */
interface CopyRules {
    /** Marker for what the item cap dropped: appended to arrays, flagged as `_truncated` on objects. */
    overflow: string | null;
    /** Report a sensitive accessor as redacted rather than as an accessor; the getter stays uninvoked either way. */
    redactAccessors: boolean;
}

const VALUE_RULES: CopyRules = { overflow: null, redactAccessors: false };
const EVENT_RULES: CopyRules = { overflow: '[truncated]', redactAccessors: true };

function truncate(text: string, overflow = ''): string {
    return text.length > MAX_STRING_LENGTH ? `${text.slice(0, MAX_STRING_LENGTH)}${overflow}` : text;
}

/** Copies the enumerable own entries of an array or object, capped, without invoking getters. */
function copyContainer(value: object, depth: number, descend: Descend, rules: CopyRules): unknown {
    if (Array.isArray(value)) {
        const items: unknown[] = value.slice(0, MAX_ITEMS).map((item) => descend(item, '', depth + 1));
        if (rules.overflow !== null && value.length > MAX_ITEMS) items.push(rules.overflow);
        return items;
    }

    let descriptors: Record<string, PropertyDescriptor>;
    try {
        descriptors = Object.getOwnPropertyDescriptors(value);
    } catch {
        return '[unavailable]';
    }

    const keys = Object.keys(descriptors).filter((key) => descriptors[key].enumerable);
    const entries: [string, unknown][] = [];
    for (const key of keys.slice(0, MAX_ITEMS)) {
        const descriptor = descriptors[key];
        if ('value' in descriptor) {
            entries.push([key, descend(descriptor.value, key, depth + 1)]);
        } else {
            entries.push([key, rules.redactAccessors && SENSITIVE_KEY.test(key) ? '[redacted]' : '[accessor]']);
        }
    }
    // fromEntries, not assignment: a "__proto__" key would otherwise retarget the snapshot prototype.
    const result: Bag = Object.fromEntries(entries);
    if (rules.overflow !== null && keys.length > MAX_ITEMS) result._truncated = true;
    return result;
}

export function safeValue(value: unknown, key = '', depth = 0, seen: WeakSet<object> = new WeakSet()): unknown {
    if (SENSITIVE_KEY.test(key)) return '[redacted]';
    if (depth > 3) return '[max depth]';
    if (typeof value === 'string') return truncate(value);
    if (value === null || typeof value !== 'object') return value;
    if (seen.has(value)) return '[circular]';
    seen.add(value);
    return copyContainer(value, depth, (item, name, next) => safeValue(item, name, next, seen), VALUE_RULES);
}

export function safeUrl(value: unknown, absolute = false): string {
    const input = truncate(String(value || ''));
    if (!input) return '';
    try {
        const url = new URL(input, document.baseURI);
        redactParams(url.searchParams);
        return absolute || /^[a-z][a-z\d+.-]*:/i.test(input) ? url.href : `${url.pathname}${url.search}${url.hash}`;
    } catch {
        return input;
    }
}

export function snapshotEvent(value: unknown): unknown {
    const seen = new WeakSet<object>();
    let budget = 100;

    const copy = (item: unknown, key: string, depth: number): unknown => {
        try {
            return copyValue(item, key, depth);
        } catch {
            return '[unavailable]';
        }
    };
    const copyValue = (item: unknown, key: string, depth: number): unknown => {
        if (SENSITIVE_KEY.test(key)) return '[redacted]';
        if (item === null || item === undefined || typeof item === 'boolean' || typeof item === 'number') {
            return item ?? null;
        }
        if (typeof item === 'string') return truncate(item, '[truncated]');
        if (typeof item === 'bigint') return `${item}n`;
        if (typeof item !== 'object') return `[${typeof item}]`;
        if (item instanceof Element) return describeElement(item);
        if (item instanceof Date) {
            const time = Date.prototype.getTime.call(item);
            return Number.isNaN(time) ? '[invalid date]' : new Date(time).toISOString();
        }
        if (item instanceof Error) return { name: item.name, message: item.message };
        // globalThis guards: a missing constructor would make instanceof throw,
        // and the catch in copy() would blank the whole value instead of one branch.
        if (globalThis.URL && item instanceof URL) return absoluteUrl(item.href);
        if (globalThis.URLSearchParams && item instanceof URLSearchParams) {
            return truncate(redactParams(new URLSearchParams(item)).toString());
        }
        if (globalThis.Headers && item instanceof Headers) return snapshotHeaders(item);
        if (globalThis.Request && item instanceof Request) {
            return {
                url: absoluteUrl(item.url),
                method: item.method,
                headers: snapshotHeaders(item.headers),
                credentials: item.credentials,
                redirect: item.redirect,
                mode: item.mode,
                referrer: absoluteUrl(item.referrer),
                referrerPolicy: item.referrerPolicy,
                priority: (item as Request & { priority?: string }).priority || '',
                signal: copy(item.signal, '', depth + 1),
            };
        }
        if (globalThis.Response && item instanceof Response) {
            return {
                url: absoluteUrl(item.url),
                status: item.status,
                statusText: item.statusText,
                ok: item.ok,
                redirected: item.redirected,
                type: item.type,
                headers: snapshotHeaders(item.headers),
            };
        }
        if (globalThis.AbortSignal && item instanceof AbortSignal) return { aborted: item.aborted };
        if (seen.has(item)) return '[circular]';
        // Shallower than safeValue, plus a budget shared by the whole snapshot: an event
        // detail is copied on every recorded event, so a wide graph must not stall the page.
        if (depth >= 3 || budget-- <= 0) return '[truncated]';
        seen.add(item);
        return copyContainer(item, depth, copy, EVENT_RULES);
    };

    return copy(value, '', 0);
}

function redactParams(params: URLSearchParams): URLSearchParams {
    // Snapshot unique keys: set() removes duplicates and shifts the live iterator.
    for (const key of new Set(params.keys())) {
        if (SENSITIVE_KEY.test(key)) params.set(key, '[redacted]');
    }
    return params;
}

function snapshotHeaders(headers: Headers): Bag {
    const result: Bag = {};
    let count = 0;
    try {
        for (const [name, value] of headers) {
            if (count++ === 30) {
                result._truncated = true;
                break;
            }
            result[name] = SENSITIVE_KEY.test(name) ? '[redacted]' : truncate(String(value));
        }
    } catch {
        return { _error: '[unavailable]' };
    }
    return result;
}

function absoluteUrl(value: unknown): string {
    return safeUrl(value, true);
}

function describeElement(el: Element): string {
    const tag = el.tagName.toLowerCase();
    const id = el.id ? `#${el.id}` : '';
    const cls =
        el.className && typeof el.className === 'string'
            ? '.' + el.className.trim().split(/\s+/).slice(0, 2).join('.')
            : '';
    return `${tag}${id}${cls}`;
}

/** Freeze owned diagnostic data while leaving inspected DOM nodes untouched. */
export function freezeSnapshot<T>(value: T, seen = new WeakSet<object>()): T {
    if (!value || typeof value !== 'object' || value instanceof Node || seen.has(value)) return value;
    seen.add(value);
    for (const descriptor of Object.values(Object.getOwnPropertyDescriptors(value))) {
        if ('value' in descriptor) freezeSnapshot(descriptor.value, seen);
    }
    return Object.freeze(value);
}

/** Compare owned data structurally and page objects by identity. Never invoke getters. */
export function sameSnapshot(left: unknown, right: unknown, seen = new WeakMap<object, object>()): boolean {
    if (Object.is(left, right)) return true;
    if (!left || !right || typeof left !== 'object' || typeof right !== 'object') return false;
    if (left instanceof Node || right instanceof Node || Object.getPrototypeOf(left) !== Object.getPrototypeOf(right))
        return false;
    const prototype = Object.getPrototypeOf(left);
    if (prototype !== Object.prototype && prototype !== Array.prototype && prototype !== null) return false;
    if (seen.has(left)) return seen.get(left) === right;
    seen.set(left, right);
    const a = Object.getOwnPropertyDescriptors(left);
    const b = Object.getOwnPropertyDescriptors(right);
    return (
        Object.keys(a).length === Object.keys(b).length &&
        Object.keys(a).every(
            (key) =>
                Object.hasOwn(b, key) &&
                'value' in a[key] &&
                'value' in b[key] &&
                sameSnapshot(a[key].value, b[key].value, seen)
        )
    );
}
