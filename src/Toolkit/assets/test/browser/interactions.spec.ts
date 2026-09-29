import { existsSync, readdirSync, readFileSync } from 'node:fs';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';

// Interactive recipes allowed to have no spec yet.
const WITHOUT_SPEC = [
    'bootstrap/accordion',
    'bootstrap/alert',
    'bootstrap/button-group',
    'bootstrap/button',
    'bootstrap/carousel',
    'bootstrap/collapse',
    'bootstrap/dropdown',
    'bootstrap/list-group',
    'bootstrap/modal',
    'bootstrap/navbar',
    'bootstrap/navs-tabs',
    'bootstrap/offcanvas',
    'bootstrap/popover',
    'bootstrap/toast',
    'bootstrap/tooltip',
    'common/clipboard',
    'common/closeable',
    'common/tooltip',
    'flowbite-4/alert',
    'flowbite-4/modal',
    'flowbite-4/tabs',
    'shadcn/accordion',
    'shadcn/alert-dialog',
    'shadcn/calendar',
    'shadcn/carousel',
    'shadcn/collapsible',
    'shadcn/combobox',
    'shadcn/date-picker',
    'shadcn/dialog',
    'shadcn/drawer',
    'shadcn/dropdown-menu',
    'shadcn/hover-card',
    'shadcn/input-otp',
    'shadcn/menubar',
    'shadcn/navigation-menu',
    'shadcn/questionnaire',
    'shadcn/resizable',
    'shadcn/sheet',
    'shadcn/sidebar',
    'shadcn/slider',
    'shadcn/sonner',
    'shadcn/tabs',
    'shadcn/toggle-group',
    'shadcn/toggle',
    'shadcn/tooltip',
];

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
            if (isInteractive(kit, recipe) && !hasSpec(kit, recipe) && !WITHOUT_SPEC.includes(name)) {
                recipesWithoutSpec.push(name);
            }
        }
    }

    expect(recipesWithoutSpec).toEqual([]);
});
