/**
 * Prints the workspace directories a release has to bump, one per line.
 *
 * The Symfony splitter leaves a subtree untagged when a release changed nothing in it, so bumping the whole workspace
 * on every patch makes npm advertise versions that exist nowhere else. split.sh answers which repositories a release
 * will tag; bumping exactly those keeps the numbers in step, and `pnpm publish --recursive` then needs no instructions
 * at all, since it skips any package whose version the registry already serves.
 *
 * It prints nothing when a release only moves subtrees that ship no npm package, which is a legitimate release, and
 * refuses one that would tag no repository at all.
 *
 * The Release on NPM workflow cannot publish a package npm does not have yet, so the first version of a new package is published by hand.
 * The script refuses a release that leaves such a package out, and `--first-publish` prints how to publish them by hand.
 *
 * Usage: node bin/release_packages.ts --branch 3.x --version 3.5.1
 *        node bin/release_packages.ts --first-publish
 */

import * as fs from 'node:fs';
import * as path from 'node:path';
import { parseArgs } from 'node:util';

const SPLIT_PROJECT = 'https://go.split.sh/api/projects/symfonyux';
const PREDICTION_TIMEOUT_MS = 30_000;
const NPM_REGISTRY = 'https://registry.npmjs.org';
const REGISTRY_TIMEOUT_MS = 30_000;

/**
 * The parts of `splitsh.json` this script reads. A subtree is declared either as a bare prefix or as an object, the
 * latter being how `ux-map` excludes its bridges from its own split.
 *
 * @example
 * {
 *     subtrees: {
 *         'ux-vue': 'src/Vue',
 *         'ux-google-map': 'src/Map/src/Bridge/Google',
 *         'ux-map': { prefixes: [{ from: 'src/Map', to: '', excludes: ['src/Bridge'] }] },
 *     },
 * }
 */
export type SplitshConfig = { subtrees: Record<string, string | { prefixes: { from: string }[] }> };

/**
 * The part of split.sh's answer this script reads. The response also carries `branch`, `version` and a `skipped`
 * list, none of which are needed here.
 */
export type TagPrediction = { tagged: string[] };

/**
 * Resolves the monorepo directory a split repository is built from.
 *
 * @param config Parsed `splitsh.json`.
 * @param split A repository name as split.sh spells it, without the `symfony/` prefix.
 * @returns The prefix the subtree is split from, relative to the repository root.
 * @throws If `splitsh.json` does not declare the subtree, which would silently leave its package behind, or if it
 *         declares several prefixes, which could not be mapped back to a single package.
 *
 * @example
 * prefixOf(config, 'ux-vue'); // 'src/Vue'
 * prefixOf(config, 'ux-google-map'); // 'src/Map/src/Bridge/Google'
 * prefixOf(config, 'ux-map'); // 'src/Map', read from prefixes[0].from
 */
export function prefixOf(config: SplitshConfig, split: string): string {
    const subtree = config.subtrees[split];

    if (undefined === subtree) {
        throw new Error(`split.sh will tag "${split}", which splitsh.json does not declare. Compare the two.`);
    }

    if (typeof subtree === 'string') {
        return subtree;
    }

    if (1 !== subtree.prefixes.length) {
        throw new Error(`The subtree "${split}" declares ${subtree.prefixes.length} prefixes, only one is supported.`);
    }

    return subtree.prefixes[0].from;
}

/**
 * Tells whether a directory holds a manifest carrying a version, which is the only thing `pnpm version` can bump.
 *
 * Being private is not a reason to leave a package out: `@symfony/ux-map` and `@symfony/stimulus-bundle` have always
 * been bumped, because their manifest ships inside their split repository, where anyone depending on the assets
 * through `file:` or `path:` reads it. They are simply never published, `pnpm publish --recursive` skipping them.
 *
 * @param directory A workspace directory, relative to the repository root.
 *
 * @example
 * hasVersionToBump('src/Map/assets'); // true, private but versioned
 * hasVersionToBump('src/Toolkit/assets'); // false, ships controllers and CSS but no manifest
 * hasVersionToBump('src/CalendarLink/assets'); // false, a manifest with no version field
 */
