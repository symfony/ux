const PATH_DECODED: Record<string, string> = {
    '%2F': '/',
    '%252F': '%2F',
    '%40': '@',
    '%3A': ':',
    '%3B': ';',
    '%2C': ',',
    '%3D': '=',
    '%2B': '+',
    '%21': '!',
    '%2A': '*',
    '%7C': '|',
};
const PATH_DECODED_PATTERN = new RegExp(Object.keys(PATH_DECODED).join('|'), 'g');

const QUERY_FRAGMENT_DECODED: Record<string, string> = {
    '%2F': '/',
    '%252F': '%2F',
    '%3F': '?',
    '%40': '@',
    '%3A': ':',
    '%21': '!',
    '%3B': ';',
    '%2C': ',',
    '%2A': '*',
};
const QUERY_FRAGMENT_DECODED_PATTERN = new RegExp(Object.keys(QUERY_FRAGMENT_DECODED).join('|'), 'g');

const LEFT_UNENCODED_BY_ENCODE_URI_COMPONENT = /[!'()*]/g;
const UNRESERVED_CHARS = /^[A-Za-z0-9\-._~]*$/;
const NUMERIC = /^\s*[+-]?(?:\d+(?:\.\d*)?|\.\d+)(?:[eE][+-]?\d+)?\s*$/;

export function toPhpString(value: unknown): string {
    if (value === null || value === undefined || value === false) {
        return '';
    }

    return value === true ? '1' : String(value);
}

export function isPhpTruthy(value: unknown): boolean {
    return value !== null && value !== undefined && value !== false && value !== '' && value !== '0' && value !== 0;
}

export function isPhpArray(value: unknown): value is Record<string, unknown> | unknown[] {
    if (Array.isArray(value)) {
        return true;
    }

    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const prototype = Object.getPrototypeOf(value);

    return prototype === Object.prototype || prototype === null;
}

export function isEmptyPhpArray(value: Record<string, unknown> | unknown[]): boolean {
    return Object.keys(value).length === 0;
}

export function looseEquals(a: unknown, b: unknown): boolean {
    if (typeof a === 'boolean' || typeof b === 'boolean') {
        return isPhpTruthy(a) === isPhpTruthy(b);
    }

    if (a === null || a === undefined || b === null || b === undefined) {
        const other = a ?? b;
        if (other === null || other === undefined) {
            return true;
        }

        return typeof other === 'string' ? other === '' : !isPhpTruthy(other);
    }

    const left = toPhpString(a);
    const right = toPhpString(b);
    if (NUMERIC.test(left) && NUMERIC.test(right)) {
        return Number(left) === Number(right);
    }

    return left === right;
}

export function rawUrlEncode(value: string): string {
    if (UNRESERVED_CHARS.test(value)) {
        return value;
    }

    return encodeURIComponent(value).replace(
        LEFT_UNENCODED_BY_ENCODE_URI_COMPONENT,
        (char) => `%${char.charCodeAt(0).toString(16).toUpperCase()}`
    );
}

export function encodePath(path: string): string {
    return rawUrlEncode(path).replace(PATH_DECODED_PATTERN, (match) => PATH_DECODED[match]);
}

export function decodeQueryOrFragment(encoded: string): string {
    return encoded.replace(QUERY_FRAGMENT_DECODED_PATTERN, (match) => QUERY_FRAGMENT_DECODED[match]);
}

export function httpBuildQuery(data: Record<string, unknown> | unknown[]): string {
    const pairs: string[] = [];
    appendQueryPairs(pairs, data, null);

    return pairs.join('&');
}

function appendQueryPairs(pairs: string[], data: Record<string, unknown> | unknown[], prefix: string | null): void {
    for (const [key, value] of Object.entries(data)) {
        if (value === null || value === undefined) {
            continue;
        }

        const encodedKey = prefix === null ? rawUrlEncode(key) : `${prefix}%5B${rawUrlEncode(key)}%5D`;

        if (isPhpArray(value)) {
            appendQueryPairs(pairs, value, encodedKey);
            continue;
        }

        pairs.push(`${encodedKey}=${rawUrlEncode(typeof value === 'boolean' ? (value ? '1' : '0') : String(value))}`);
    }
}
