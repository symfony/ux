/**
 * Updates the tooling dependencies of the workspace: the ones Dependabot updates, listed in `.github/dependabot.yml`.
 *
 * Each dependency is updated within its range. A caret range never leaves a 0.x minor, so a 0.x dependency also moves
 * to its latest version, as long as that version is still 0.x. What is left afterwards needs a new major version,
 * and is only reported.
 *
 * Usage: node bin/update_tooling_dependencies.ts
 */

import { execFileSync, spawnSync } from 'node:child_process';
import * as fs from 'node:fs';
import * as path from 'node:path';
import { parse } from 'yaml';

export type DependabotConfig = {
    updates: { 'package-ecosystem': string; allow?: { 'dependency-name': string }[] }[];
};
export type OutdatedReport = Record<string, { current: string; latest: string }>;

/**
 * Lists the dependencies Dependabot is allowed to update in the npm ecosystem.
 *
 * @returns The dependency names, some of them patterns such as `vitest*`.
 */
export function toolingDependencies(config: DependabotConfig): string[] {
    const npm = config.updates.find((update) => 'npm' === update['package-ecosystem']);

    if (undefined === npm) {
        throw new Error('The Dependabot config has no npm entry.');
    }

    return (npm.allow ?? []).map((allowed) => allowed['dependency-name']);
}

/**
 * Lists the 0.x dependencies that have a newer 0.x version, which their caret range does not allow.
 *
 * @param report The output of `pnpm outdated --format json`.
 */
export function zeroMinorBumps(report: OutdatedReport): string[] {
    const isZero = (version: string) => version.startsWith('0.');

    return Object.entries(report)
        .filter(([, { current, latest }]) => isZero(current) && isZero(latest) && current !== latest)
        .map(([name]) => name);
}

if (process.argv[1] && import.meta.filename === path.resolve(process.argv[1])) {
    const config = parse(fs.readFileSync('.github/dependabot.yml', 'utf8'));
    const dependencies = toolingDependencies(config);

    execFileSync('pnpm', ['update', '--recursive', '--dev', ...dependencies], { stdio: 'inherit' });

    // pnpm outdated exits with 1 when it finds something, so its exit code cannot tell a failure.
    const outdated = spawnSync('pnpm', ['outdated', '--recursive', '--dev', '--format', 'json', ...dependencies], {
        encoding: 'utf8',
    });
    const bumps = zeroMinorBumps(JSON.parse(outdated.stdout));

    if (bumps.length > 0) {
        execFileSync('pnpm', ['update', '--recursive', '--dev', '--latest', ...bumps], { stdio: 'inherit' });
    }

    console.log('\nThese tooling dependencies need a new major version, update them by hand:');
    spawnSync('pnpm', ['outdated', '--recursive', '--dev', ...dependencies], { stdio: 'inherit' });
}
