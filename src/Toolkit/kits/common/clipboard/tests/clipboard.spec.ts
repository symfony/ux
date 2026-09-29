import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const grantClipboard = (page: Page) => page.context().grantPermissions(['clipboard-read', 'clipboard-write']);

const readClipboard = (page: Page) => page.evaluate(() => navigator.clipboard.readText());

describeRecipe('common/clipboard', () => {
    testState('shows the success target after a copy', {
        example: 'default',
        state: 'copied',
        act: async (page) => {
            await grantClipboard(page);
            // The success target hides itself after 2 seconds, the screenshot must not race it.
            await page.clock.install();

            await page.getByRole('button', { name: 'Copy' }).click();

            await expect(page.getByText('Copied!')).toBeVisible();
            await expect.poll(() => readClipboard(page)).toBe('composer require symfony/ux-toolkit');
        },
    });

    testState('swaps the button label after a copy', {
        example: 'swap-the-button-label',
        state: 'copied',
        act: async (page) => {
            await grantClipboard(page);
            await page.clock.install();

            await page.getByRole('button', { name: 'Copy' }).click();

            await expect(page.getByRole('button', { name: 'Copied!' })).toBeVisible();
        },
    });

    testState('adds the success class after a copy', {
        example: 'feedback-via-a-css-class',
        state: 'copied',
        act: async (page) => {
            await grantClipboard(page);
            await page.clock.install();

            await page.getByRole('button', { name: 'Copy' }).click();

            await expect(page.getByRole('button', { name: 'Copy' })).toHaveCSS('background-color', 'rgb(22, 163, 74)');
        },
    });

    test('copies the text of the source target', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/copy-an-element-s-text');

        await page.getByRole('button', { name: 'Copy text' }).click();

        await expect.poll(() => readClipboard(page)).toBe('The quick brown fox jumps over the lazy dog.');
    });

    test('copies the value of a form control', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/copy-a-form-element-s-value');

        await page.getByRole('button', { name: 'Copy email' }).click();

        await expect.poll(() => readClipboard(page)).toBe('hello@example.com');
    });

    test('copies code verbatim, newlines and indentation included', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/copy-code');

        await page.getByRole('button', { name: 'Copy' }).click();

        await expect.poll(() => readClipboard(page)).toBe("$response = new JsonResponse([\n    'status' => 'ok',\n]);");
    });

    test('copies the source value instead of what is on screen', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/copy-using-a-value');

        await page.getByRole('button', { name: 'Copy API Key' }).click();

        await expect.poll(() => readClipboard(page)).toBe('a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6');
    });

    test('hides the success target again after its duration', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/copied-feedback', { timers: 'fake' });
        const success = page.getByText('Copied to clipboard!');
        await expect(success).toBeHidden();

        await page.getByRole('button', { name: 'Copy' }).click();

        await expect(success).toBeVisible();

        await page.clock.runFor(1000);

        await expect(success).toBeVisible();

        await page.clock.runFor(500);

        await expect(success).toBeHidden();
    });

    test('restores the idle label after the success duration', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/swap-the-button-label', { timers: 'fake' });
        const button = page.getByRole('button', { name: 'Copy' });

        await button.click();

        await expect(page.getByRole('button', { name: 'Copied!' })).toBeVisible();

        await page.clock.runFor(2000);

        await expect(button).toBeVisible();
        await expect(page.getByText('Copied!')).toBeHidden();
    });

    test('removes the success class after the success duration', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/feedback-via-a-css-class', { timers: 'fake' });
        const button = page.getByRole('button', { name: 'Copy' });
        const idleColor = await button.evaluate((element) => getComputedStyle(element).backgroundColor);

        await button.press('Enter');

        await expect(button).toHaveCSS('background-color', 'rgb(22, 163, 74)');

        await page.clock.runFor(2000);

        await expect(button).toHaveCSS('background-color', idleColor);
    });

    test('dispatches clipboard:copied to open a tooltip on the button', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('common/clipboard/feedback-with-a-tooltip');
        const button = page.getByRole('button', { name: 'Copy' });

        await button.click();

        await expect(page.getByRole('tooltip')).toHaveText('Copied!');
        await expect(button).toHaveAccessibleDescription('Copied!');
        await expect.poll(() => readClipboard(page)).toBe('php bin/console debug:router');
    });
});
