import * as fs from 'node:fs';
import { expect, test } from 'vitest';
import { parse } from 'yaml';
import { toolingDependencies, zeroMinorBumps } from './update_tooling_dependencies.ts';

test('toolingDependencies() reads the allow list of the npm entry', () => {
    const config = {
        updates: [
            { 'package-ecosystem': 'github-actions', directory: '/' },
            {
                'package-ecosystem': 'npm',
                allow: [{ 'dependency-name': 'oxfmt' }, { 'dependency-name': '@testing-library/*' }],
            },
        ],
    };

    expect(toolingDependencies(config)).toEqual(['oxfmt', '@testing-library/*']);
});

test('toolingDependencies() refuses a config without an npm entry', () => {
    const config = { updates: [{ 'package-ecosystem': 'github-actions', directory: '/' }] };

    expect(() => toolingDependencies(config)).toThrow('no npm entry');
});

test('toolingDependencies() reads the Dependabot config of the repository', () => {
    const config = parse(fs.readFileSync('.github/dependabot.yml', 'utf8'));

    const dependencies = toolingDependencies(config);

    expect(dependencies).toContain('oxfmt');
    expect(dependencies).not.toContain('idiomorph');
});

test('zeroMinorBumps() keeps the 0.x dependencies whose latest version is still 0.x', () => {
    const report = {
        oxfmt: { current: '0.41.0', latest: '0.72.0' },
        tinyglobby: { current: '0.2.17', latest: '0.2.17' },
        'vitest-canvas-mock': { current: '0.3.3', latest: '1.2.0' },
        jsdom: { current: '26.1.0', latest: '30.1.2' },
    };

    expect(zeroMinorBumps(report)).toEqual(['oxfmt']);
});
