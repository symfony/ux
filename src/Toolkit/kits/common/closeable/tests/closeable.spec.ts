import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('common/closeable', () => {
    test('removes the message on dismiss', async ({ page, gotoExample }) => {
        await gotoExample('common/closeable/default');
        await expect(page.getByText('Heads up!')).toBeVisible();

        await page.getByRole('button', { name: 'Dismiss' }).click();

        await expect(page.getByText('Heads up!')).toHaveCount(0);
    });

    test('hides the countdown bar until a close starts', async ({ page, gotoExample }) => {
        await gotoExample('common/closeable/delayed-close');

        await expect(page.locator('[data-closeable-target="timerbar"]')).toBeHidden();
    });

    testState('shows the countdown bar during a delayed close', {
        example: 'delayed-close',
        state: 'closing',
        act: async (page) => {
            // Freezes the bar at full width before its shrinking transition starts.
            await page.clock.install();
            await page.clock.pauseAt(Date.now() + 60_000);

            await page.getByRole('button', { name: 'Dismiss' }).click();
            await page.mouse.move(0, 0);

            await expect(page.locator('[data-closeable-target="timerbar"]')).toBeVisible();
            await expect(page.getByText('Saved!')).toBeVisible();
        },
    });

    test('removes the message once the delay is over', async ({ page, gotoExample }) => {
        await gotoExample('common/closeable/delayed-close', { timers: 'fake' });

        await page.getByRole('button', { name: 'Dismiss' }).click();
        await page.clock.runFor(2000);

        await expect(page.getByText('Saved!')).toBeVisible();

        await page.clock.runFor(2000);

        await expect(page.getByText('Saved!')).toHaveCount(0);
    });

    test('closes on its own after the auto close delay', async ({ page, gotoExample }) => {
        await gotoExample('common/closeable/auto-close', { timers: 'fake' });

        await expect(page.getByText('Copied to clipboard')).toBeVisible();
        await expect(page.locator('[data-closeable-target="timerbar"]')).toBeVisible();

        await page.clock.runFor(2000);

        await expect(page.getByText('Copied to clipboard')).toBeVisible();

        await page.clock.runFor(3000);

        await expect(page.getByText('Copied to clipboard')).toHaveCount(0);
    });

    test('cancels the auto close on hover', async ({ page, gotoExample }) => {
        await gotoExample('common/closeable/cancel-auto-close', { timers: 'fake' });
        await expect(page.locator('[data-closeable-target="timerbar"]')).toBeVisible();

        await page.getByText('Hover to keep me').hover();
        await page.clock.runFor(10000);

        await expect(page.getByText('Hover to keep me')).toBeVisible();
        await expect(page.locator('[data-closeable-target="timerbar"]')).toBeHidden();
    });

    test('still closes on dismiss after the auto close was cancelled', async ({ page, gotoExample }) => {
        await gotoExample('common/closeable/cancel-auto-close', { timers: 'fake' });
        await page.getByText('Hover to keep me').hover();

        await page.getByRole('button', { name: 'Dismiss' }).click();
        await page.clock.runFor(1000);

        await expect(page.getByText('Hover to keep me')).toHaveCount(0);
    });
});
