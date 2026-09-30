import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';

const kitsDir = fileURLToPath(new URL('../../../kits', import.meta.url));

const listDirs = (dir: string): string[] =>
    readdirSync(dir, { withFileTypes: true })
        .filter((entry) => entry.isDirectory())
        .map((entry) => entry.name);

const hasController = (kit: string, recipe: string): boolean =>
    existsSync(join(kitsDir, kit, recipe, 'assets/controllers'));

const readRecipeDependencies = (kit: string, recipe: string): string[] => {
    const manifest = JSON.parse(readFileSync(join(kitsDir, kit, recipe, 'manifest.json'), 'utf8'));

    return manifest.dependencies?.recipe ?? [];
};

const listTextFiles = (dir: string): string[] =>
    readdirSync(dir, { recursive: true, withFileTypes: true })
        .filter((entry) => entry.isFile() && /\.(md|twig)$/.test(entry.name))
        .map((entry) => join(entry.parentPath, entry.name));

const usesBootstrapJs = (kit: string, recipe: string): boolean =>
    listTextFiles(join(kitsDir, kit, recipe)).some((file) =>
        /data-bs-(toggle|dismiss|ride|slide)/.test(readFileSync(file, 'utf8'))
    );

const isInteractive = (kit: string, recipe: string): boolean =>
    hasController(kit, recipe) ||
    readRecipeDependencies(kit, recipe).some((dependency) => hasController(kit, dependency)) ||
    usesBootstrapJs(kit, recipe);

const hasSpec = (kit: string, recipe: string): boolean => {
    const testsDir = join(kitsDir, kit, recipe, 'tests');

    return existsSync(testsDir) && readdirSync(testsDir).some((file) => file.endsWith('.spec.ts'));
};

test('every interactive recipe has an interaction spec', () => {
    const recipesWithoutSpec: string[] = [];
    for (const kit of listDirs(kitsDir)) {
        for (const recipe of listDirs(join(kitsDir, kit))) {
            const name = `${kit}/${recipe}`;
            if (isInteractive(kit, recipe) && !hasSpec(kit, recipe)) {
                recipesWithoutSpec.push(name);
            }
        }
    }

    expect(recipesWithoutSpec).toEqual([]);
});