export function hasVersionToBump(directory: string): boolean {
    const manifest = path.join(directory, 'package.json');

    return fs.existsSync(manifest) && undefined !== JSON.parse(fs.readFileSync(manifest, 'utf8')).version;
}

/**
 * Turns the repositories split.sh will tag into the workspace directories to bump, in the order it listed them.
 *
 * Subtrees with nothing to bump are dropped rather than refused, and a release touching only those is a normal
 * release that bumps nothing and prepares the tag alone.
 *
 * @param prediction What split.sh answered for this branch and version.
 * @param config Parsed `splitsh.json`.
 * @param hasVersion Tells whether a directory has a version to bump. Injected so the decision stays testable.
 * @returns The directories to hand to `pnpm version --filter`, possibly empty.
 * @throws If split.sh would tag no repository at all, since there is then nothing to release.
 *
 * @example
 * // 3.5.1 only touched Map and Pagination
 * bumpDirectories({ tagged: ['ux-map', 'ux-pagination'] }, config, hasVersionToBump);
 * // ['src/Map/assets', 'src/Pagination/assets']
 *
 * @example
 * // TwigComponent has no manifest to bump, so this release only gets its tag
 * bumpDirectories({ tagged: ['ux-twig-component'] }, config, hasVersionToBump); // []
 */
export function bumpDirectories(
    prediction: TagPrediction,
    config: SplitshConfig,
    hasVersion: (directory: string) => boolean
): string[] {
    if (0 === prediction.tagged.length) {
        throw new Error('split.sh would tag no repository for this release, so there is nothing to release.');
    }

    return prediction.tagged.map((split) => `${prefixOf(config, split)}/assets`).filter(hasVersion);
}

/**
 * Asks split.sh which repositories it will tag, for the branch as it currently has it.
 *
 * The answer describes what split.sh can see, so it is only valid while the local branch matches `upstream`, which
 * `release.sh` checks before calling this.
 *
 * @param branch The release branch, such as `3.x`.
 * @param version The version being released, without the leading `v`.
 * @returns The prediction, of which only `tagged` is used downstream.
 * @throws If split.sh is unreachable, times out after 30 seconds, answers a non-2xx status, or answers something
 *         that carries no `tagged` list.
 *
 * @example
 * await fetchPrediction('3.x', '3.5.1');
 * // { branch: '3.x', tagged: ['ux-map', 'ux-pagination'], skipped: ['stimulus-bundle', ...], version: '3.5.1' }
 *
 * @example
 * // nothing changed since the previous tag
 * await fetchPrediction('3.x', '3.5.2'); // { ..., tagged: [], skipped: [...20 subtrees] }
 */
export async function fetchPrediction(branch: string, version: string): Promise<TagPrediction> {
    const url = `${SPLIT_PROJECT}/branches/${encodeURIComponent(branch)}/tag-prediction?version=${encodeURIComponent(version)}`;
    const response = await fetch(url, { signal: AbortSignal.timeout(PREDICTION_TIMEOUT_MS) });
    const body = await response.text();

    if (!response.ok) {
        throw new Error(`split.sh answered ${response.status} for ${branch} ${version}: ${body}`);
    }

    const prediction = JSON.parse(body);

    if (!Array.isArray(prediction?.tagged)) {
        throw new Error(`split.sh answered without a "tagged" list for ${branch} ${version}: ${body}`);
    }

    return prediction;
}

/**
 * A package `pnpm publish --recursive` publishes, with the workspace directory it lives in.
 */
export type NpmPackage = { directory: string; name: string };

/**
 * Lists the packages `pnpm publish --recursive` publishes: every versioned manifest that is not private.
 *
 * @param config Parsed `splitsh.json`, which declares every published package.
 *
 * @example
 * publishablePackages(config);
 * // [{ directory: 'src/Autocomplete/assets', name: '@symfony/ux-autocomplete' }, ...], without the private ux-map
 */
export function publishablePackages(config: SplitshConfig): NpmPackage[] {
    const packages: NpmPackage[] = [];

    for (const split of Object.keys(config.subtrees)) {
        const directory = `${prefixOf(config, split)}/assets`;

        if (!hasVersionToBump(directory)) {
            continue;
        }

        const manifest = JSON.parse(fs.readFileSync(path.join(directory, 'package.json'), 'utf8'));

        if (true !== manifest.private) {
            packages.push({ directory, name: manifest.name });
        }
    }

    return packages;
}

