/**
 * Refuses a release whose version is not the one right after the previous release of its major.
 *
 * A typo once released 2.26.3 after 2.36.2, a version that went backwards on npm. The previous releases are read from
 * the tags on `upstream`, never the local ones, which may be out of date.
 *
 * Usage: node bin/release_version.ts --version 2.36.4
 */

import { execFileSync } from 'node:child_process';
import * as path from 'node:path';
import { parseArgs } from 'node:util';

const VERSION_PATTERN = /^(\d+)\.(\d+)\.(\d+)(?:-([0-9A-Za-z.-]+))?$/;

export type Version = { major: number; minor: number; patch: number; prerelease: string[] };

type Tagged = { name: string; version: Version };

/**
 * Reads a version in the format `release.sh` accepts, without the leading `v`.
 *
 * @example
 * parseVersion('2.37.0-rc.1'); // { major: 2, minor: 37, patch: 0, prerelease: ['rc', '1'] }
 * parseVersion('v2.37.0'); // null
 */
export function parseVersion(version: string): Version | null {
    const match = VERSION_PATTERN.exec(version);

    if (null === match) {
        return null;
    }

    return {
        major: Number(match[1]),
        minor: Number(match[2]),
        patch: Number(match[3]),
        prerelease: undefined === match[4] ? [] : match[4].split('.'),
    };
}

function compareIdentifiers(a: string, b: string): number {
    const aIsNumeric = /^\d+$/.test(a);
    const bIsNumeric = /^\d+$/.test(b);

    if (aIsNumeric && bIsNumeric) {
        return Number(a) - Number(b);
    }

    if (aIsNumeric !== bIsNumeric) {
        return aIsNumeric ? -1 : 1;
    }

    return a < b ? -1 : a > b ? 1 : 0;
}

/**
 * Orders two versions by semver precedence: a release comes after its prereleases, numeric prerelease identifiers
 * compare as numbers and come before alphanumeric ones.
 *
 * @returns A negative number when `a` comes first, a positive one when `b` does, 0 when they are equal.
 *
 * @example
 * compareVersions(parseVersion('2.37.0-rc.2')!, parseVersion('2.37.0-rc.10')!); // < 0
 * compareVersions(parseVersion('2.37.0')!, parseVersion('2.37.0-rc1')!); // > 0
 */
export function compareVersions(a: Version, b: Version): number {
    const core = a.major - b.major || a.minor - b.minor || a.patch - b.patch;

    if (0 !== core) {
        return core;
    }

    if (0 === a.prerelease.length || 0 === b.prerelease.length) {
        return b.prerelease.length - a.prerelease.length;
    }

    const length = Math.min(a.prerelease.length, b.prerelease.length);

    for (let index = 0; index < length; index++) {
        const order = compareIdentifiers(a.prerelease[index], b.prerelease[index]);

        if (0 !== order) {
            return order;
        }
    }

    return a.prerelease.length - b.prerelease.length;
}

function highest(tags: Tagged[]): Tagged | null {
    return tags.reduce<Tagged | null>(
        (top, tag) => (null === top || compareVersions(tag.version, top.version) > 0 ? tag : top),
        null
    );
}

function invalidVersion(version: string): Error {
    return new Error(
        `"${version}" is not a valid version. Expected semver without leading "v", like 3.3.0 or 3.3.0-rc1.`
    );
}

/**
 * Refuses a version that is not the next patch or the next minor of the previous release of its major, or that does
 * not come after one of its prereleases. The first release of a major has to be x.0.0.
 *
 * @param version The version being released, without the leading `v`.
 * @param previous The versions already released, without the leading `v`. Other majors, the version itself and
 *                 anything that is not a version are ignored.
 * @throws If the version does not follow the previous release.
 *
 * @example
 * assertNextVersion('2.36.4', ['2.36.3', '3.8.1']); // passes, 2.37.0 would too
 * assertNextVersion('2.26.3', ['2.36.3']); // throws, naming v2.36.3, 2.36.4 and 2.37.0
 * assertNextVersion('2.37.0-rc2', ['2.36.3', '2.37.0-rc1']); // passes
 */
export function assertNextVersion(version: string, previous: string[]): void {
    const next = parseVersion(version);

    if (null === next) {
        throw invalidVersion(version);
    }

    const tags: Tagged[] = [];

    for (const name of previous) {
        const parsed = name === version ? null : parseVersion(name);

        if (null !== parsed && parsed.major === next.major) {
            tags.push({ name: `v${name}`, version: parsed });
        }
    }

    const lastRelease = highest(tags.filter(({ version }) => 0 === version.prerelease.length));
    const core = `${next.major}.${next.minor}.${next.patch}`;

    if (null === lastRelease) {
        if (`${next.major}.0.0` !== core) {
            throw new Error(`${version} would be the first ${next.major}.x release, so it must be ${next.major}.0.0.`);
        }
    } else {
        const { major, minor, patch } = lastRelease.version;
        const candidates = [`${major}.${minor}.${patch + 1}`, `${major}.${minor + 1}.0`];

        if (!candidates.includes(core)) {
            throw new Error(
                `${version} does not follow ${lastRelease.name}, the last ${major}.x release. Release ${candidates.join(' or ')} instead.`
            );
        }
    }

    const lastTag = highest(tags);

    if (null !== lastTag && compareVersions(next, lastTag.version) <= 0) {
        throw new Error(`${version} does not come after ${lastTag.name}, the last ${next.major}.x tag.`);
    }
}

/**
 * Lists the versions a remote has tagged in a major.
 *
 * @param remote A remote name or URL, such as `upstream`.
 * @param major Only tags of this major are listed.
 * @returns The versions, without the leading `v`, in no particular order.
 * @throws If the remote cannot be listed, rather than finding no previous release.
 *
 * @example
 * previousVersions('upstream', 2); // ['2.0.0', ..., '2.36.3'], without the v3 tags
 */
export function previousVersions(remote: string, major: number): string[] {
    const output = execFileSync('git', ['ls-remote', '--tags', '--refs', remote, `v${major}.*`], {
        encoding: 'utf8',
        stdio: ['ignore', 'pipe', 'pipe'],
    });
    const versions: string[] = [];

    for (const line of output.split('\n')) {
        const ref = line.split('\t')[1] ?? '';

        if (ref.startsWith('refs/tags/v')) {
            versions.push(ref.slice('refs/tags/v'.length));
        }
    }

    return versions;
}

if (process.argv[1] && import.meta.filename === path.resolve(process.argv[1])) {
    try {
        const { values } = parseArgs({ options: { version: { type: 'string' } } });
        const version = values.version ?? '';
        const next = parseVersion(version);

        if (null === next) {
            throw invalidVersion(version);
        }

        assertNextVersion(version, previousVersions('upstream', next.major));
    } catch (error) {
        console.error(error instanceof Error ? error.message : error);
        process.exit(1);
    }
}
