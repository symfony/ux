import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

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

    testState('keeps the toasts already shown after being moved in the DOM', {
        example: 'default',
        state: 'expanded-after-move',
        act: async (page) => {
            await page.clock.install();
            await page.clock.pauseAt(Date.now() + 60_000);
            await page.getByRole('button', { name: 'Default' }).click();
            await page.mouse.move(0, 0);
            await page.clock.runFor(100);
            await page.locator('[data-controller="sonner"]').evaluate(async (element) => {
                const parent = element.parentNode!;
                const next = element.nextSibling;
                element.remove();
                // The clock is paused: a microtask lets Stimulus disconnect the controller, where a timer would never fire.
                await Promise.resolve();
                parent.insertBefore(element, next);
            });
            await page.getByRole('button', { name: 'Success' }).click();
            await page.mouse.move(0, 0);
            await page.clock.runFor(100);

            await expect(page.getByRole('status')).toHaveCount(2);

            await page.getByRole('status').filter({ hasText: 'Profile updated successfully' }).hover();

            await expect(page.getByRole('status').filter({ hasText: 'Event has been created' })).toHaveCSS(
                'opacity',
                '1'
            );
        },
    });

    for (const type of ['success', 'error', 'warning', 'info']) {
        test(`paints a rich ${type} toast with an opaque background in dark mode`, async ({ page, gotoExample }) => {
            await gotoExample('shadcn/sonner/rich-colors', { theme: 'dark', timers: 'fake' });
            const toast = page.locator(`[data-slot="toast"][data-type="${type}"]`);

            await page.getByRole('button', { name: type }).click();
            await expect(toast).toBeVisible();

            const alpha = await toast.evaluate((element) => {
                const context = document.createElement('canvas').getContext('2d')!;
                context.fillStyle = getComputedStyle(element).backgroundColor;
                context.fillRect(0, 0, 1, 1);

                return context.getImageData(0, 0, 1, 1).data[3];
            });
            expect(alpha).toBe(255);
        });
    }

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

    test('still disappears after its duration once moved in the DOM', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sonner/with-description', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Sonner' }).click();
        const toast = page.getByRole('status');
        await expect(toast).toBeVisible();
        await page.locator('[data-controller="sonner"]').evaluate(async (element) => {
            const parent = element.parentNode!;
            const next = element.nextSibling;
            element.remove();
            await new Promise((resolve) => setTimeout(resolve, 50));
            parent.insertBefore(element, next);
        });

        await page.clock.runFor(5000);

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

    test('lets clicks through its empty list under a CSP blocking inline styles', async ({ page, gotoExample }) => {
        await page.route('**/shadcn/sonner/default*', async (route) => {
            const response = await route.fetch();
            await route.fulfill({
                response,
                headers: { ...response.headers(), 'content-security-policy': "style-src-attr 'none'" },
            });
        });
        await gotoExample('shadcn/sonner/default');

        await expect.poll(() => isUnderPointer(page.locator('[data-sonner-target="list"]'))).toBe(false);
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
