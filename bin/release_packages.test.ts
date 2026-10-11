import * as fs from 'node:fs';
import { globSync } from 'tinyglobby';
import { expect, test } from 'vitest';
import {
    assertBumped,
    bumpDirectories,
    firstPublishSteps,
    hasVersionToBump,
    prefixOf,
    publishablePackages,
    unpublishedPackages,
} from './release_packages.ts';

const CONFIG = {
    subtrees: {
        'ux-vue': 'src/Vue',
        'ux-map': { prefixes: [{ from: 'src/Map', to: '', excludes: ['src/Bridge'] }] },
        'ux-twig-component': 'src/TwigComponent',
    },
};

test('reads the prefix of a subtree in both the string and the prefixes form', () => {
    expect(prefixOf(CONFIG, 'ux-vue')).toBe('src/Vue');
    expect(prefixOf(CONFIG, 'ux-map')).toBe('src/Map');
});

test('refuses a subtree splitsh.json does not declare, whose package would silently never be bumped', () => {
    expect(() => prefixOf(CONFIG, 'ux-brand-new')).toThrow('ux-brand-new');
});

test('refuses a subtree declaring several prefixes, because it could not be mapped back to one package', () => {
    const config = { subtrees: { 'ux-thing': { prefixes: [{ from: 'src/A' }, { from: 'src/B' }] } } };

    expect(() => prefixOf(config, 'ux-thing')).toThrow('ux-thing');
});

test('turns the repositories split.sh will tag into the directories to bump', () => {
    const directories = bumpDirectories({ tagged: ['ux-map', 'ux-vue'] }, CONFIG, () => true);

    expect(directories).toEqual(['src/Map/assets', 'src/Vue/assets']);
});

test('bumps a private package like any other, since its manifest ships in its split repository', () => {
    expect(hasVersionToBump('src/Map/assets')).toBe(true);
    expect(hasVersionToBump('src/StimulusBundle/assets')).toBe(true);
});

test('has nothing to bump where the assets ship no manifest at all', () => {
    expect(hasVersionToBump('src/Toolkit/assets')).toBe(false);
    expect(hasVersionToBump('src/TwigComponent/assets')).toBe(false);
});

test('has nothing to bump in a manifest that carries no version field', () => {
    expect(hasVersionToBump('src/CalendarLink/assets')).toBe(false);
});

test('leaves out a tagged subtree that has nothing to bump, which only gets its tag', () => {
    const directories = bumpDirectories(
        { tagged: ['ux-twig-component'] },
        CONFIG,
        (directory) => 'src/Vue/assets' === directory
    );

    expect(directories).toEqual([]);
});

test('refuses a release split.sh would tag nowhere, which has nothing to release', () => {
    expect(() => bumpDirectories({ tagged: [] }, CONFIG, () => true)).toThrow(/nothing to release/);
});

test('every package this repository publishes belongs to a subtree splitsh.json declares', () => {
    const config = JSON.parse(fs.readFileSync('splitsh.json', 'utf8'));
    const declared = new Set(
        Object.keys(config.subtrees).map((split) => `${prefixOf(config, split)}/assets/package.json`)
    );

    const orphans = globSync(['src/*/assets/package.json', 'src/*/src/Bridge/*/assets/package.json'])
        .filter((file) => !declared.has(file))
        .filter((file) => true !== JSON.parse(fs.readFileSync(file, 'utf8')).private);

    expect(orphans).toEqual([]);
});

const VUE = { directory: 'src/Vue/assets', name: '@symfony/ux-vue' };
const ROUTER = { directory: 'src/Router/assets', name: '@symfony/ux-router' };
const BRAND_NEW = { directory: 'src/BrandNew/assets', name: '@symfony/ux-brand-new' };

test('publishes every versioned manifest that is not private', () => {
    expect(publishablePackages(CONFIG)).toEqual([VUE]);
});

test('keeps the packages npm does not serve at all', async () => {
    const unpublished = await unpublishedPackages([VUE, ROUTER], async (name) => '@symfony/ux-vue' === name);

    expect(unpublished).toEqual([ROUTER]);
});

test('refuses a release that leaves out a package npm does not have yet, which the workflow would fail to publish', () => {
    expect(() => assertBumped([ROUTER], ['src/Vue/assets'])).toThrow('@symfony/ux-router');
});

test('accepts a release that bumps every package npm does not have yet', () => {
    expect(() => assertBumped([ROUTER], ['src/Vue/assets', 'src/Router/assets'])).not.toThrow();
});

test('lists one publish command and one trusted publisher page per package npm does not have yet', () => {
    const lines = firstPublishSteps([ROUTER, BRAND_NEW]).split('\n');

    expect(lines).toContain('    pnpm --filter @symfony/ux-router publish --access public --no-git-checks');
    expect(lines).toContain('    pnpm --filter @symfony/ux-brand-new publish --access public --no-git-checks');
    expect(lines).toContain('    https://www.npmjs.com/package/@symfony/ux-router/access');
    expect(lines).toContain('    https://www.npmjs.com/package/@symfony/ux-brand-new/access');
});

test('has no step to list when npm already has every package', () => {
    expect(firstPublishSteps([])).toBe('');
});
