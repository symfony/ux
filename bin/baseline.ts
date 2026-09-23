/**
 * Web Platform Baseline level the code shipped by a package may rely on, declared in its
 * `assets/package.json`:
 *
 *     "config": { "baseline": "newly", "baselineIgnoreFeatures": ["notifications"] }
 *
 * Anything that declares nothing falls back to the repository default in the root `package.json`.
 */

import { readFileSync } from 'node:fs';
import { dirname } from 'node:path';

import { globSync } from 'tinyglobby';

import rootPackage from '../package.json' with { type: 'json' };

/** `widely`, `newly`, or the year a feature became newly available at the latest. */
export type Baseline = 'widely' | 'newly' | number;

/** A directory and the Baseline level every file under it is held to. */
export interface BaselineScope {
    dir: string;
    baseline: Baseline;
    ignoreFeatures: string[];
}

/** Repository-wide default, also read by the Baseline badge in the README. */
export const DEFAULT_BASELINE = rootPackage.config.baseline as Baseline;

/** Manifests a package can declare its level in. */
const MANIFEST_GLOBS = rootPackage.workspaces.map((workspace) => `${workspace}/package.json`);

/**
 * Everything a package ships. Sources give findings on code someone can edit, `dist` covers what
 * is actually published: tsdown bundles a few dependencies into it, `idiomorph` into LiveComponent
 * for instance, and their code never appears in the sources.
 */
export const ASSET_GLOBS = [
    ...rootPackage.workspaces.map((workspace) => `${workspace}/{src,dist}`),
    'src/Toolkit/kits/**/assets',
].map((dir) => `${dir}/**/*.{js,ts,css}`);

/**
 * Every declared scope, longest directory first so the closest declaration to a file wins.
 */
export function declaredScopes(): BaselineScope[] {
    const scopes = globSync(MANIFEST_GLOBS).flatMap((file) => {
        const { baseline, baselineIgnoreFeatures } = readJson(file).config ?? {};

        // Ignoring a feature is a declaration of its own, so a package that only lists exceptions
        // still gets a scope, held to the repository default.
        if (undefined === baseline && undefined === baselineIgnoreFeatures) {
            return [];
        }

        return [
            {
                dir: dirname(file),
                baseline: (baseline ?? DEFAULT_BASELINE) as Baseline,
                ignoreFeatures: (baselineIgnoreFeatures ?? []) as string[],
            },
        ];
    });

    return scopes.sort((a, b) => b.dir.length - a.dir.length);
}

/** Scope governing a file, or the repository default when nothing closer declares one. */
export function scopeOf(file: string, scopes: BaselineScope[]): BaselineScope {
    return (
        scopes.find(({ dir }) => file === dir || file.startsWith(`${dir}/`)) ?? {
            dir: '.',
            baseline: DEFAULT_BASELINE,
            ignoreFeatures: [],
        }
    );
}

/**
 * @throws {SyntaxError} when the file is not valid JSON, which is a repository error worth
 *   failing on rather than silently ignoring the declaration it carries.
 */
function readJson(file: string): Record<string, any> {
    return JSON.parse(readFileSync(file, 'utf8'));
}
