/**
 * Lowest browser versions a Baseline level covers, as tsdown targets.
 *
 * Kept out of `baseline.ts` so the Baseline check does not pay for Browserslist, which only the
 * build needs.
 */

import browserslist from 'browserslist';

import type { Baseline } from './baseline.ts';

// Browserslist reports a single version for the two Android browsers, the latest one rather
// than a floor, and esbuild targets have no entry for them anyway since they share the engine
// version of their desktop counterpart.
const TARGET_NAMES: Record<string, string> = {
    chrome: 'chrome',
    edge: 'edge',
    firefox: 'firefox',
    safari: 'safari',
    ios_saf: 'ios',
};

/**
 * Lowest browser versions the target Baseline covers, as tsdown targets.
 *
 * tsdown ships its own `baseline-widely-available` target, but it expands to a snapshot frozen in
 * the tsdown release rather than to the current Baseline, so Browserslist stays the single source.
 */
export function baselineTarget(baseline: Baseline): string[] {
    // `baseline newly available` resolves to no browser at all, and a lower floor only ever means
    // more downleveled syntax, so anything past Widely available compiles for it too.
    const query = 'number' === typeof baseline ? `baseline ${baseline}` : 'baseline widely available';

    // Browserslist lists versions from newest to oldest, so the last one seen is the floor.
    const floor = new Map<string, string>();
    for (const entry of browserslist(query)) {
        // Caniuse merges releases, so an entry reads `ios_saf 17.6-17.7`; the floor is its start.
        const [name, version] = entry.replace(/-.*/, '').split(' ');
        const target = TARGET_NAMES[name];
        if (target) {
            floor.set(target, version);
        }
    }

    return Array.from(floor, ([name, version]) => `${name}${version}`);
}
