/**
 * Indexes the `web-features` catalog by the source constructs a checker can spot, so a JavaScript
 * name (`URLPattern`, `navigator.clipboard`) or a CSS construct (`anchor-name`, `:has`) resolves to
 * the feature that carries its Baseline status.
 *
 * Both indexes are derived from the `compat_features` keys, which already spell out the construct:
 * `api.URLPattern`, `javascript.builtins.Promise.try`, `css.properties.text-wrap.balance`.
 */

import { features } from 'web-features';

import type { Baseline } from './baseline.ts';

export interface Feature {
    id: string;
    name: string;
    /** `high` is Widely available, `low` is Newly available, `false` is Limited availability. */
    baseline: 'high' | 'low' | false;
    newlySince?: string;
    widelyOn?: string;
}

/** Interfaces reachable through a global instance, so `api.Navigator.clipboard` is spottable. */
const GLOBAL_INSTANCES: Record<string, string> = {
    Document: 'document',
    History: 'history',
    Location: 'location',
    Navigator: 'navigator',
    Performance: 'performance',
    Screen: 'screen',
    Window: 'window',
};

/**
 * @param key the BCD key the construct was found under, e.g. `css.properties.cursor`.
 */
function toFeature(id: string, key: string): Feature {
    // A feature aggregates every one of its compat keys, so `cursor` reads as Limited because of a
    // rarely supported keyword while the property itself has been Widely available for years. Only
    // the per-key status describes the construct a checker actually spotted.
    const status = features[id].status.by_compat_key?.[key] ?? features[id].status;

    return {
        id,
        name: features[id].name,
        baseline: status.baseline,
        newlySince: status.baseline_low_date,
        widelyOn: status.baseline_high_date ?? widelyDate(status.baseline_low_date),
    };
}

/**
 * @param keyToName turns the segments of a BCD key into the construct a checker can spot, or
 *   undefined for the keys this index has no way to recognise.
 */
function index(keyToName: (parts: string[]) => string | undefined): Map<string, Feature> {
    const byName = new Map<string, Feature>();

    for (const id of Object.keys(features)) {
        for (const key of features[id].compat_features ?? []) {
            const name = keyToName(key.split('.'));
            if (undefined === name) {
                continue;
            }

            // A construct can belong to several features; the narrowest status must win.
            const feature = toFeature(id, key);
            const current = byName.get(name);
            if (!current || rank(current.baseline) > rank(feature.baseline)) {
                byName.set(name, feature);
            }
        }
    }

    return byName;
}

/** A feature turns Widely available 30 months after it turned Newly available. */
function widelyDate(newlySince?: string): string | undefined {
    if (!newlySince) {
        return undefined;
    }

    const date = new Date(`${newlySince}T00:00:00Z`);
    date.setUTCMonth(date.getUTCMonth() + 30);

    return date.toISOString().slice(0, 10);
}

/** Orders the three statuses so the narrowest one can win a collision. */
function rank(baseline: 'high' | 'low' | false): number {
    return false === baseline ? 0 : 'low' === baseline ? 1 : 2;
}

/** Globals, their static members, and the members of the interfaces reachable from a global. */
export const JS_FEATURES: Map<string, Feature> = index(([root, ...rest]) => {
    if ('javascript' === root && 'builtins' === rest[0]) {
        const [, ...path] = rest;

        return path.length <= 2 ? path.join('.') : undefined;
    }

    if ('api' !== root) {
        return undefined;
    }

    const [interfaceName, member] = rest;
    if (1 === rest.length) {
        return interfaceName;
    }

    // A key naming something deeper than a member, like `api.X.y.z`, describes a construct the
    // checker cannot tell apart from plain `X.y`, so it must not drag `X.y` down with it.
    if (2 !== rest.length) {
        return undefined;
    }

    // `api.X.X` is the constructor of X, already covered by the interface itself.
    if (member === interfaceName) {
        return undefined;
    }

    if (member.endsWith('_static')) {
        return `${interfaceName}.${member.slice(0, -'_static'.length)}`;
    }

    const instance = GLOBAL_INSTANCES[interfaceName];

    return instance ? `${instance}.${member}` : undefined;
});

/** Properties, property values, selectors and at-rules, keyed by a `kind:name` construct. */
export const CSS_FEATURES: Map<string, Feature> = index(([root, group, ...rest]) => {
    if ('css' !== root || rest.length > 2) {
        return undefined;
    }

    if ('properties' === group) {
        const [property, value] = rest;

        return value ? `property:${property}:${value}` : `property:${property}`;
    }

    if (1 !== rest.length) {
        return undefined;
    }

    if ('selectors' === group) {
        return `selector:${rest[0]}`;
    }

    if ('at-rules' === group) {
        return `at-rule:${rest[0]}`;
    }

    return undefined;
});

/** Whether a feature has reached the level a scope is held to. */
export function isAllowed(feature: Feature, baseline: Baseline): boolean {
    if ('high' === feature.baseline) {
        return true;
    }

    if ('low' !== feature.baseline) {
        return false;
    }

    if ('newly' === baseline) {
        return true;
    }

    return undefined !== feature.newlySince && Number.parseInt(feature.newlySince, 10) <= baseline;
}
