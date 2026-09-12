import { safeValue } from '../core/snapshot';

export interface ActionDescriptor {
    event: string | null;
    trigger: string | null;
    scope: string | null;
    filters: string[];
    controller: string;
    method: string;
    options: string[];
}

/** Parse Stimulus values and action parameters without losing plain strings. */
export function parseAttributeValue(raw: string, key = ''): unknown {
    let value: unknown;
    try {
        value = JSON.parse(raw);
    } catch {
        value = raw;
    }
    return safeValue(value, key);
}

export function parseActionParameters(
    element: Element,
    controller: string,
    excluded: string[] = []
): Record<string, unknown> {
    const params: Record<string, unknown> = {};
    const prefix = `data-${controller}-`;
    for (const { name, value } of element.attributes) {
        if (!name.startsWith(prefix) || !name.endsWith('-param')) continue;
        const key = name.slice(prefix.length, -6);
        if (!excluded.includes(key)) params[key] = parseAttributeValue(value, key);
    }
    return params;
}

export function parseActionDescriptor(descriptor: string): ActionDescriptor | null {
    const arrowMatch = descriptor.match(/^(.+)->(.+)#(.+)$/);
    if (arrowMatch) {
        const [method, ...options] = arrowMatch[3].split(':');
        const trigger = arrowMatch[1];
        const [eventAndFilters, scope = null] = trigger.split('@');
        const [event, ...filters] = eventAndFilters.split('.');
        return { event, trigger, scope, filters, controller: arrowMatch[2], method, options };
    }
    const hashMatch = descriptor.match(/^(.+)#(.+)$/);
    if (hashMatch) {
        const [method, ...options] = hashMatch[2].split(':');
        return { event: null, trigger: null, scope: null, filters: [], controller: hashMatch[1], method, options };
    }
    return null;
}
