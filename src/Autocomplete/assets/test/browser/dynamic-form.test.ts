import { expect, type Page, test } from '@playwright/test';

async function typeInTomSelect(page: Page, testId: string, text: string) {
    const wrapper = page.locator(`[data-test-id="${testId}"]`).locator('..');
    const tsControl = wrapper.locator('.ts-control');
    await tsControl.waitFor({ state: 'visible', timeout: 10000 });
    await tsControl.click();
    await tsControl.locator('input').fill(text);
}

async function waitForAutocomplete(page: Page, testId: string) {
    const element = page.locator(`[data-test-id="${testId}"]`);
    await element.waitFor({ state: 'attached', timeout: 10000 });
    const wrapper = element.locator('..');
    await wrapper.locator('.ts-control').waitFor({ state: 'visible', timeout: 10000 });
}

test.describe('Autocomplete with Dynamic Forms', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/test/autocomplete-dynamic-form');
        await expect(page.locator('[data-test-id="test-page"]')).toBeVisible();
    });

    test('should not throw "Tom Select already initialized" error when switching between dynamic autocomplete fields', async ({
        page,
    }) => {
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        await page.selectOption('[data-test-id="production-type"]', 'movie');
        await waitForAutocomplete(page, 'movie-autocomplete');

        await typeInTomSelect(page, 'movie-autocomplete', 'Matrix');
        await page.waitForTimeout(500);

        const optionsAfterFirstFill = page.locator('[data-test-id="autocomplete-option"]');
        if ((await optionsAfterFirstFill.count()) > 0) {
            await optionsAfterFirstFill.first().click();
            await page.waitForTimeout(1000);
        }

        await page.selectOption('[data-test-id="production-type"]', 'videogame');
        await waitForAutocomplete(page, 'videogame-autocomplete');

        await typeInTomSelect(page, 'videogame-autocomplete', 'Halo');
        await page.waitForTimeout(500);

        const optionsAfterSecondFill = page.locator('[data-test-id="autocomplete-option"]');
        if ((await optionsAfterSecondFill.count()) > 0) {
            await optionsAfterSecondFill.first().click();
        }

        await page.selectOption('[data-test-id="production-type"]', 'movie');
        await waitForAutocomplete(page, 'movie-autocomplete');

        await typeInTomSelect(page, 'movie-autocomplete', 'Inception');
        await page.waitForTimeout(500);

        const tomSelectError = consoleErrors.find((error) => error.includes('Tom Select already initialized'));

        expect(tomSelectError).toBeUndefined();

        await expect(page.locator('[data-test-id="autocomplete-option"]')).toHaveCount(1);
    });

    test('should properly disconnect and reconnect Tom Select on rapid type changes', async ({ page }) => {
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        for (let i = 0; i < 5; i++) {
            await page.selectOption('[data-test-id="production-type"]', 'movie');
            await page.waitForTimeout(100);
            await page.selectOption('[data-test-id="production-type"]', 'videogame');
            await page.waitForTimeout(100);
        }

        await page.selectOption('[data-test-id="production-type"]', 'movie');
        await waitForAutocomplete(page, 'movie-autocomplete');

        await typeInTomSelect(page, 'movie-autocomplete', 'Test');

        expect(consoleErrors).toHaveLength(0);
    });

    test('should handle autocomplete in morphed LiveComponent without errors', async ({ page }) => {
        const consoleErrors: string[] = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') {
                consoleErrors.push(msg.text());
            }
        });

        await page.selectOption('[data-test-id="production-type"]', 'movie');
        await waitForAutocomplete(page, 'movie-autocomplete');

        await typeInTomSelect(page, 'movie-autocomplete', 'Matrix');
        await page.waitForTimeout(500);

        const dropdown = page.locator('.ts-dropdown');
        await dropdown.waitFor({ state: 'visible', timeout: 5000 });

        const firstOption = dropdown.locator('.option').first();
        if (await firstOption.isVisible()) {
            await firstOption.click();
        }

        await page.waitForTimeout(1000);

        await typeInTomSelect(page, 'movie-autocomplete', 'Inception');
        await page.waitForTimeout(1000);

        expect(consoleErrors).toHaveLength(0);

        const dropdownVisible = await dropdown.isVisible();
        expect(dropdownVisible).toBe(true);
    });
});