/**
 * Tells whether npm serves a package, whatever its versions.
 *
 * @throws If the registry is unreachable, times out after 30 seconds, or answers anything but 200 or 404.
 *
 * @example
 * await isOnNpm('@symfony/ux-vue'); // true
 */
export async function isOnNpm(name: string): Promise<boolean> {
    const url = `${NPM_REGISTRY}/${encodeURIComponent(name)}`;
    const response = await fetch(url, { method: 'HEAD', signal: AbortSignal.timeout(REGISTRY_TIMEOUT_MS) });

    if (404 === response.status) {
        return false;
    }

    if (!response.ok) {
        throw new Error(`npm answered ${response.status} for ${name}.`);
    }

    return true;
}

/**
 * Keeps the packages npm does not have yet.
 *
 * The Release on NPM workflow publishes through trusted publishing, which npm only allows on a package that already exists.
 *
 * @param isPublished Tells whether npm serves a package. Injected so the decision stays testable.
 */
export async function unpublishedPackages(
    packages: NpmPackage[],
    isPublished: (name: string) => Promise<boolean>
): Promise<NpmPackage[]> {
    const published = await Promise.all(packages.map((npmPackage) => isPublished(npmPackage.name)));

    return packages.filter((_, index) => !published[index]);
}

/**
 * Refuses a release that leaves out a package npm does not have yet.
 *
 * `pnpm publish --recursive` would still try to publish it at the version it carries, and fail.
 * This happens when split.sh does not split the package yet.
 *
 * @param unpublished The packages npm does not have yet.
 * @param directories The directories the release bumps.
 * @throws If one of the packages is not bumped.
 */
export function assertBumped(unpublished: NpmPackage[], directories: string[]): void {
    const forgotten = unpublished.filter(({ directory }) => !directories.includes(directory));

    if (forgotten.length > 0) {
        const names = forgotten.map(({ name }) => name).join(', ');

        throw new Error(
            `npm does not have ${names} yet, and this release does not bump it, so the Release on NPM workflow would fail to publish it. Check that split.sh splits it, or make it private until it does.`
        );
    }
}

/**
 * Explains how to publish by hand the first version of the packages npm does not have yet.
 *
 * @param unpublished The packages npm does not have yet.
 * @returns The steps, or an empty string when there is none.
 */
export function firstPublishSteps(unpublished: NpmPackage[]): string {
    if (0 === unpublished.length) {
        return '';
    }

    const publishes = unpublished.map(
        ({ name }) => `    pnpm --filter ${name} publish --access public --no-git-checks`
    );
    const settings = unpublished.map(({ name }) => `    https://www.npmjs.com/package/${name}/access`);

    return [
        'npm does not have these packages yet, and the Release on NPM workflow cannot create them. Before you push, publish their first version by hand:',
        '',
        ...publishes,
        '',
        'Then set up OIDC on npm. On each page below, add a trusted publisher: GitHub Actions, organization "symfony", repository "ux", workflow "release-on-npm.yaml".',
        '',
        ...settings,
        '',
    ].join('\n');
}

if (process.argv[1] && import.meta.filename === path.resolve(process.argv[1])) {
    try {
        const { values } = parseArgs({
            options: { branch: { type: 'string' }, version: { type: 'string' }, 'first-publish': { type: 'boolean' } },
        });
        const config = JSON.parse(fs.readFileSync('splitsh.json', 'utf8'));
        const unpublished = await unpublishedPackages(publishablePackages(config), isOnNpm);

        if (values['first-publish']) {
            process.stdout.write(firstPublishSteps(unpublished));
        } else {
            const prediction = await fetchPrediction(values.branch ?? '', values.version ?? '');
            const directories = bumpDirectories(prediction, config, hasVersionToBump);

            assertBumped(unpublished, directories);
            process.stdout.write(directories.map((directory) => `${directory}\n`).join(''));
        }
    } catch (error) {
        console.error(error instanceof Error ? error.message : error);
        process.exit(1);
    }
}
