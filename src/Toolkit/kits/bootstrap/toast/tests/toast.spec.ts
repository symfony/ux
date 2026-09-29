import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/toast', () => {
    testState('shows the toast from its trigger', {
        example: 'live-example',
        state: 'shown',
        act: async (page) => {
            // Keeps the toast from hiding itself before the screenshot.
            await page.clock.install();

            await page.getByRole('button', { name: 'Show live toast' }).click();

            await expect(page.getByRole('status')).toBeVisible();
            await expect(page.getByRole('status')).toContainText('See? Just like this.');
            await expect(page.getByRole('status')).not.toHaveClass(/\bshowing\b/);
        },
    });

    test('keeps the live toast hidden until its trigger is clicked', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/toast/live-example');

        await expect(page.getByRole('status')).toBeHidden();
    });

    test('hides the live toast after its delay', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/toast/live-example', { timers: 'fake' });
        const toast = page.getByRole('status');
        await page.getByRole('button', { name: 'Show live toast' }).click();
        await expect(toast).toBeVisible();
        // The toast shows up under the pointer, and hovering it pauses the delay.
        await page.mouse.move(0, 0);
        await expect(toast).not.toHaveClass(/\bshowing\b/);

        await page.clock.runFor(3000);

        await expect(toast).toBeVisible();

        await page.clock.runFor(3000);

        await expect(toast).toBeHidden();
    });

    test('stays visible when autohide is disabled', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/toast/default', { timers: 'fake' });

        await page.clock.runFor(10_000);

        await expect(page.getByRole('status')).toBeVisible();
    });

    test('dismisses with the close button of its header', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/toast/default');
        const toast = page.getByRole('status');
        await expect(toast).toBeVisible();

        await toast.getByRole('button', { name: 'Close' }).click();

        await expect(toast).toBeHidden();
    });

    test('dismisses the live toast with its close button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/toast/live-example');
        const toast = page.getByRole('status');
        await page.getByRole('button', { name: 'Show live toast' }).click();
        await expect(toast).toBeVisible();

        await toast.getByRole('button', { name: 'Close' }).click();

        await expect(toast).toBeHidden();
    });

    test('dismisses from a close button placed in the body', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/toast/custom-content');
        const toast = page.getByRole('status').filter({ has: page.getByRole('button', { name: 'Take action' }) });
        await expect(toast).toBeVisible();

        await toast.getByRole('button', { name: 'Close' }).click();

        await expect(toast).toBeHidden();
        await expect(page.getByRole('status')).toHaveCount(1);
    });
});
