import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sonner', () => {
    testState('shows a toast on click', {
        example: 'default',
        state: 'toast',
        act: async (page) => {
            await page.clock.install();
            await page.clock.pauseAt(Date.now() + 60_000);

            await page.getByRole('button', { name: 'Default' }).click();
            await page.mouse.move(0, 0);
            await page.clock.runFor(100);

            const toast = page.getByRole('status').filter({ hasText: 'Event has been created' });
            await expect(toast).toHaveAttribute('data-state', 'open');
            await expect(toast).toContainText('Sunday, December 03, 2023 at 9:00 AM');
        },
    });

    testState('expands the stack on hover', {
        example: 'default',
        state: 'expanded',
        act: async (page) => {
            await page.clock.install();
            await page.clock.pauseAt(Date.now() + 60_000);
            await page.getByRole('button', { name: 'Success' }).click();
            await page.getByRole('button', { name: 'Info' }).click();
            await page.mouse.move(0, 0);
            await page.clock.runFor(100);

            await page.getByRole('status').filter({ hasText: 'New update available' }).hover();

            await expect(page.getByRole('status').filter({ hasText: 'Profile updated successfully' })).toHaveCSS(
                'opacity',
                '1'
            );
        },
    });

    testState('colors a toast by its type', {
        example: 'rich-colors',
        state: 'error',
        act: async (page) => {
            await page.clock.install();
            await page.clock.pauseAt(Date.now() + 60_000);

            await page.getByRole('button', { name: 'Error' }).click();
            await page.mouse.move(0, 0);
            await page.clock.runFor(100);

            await expect(page.getByRole('alert')).toHaveAttribute('data-state', 'open');
            await expect(page.getByRole('alert')).toContainText('Something went wrong');
        },
    });

    test('announces an error as an alert and other types as a status', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/types', { timers: 'fake' });

        await page.getByRole('button', { name: 'Error' }).click();
        await page.getByRole('button', { name: 'Success' }).click();

        await expect(page.getByRole('alert')).toHaveText('Failed to save changes');
        await expect(page.getByRole('alert')).toHaveAttribute('aria-live', 'assertive');
        await expect(page.getByRole('status')).toHaveText('Changes saved');
        await expect(page.getByRole('status')).toHaveAttribute('aria-live', 'polite');
    });

    test('stacks the newest toast on top', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/types', { timers: 'fake' });

        await page.getByRole('button', { name: 'Default' }).click();
        await page.getByRole('button', { name: 'Success' }).click();
        await page.getByRole('button', { name: 'Info' }).click();

        await expect(page.getByRole('status')).toHaveText([
            'New message received',
            'Changes saved',
            'Default notification',
        ]);
    });

    test('disappears after its duration', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/with-description', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Sonner' }).click();
        const toast = page.getByRole('status');
        await expect(toast).toBeVisible();

        await page.clock.runFor(3000);

        await expect(toast).toBeVisible();

        await page.clock.runFor(2000);

        await expect(toast).toBeHidden();
    });

    test('keeps a loading toast until it is dismissed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/types', { timers: 'fake' });
        await page.getByRole('button', { name: 'Loading' }).click();

        await page.clock.runFor(10_000);

        await expect(page.getByRole('status')).toHaveText('Uploading file…');
    });

    test('pauses the countdown while hovered', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/default', { timers: 'fake' });
        await page.getByRole('button', { name: 'Default' }).click();
        const toast = page.getByRole('status');
        await toast.hover();

        await page.clock.runFor(6000);

        await expect(toast).toBeVisible();

        await page.mouse.move(0, 0);
        await page.clock.runFor(5000);

        await expect(toast).toBeHidden();
    });

    test('closes with its close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/default', { timers: 'fake' });
        await page.getByRole('button', { name: 'Default' }).click();
        const toast = page.getByRole('status');
        await toast.hover();

        await toast.getByRole('button', { name: 'Close notification' }).click();
        await page.clock.runFor(1000);

        await expect(toast).toBeHidden();
    });

    test('closes on a swipe to the right', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/with-description', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Sonner' }).click();
        const toast = page.getByRole('status');
        const box = await toast.boundingBox();
        expect(box).not.toBeNull();

        await page.mouse.move(box!.x + 20, box!.y + box!.height / 2);
        await page.mouse.down();
        await page.mouse.move(box!.x + 140, box!.y + box!.height / 2, { steps: 5 });
        await page.mouse.up();
        await page.clock.runFor(1000);

        await expect(toast).toBeHidden();
    });

    test('follows the action link on click', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/with-action', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Sonner' }).click();

        await page.getByRole('status').getByRole('link', { name: 'Undo' }).click();

        await expect(page).toHaveURL(/#$/);
    });

    test('hydrates server-rendered toasts', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/server-rendered', { timers: 'fake' });
        const saved = page.getByRole('status').filter({ hasText: 'Profile saved' });
        await expect(saved).toHaveAttribute('data-state', 'open');
        await expect(page.getByRole('status').filter({ hasText: 'Welcome back, Alice!' })).toBeVisible();

        await saved.hover();
        await saved.getByRole('button', { name: 'Close notification' }).click();
        await page.clock.runFor(1000);

        await expect(saved).toBeHidden();
        await expect(page.getByRole('status').filter({ hasText: 'Welcome back, Alice!' })).toBeVisible();
    });
});
