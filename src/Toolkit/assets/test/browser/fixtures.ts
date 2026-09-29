import { fileURLToPath } from 'node:url';
import { expect, test as base, type Page } from '@playwright/test';

export type Theme = 'light' | 'dark';

export const themes: Theme[] = ['light', 'dark'];

type GotoExampleOptions = {
    theme?: Theme;
    timers?: 'real' | 'fake';
};

type Fixtures = {
    gotoExample: (path: string, options?: GotoExampleOptions) => Promise<void>;
    failOnPageErrors: void;
    blockExternalRequests: void;
};

type StateOptions = {
    example: string;
    state: string;
    act: (page: Page) => Promise<void>;
};

/**
 * Declares the screenshot a test compares, so `bin/update_toolkit_tests.sh` can list them and remove the others.
 */
export const screenshotAnnotation = (name: string[]) => ({
    annotation: { type: 'screenshot', description: name.join('/') },
});

const FIXED_TIME = new Date('2026-03-15T10:00:00Z');
const PLACEHOLDER_IMAGE = fileURLToPath(new URL('./placeholder.png', import.meta.url));

export const test = base.extend<Fixtures>({
    // Remote images change and load at their own pace: screenshots show a local placeholder instead.
    blockExternalRequests: [
        async ({ page, baseURL }, use) => {
            await page.route(
                (url) => !url.href.startsWith(`${baseURL}/`),
                (route) => {
                    if ('image' === route.request().resourceType()) {
                        return route.fulfill({ path: PLACEHOLDER_IMAGE });
                    }

                    return route.abort();
                }
            );

            await use();
        },
        { auto: true },
    ],

    failOnPageErrors: [
        async ({ page, baseURL }, use) => {
            const errors: string[] = [];
            const isLocal = (url: string) => url.startsWith(`${baseURL}/`);

            page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
            page.on('console', (message) => {
                const url = message.location().url;
                if ('error' !== message.type() || (url && !isLocal(url))) {
                    return;
                }
                errors.push(`console.error: ${message.text()}`);
            });
            page.on('response', (response) => {
                if (response.status() >= 400 && isLocal(response.url())) {
                    errors.push(`HTTP ${response.status()}: ${response.url()}`);
                }
            });

            await use();

            expect(errors).toEqual([]);
        },
        { auto: true },
    ],

    gotoExample: async ({ page }, use) => {
        await use(async (path, { theme = 'light', timers = 'real' } = {}) => {
            await page.emulateMedia({ colorScheme: theme });
            // Any Playwright clock fakes requestAnimationFrame, which then fires without a real render.
            if ('fake' === timers) {
                await page.clock.install({ time: FIXED_TIME });
            }

            await page.goto(`/${path}?theme=${theme}`);
            await page.evaluate(async () => {
                await document.fonts.ready;
            });

            if ('fake' === timers) {
                await page.clock.runFor(1000);
            }
        });
    },
});

let currentRecipe: string | null = null;

/**
 * Groups the tests of a recipe spec, so `--grep <kit>/<recipe>` selects them and `testState()` knows the recipe.
 */
export function describeRecipe(recipe: string, callback: () => void): void {
    test.describe(recipe, () => {
        currentRecipe = recipe;
        try {
            callback();
        } finally {
            currentRecipe = null;
        }
    });
}

/**
 * Screenshots a state that only an interaction reaches, in every theme.
 *
 * It opens `example` of the recipe, runs `act` (the interaction and its assertions),
 * then compares the page with `kits/<kit>/<recipe>/tests/screenshots/<example>-<state>-<theme>.png`.
 */
export function testState(title: string, { example, state, act }: StateOptions): void {
    const recipe = currentRecipe;
    if (null === recipe) {
        throw new Error('testState() must be called inside describeRecipe().');
    }

    for (const theme of themes) {
        const name = [...recipe.split('/'), 'tests', 'screenshots', `${example}-${state}-${theme}.png`];

        test(`${title} (${theme})`, screenshotAnnotation(name), async ({ page, gotoExample }) => {
            await gotoExample(`${recipe}/${example}`, { theme });
            await act(page);

            await expect(page).toHaveScreenshot(name, { fullPage: true });
        });
    }
}

export { expect };
