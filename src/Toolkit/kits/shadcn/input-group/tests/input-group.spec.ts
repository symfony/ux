import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const BLUE_600 = 'oklch(0.546 0.245 262.881)';

const grantClipboard = (page: Page) => page.context().grantPermissions(['clipboard-read', 'clipboard-write']);

const readClipboard = (page: Page) => page.evaluate(() => navigator.clipboard.readText());

describeRecipe('shadcn/input-group', () => {
    testState('shows a check once the URL is copied', {
        example: 'button',
        state: 'copied',
        act: async (page) => {
            await grantClipboard(page);
            // The copy icon comes back after 2 seconds, the screenshot must not race it.
            await page.clock.install();
            const copy = page.getByRole('button', { name: 'Copy' });

            await copy.click();
            await page.mouse.move(0, 0);

            await expect(copy.locator('[data-input-group-display-target="copiedIcon"]')).toBeVisible();
            await expect(copy.locator('[data-input-group-display-target="copyIcon"]')).toBeHidden();
            await expect.poll(() => readClipboard(page)).toBe('https://x.com/symfony');
        },
    });

    testState('fills the star once marked as favorite', {
        example: 'button',
        state: 'favorite',
        act: async (page) => {
            const favorite = page.getByRole('button', { name: 'Favorite' });

            await favorite.click();
            await page.mouse.move(0, 0);

            await expect(favorite.locator('path')).toHaveCSS('fill', BLUE_600);
            await expect(favorite.locator('path')).toHaveCSS('stroke', BLUE_600);
        },
    });

    testState('opens the connection info', {
        example: 'button',
        state: 'info-open',
        act: async (page) => {
            const info = page.getByRole('button', { name: 'Connection info' });

            await info.click();

            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(page.getByRole('dialog')).toHaveCSS('border-radius', '14px');
            await expect(info).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('shows the copy icon again after two seconds', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('shadcn/input-group/button', { timers: 'fake' });
        const copy = page.getByRole('button', { name: 'Copy' });
        const copied = copy.locator('[data-input-group-display-target="copiedIcon"]');
        await copy.click();
        await expect(copied).toBeVisible();

        await page.clock.runFor(2000);

        await expect(copied).toBeHidden();
        await expect(copy.locator('[data-input-group-display-target="copyIcon"]')).toBeVisible();
    });

    test('shows the connection info button before the https:// prefix', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-group/button');

        const info = await page.getByRole('button', { name: 'Connection info' }).boundingBox();
        const prefix = await page.getByText('https://', { exact: true }).boundingBox();

        expect(info?.x).toBeLessThan(prefix?.x ?? 0);
    });

    test('focuses the input when an addon is clicked', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-group/default');

        await page.getByText('12 results').click();

        await expect(page.getByPlaceholder('Search...')).toBeFocused();
    });

    test('leaves the focus on a button clicked inside an addon', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-group/button');
        const favorite = page.getByRole('button', { name: 'Favorite' });

        await favorite.click();

        await expect(favorite).toBeFocused();
    });
});
