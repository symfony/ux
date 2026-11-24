import { execFileSync } from 'node:child_process';
import * as fs from 'node:fs';
import * as os from 'node:os';
import * as path from 'node:path';
import { afterAll, beforeAll, describe, expect, test } from 'vitest';
import { assertNextVersion, compareVersions, parseVersion, previousVersions } from './release_version.ts';

test.each([
    ['2.36.4', ['2.36.3']],
    ['2.37.0', ['2.36.3']],
    ['2.37.0-rc1', ['2.36.3']],
    ['2.37.0-rc2', ['2.36.3', '2.37.0-rc1']],
    ['2.37.0', ['2.36.3', '2.37.0-rc1']],
    ['4.0.0', ['3.8.1']],
    ['4.0.0-beta1', []],
    ['2.36.4', ['2.36.3', '2.36.4']],
    ['2.36.4', ['2.36.3', '3.8.1', '2.x', '2.36.3-']],
])('accepts %s after %j', (version, previous) => {
    const act = () => assertNextVersion(version, previous);

    expect(act).not.toThrow();
});

test.each([
    ['2.26.3', ['2.36.3']],
    ['2.36.2', ['2.36.3']],
    ['2.36.5', ['2.36.3']],
    ['2.38.0', ['2.36.3']],
    ['2.37.0-beta1', ['2.36.3', '2.37.0-rc1']],
    ['2.36.4', ['2.36.3', '2.37.0-rc1']],
    ['4.1.0', ['3.8.1']],
])('refuses %s after %j', (version, previous) => {
    const act = () => assertNextVersion(version, previous);

    expect(act).toThrow();
});

test('names the previous tag and the versions that would follow it', () => {
    const act = () => assertNextVersion('2.26.3', ['2.36.2', '2.36.3']);

    expect(act).toThrow('v2.36.3');
    expect(act).toThrow('2.36.4');
    expect(act).toThrow('2.37.0');
});

test('refuses a version release.sh would not accept either', () => {
    const act = () => assertNextVersion('2.36', ['2.36.3']);

    expect(act).toThrow('2.36');
});

test.each([
    ['2.36.9', '2.36.10'],
    ['2.37.0-rc.2', '2.37.0-rc.10'],
    ['2.37.0-beta1', '2.37.0-rc1'],
    ['2.37.0-rc1', '2.37.0'],
    ['2.37.0-1', '2.37.0-alpha'],
    ['2.37.0-rc', '2.37.0-rc.1'],
])('orders %s before %s', (lower, higher) => {
    const order = compareVersions(parseVersion(lower)!, parseVersion(higher)!);

    expect(order).toBeLessThan(0);
});

test.each(['2.36', 'v2.36.3', '2.36.3-', '2.x'])('does not read %s as a version', (input) => {
    const version = parseVersion(input);

    expect(version).toBeNull();
});

describe('previousVersions', () => {
    let root: string;
    let remote: string;

    const git = (...args: string[]) =>
        execFileSync('git', ['-c', 'user.name=test', '-c', 'user.email=test@example.com', ...args], {
            cwd: remote,
            encoding: 'utf8',
            env: { ...process.env, GIT_CONFIG_GLOBAL: '/dev/null', GIT_CONFIG_NOSYSTEM: '1' },
        });

    beforeAll(() => {
        root = fs.mkdtempSync(path.join(os.tmpdir(), 'release-version-'));
        remote = path.join(root, 'remote');
        fs.mkdirSync(remote);
        git('init', '--quiet');
        git('commit', '--allow-empty', '--quiet', '-m', 'first');
        git('tag', 'v2.0.0');
        git('commit', '--allow-empty', '--quiet', '-m', 'second');
        git('tag', '-a', 'v2.0.1', '-m', 'v2.0.1');
        git('tag', 'v3.0.0');
        git('tag', 'v20.0.0');
    });

    afterAll(() => {
        fs.rmSync(root, { recursive: true, force: true });
    });

    test('lists the versions the remote tagged in the major, whether the tags are annotated or not', () => {
        const versions = previousVersions(remote, 2);

        expect(versions.sort()).toEqual(['2.0.0', '2.0.1']);
    });

    test('refuses a remote it cannot list rather than finding no previous release', () => {
        const act = () => previousVersions(path.join(root, 'missing'), 2);

        expect(act).toThrow();
    });
});
