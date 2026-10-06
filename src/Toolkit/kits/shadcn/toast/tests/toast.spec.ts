import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

// Only the toasts of type `error` carry a role, so the others are found through their slot.
const toasts = (page: Page) => page.locator('[data-slot="toast"]');

// Freezes the timers, so the toasts stay on screen for the screenshot.
async function freezeTime(page: Page): Promise<void> {
    await page.clock.install();
    await page.clock.pauseAt(Date.now() + 60_000);
}

// Lets the two animation frames of the entrance run, and moves the pointer off the toasts,
// which would otherwise expand the stack.
async function settle(page: Page): Promise<void> {
    await page.mouse.move(0, 0);
    await page.clock.runFor(100);
}

// The entrance is a CSS transition, which runs in real time whatever the clock: a toast has to reach
// its resting place before a drag can aim at it.
async function waitForEntrance(page: Page): Promise<void> {
    await page.clock.runFor(100);
    await toasts(page).evaluate((toast) => Promise.all(toast.getAnimations().map((animation) => animation.finished)));
}

async function recordClose(page: Page): Promise<void> {
    await page.evaluate(() => {
        document.addEventListener('toast:close', (event) => {
            const { id, reason } = (event as CustomEvent<{ id: string; reason: string }>).detail;
            document.body.dataset.closedToast = id;
            document.body.dataset.closeReason = reason;
        });
    });
}

describeRecipe('shadcn/toast', () => {
    testState('shows a toast on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            await freezeTime(page);

            await page.getByRole('button', { name: 'Show Toast' }).click();
            await settle(page);

            const toast = toasts(page);
            await expect(toast).toContainText('Event created');
            await expect(toast).toContainText('Sunday, December 3 at 9:00 AM');
            await expect(toast.getByRole('button', { name: 'Undo' })).toBeVisible();
            await expect(toast).not.toHaveAttribute('data-starting-style');
        },
    });

    testState('stacks the toasts behind the newest one', {
        example: 'types',
        state: 'stacked',
        act: async (page) => {
            await freezeTime(page);

            await page.getByRole('button', { name: 'Success' }).click();
            await page.getByRole('button', { name: 'Info' }).click();
            await page.getByRole('button', { name: 'Warning' }).click();
            await settle(page);

            await expect(toasts(page)).toHaveCount(3);
            await expect(toasts(page).first()).toContainText('The event cannot start before 8:00 AM.');
            await expect(toasts(page).first()).not.toHaveAttribute('data-expanded');
        },
    });

    testState('expands the stack on hover', {
        example: 'types',
        state: 'expanded',
        act: async (page) => {
            await freezeTime(page);
            await page.getByRole('button', { name: 'Success' }).click();
            await page.getByRole('button', { name: 'Info' }).click();
            await page.getByRole('button', { name: 'Error' }).click();
            await settle(page);

            await toasts(page).first().hover();

            for (const toast of await toasts(page).all()) {
                await expect(toast).toHaveAttribute('data-expanded');
            }
        },
    });

    testState('replaces the loading toast once the task succeeds', {
        example: 'promise',
        state: 'success',
        act: async (page) => {
            await freezeTime(page);

            await page.getByRole('button', { name: 'Create Event', exact: true }).click();
            await settle(page);
            await page.clock.runFor(2000);

            await expect(toasts(page)).toHaveCount(1);
            await expect(toasts(page)).toHaveAttribute('data-type', 'success');
            await expect(toasts(page)).toContainText('Event created.');
        },
    });

    testState('anchors the toasts to the position of their region', {
        example: 'position',
        state: 'placed',
        act: async (page) => {
            await freezeTime(page);

            // Three regions that do not overlap at the width of the viewport. The top toasts land over the
            // buttons, which a real click could no longer reach.
            for (const name of ['Top Left', 'Top Right', 'Bottom Center']) {
                await page.getByRole('button', { name }).dispatchEvent('click');
            }
            await settle(page);

            await expect(page.locator('[data-position="top-left"] [data-slot="toast"]')).toHaveText(
                'Top left notification'
            );
            await expect(page.locator('[data-position="top-right"] [data-slot="toast"]')).toHaveText(
                'Top right notification'
            );
            await expect(page.locator('[data-position="bottom-center"] [data-slot="toast"]')).toHaveText(
                'Bottom center notification'
            );
        },
    });

    test('announces an error assertively and other types politely', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/types', { timers: 'fake' });

        await page.getByRole('button', { name: 'Error' }).click();
        await page.getByRole('button', { name: 'Success' }).click();

        await expect(page.getByRole('alert')).toHaveText('The event could not be created.');
        await expect(page.getByRole('alert')).toHaveAttribute('aria-live', 'assertive');
        await expect(toasts(page).first()).not.toHaveAttribute('role');
        await expect(page.getByRole('region', { name: 'Notifications' })).toHaveAttribute('aria-live', 'polite');
    });

    test('shows only the icon of its type', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/types', { timers: 'fake' });

        await page.getByRole('button', { name: 'Warning' }).click();

        const icons = toasts(page).locator('[data-slot="toast-icon"]');
        await expect(icons.locator('visible=true')).toHaveCount(1);
        await expect(icons.locator('visible=true')).toHaveAttribute('data-type', 'warning');
    });

    test('stacks the newest toast in front', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/types', { timers: 'fake' });

        await page.getByRole('button', { name: 'Default' }).click();
        await page.getByRole('button', { name: 'Success' }).click();
        await page.getByRole('button', { name: 'Info' }).click();

        await expect(toasts(page).locator('[data-slot="toast-description"]')).toHaveText([
            'Arrive 10 minutes before the event.',
            'Event has been created.',
            'Event has been created.',
        ]);
    });

    test('fades out the toasts beyond the limit', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/types', { timers: 'fake' });

        for (const name of ['Default', 'Success', 'Info', 'Warning']) {
            await page.getByRole('button', { name }).click();
        }

        await expect(toasts(page)).toHaveCount(4);
        await expect(toasts(page).nth(2)).not.toHaveAttribute('data-limited');
        await expect(toasts(page).nth(3)).toHaveAttribute('data-limited');
    });

    test('keeps the toasts beyond the limit out of the tab order', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/types', { timers: 'fake' });
        for (const name of ['Default', 'Success', 'Info', 'Warning']) {
            await page.getByRole('button', { name }).click();
        }
        await expect(toasts(page).nth(3)).toHaveAttribute('data-limited');

        await toasts(page).nth(2).getByRole('button', { name: 'Close toast' }).focus();
        await page.keyboard.press('Tab');

        await expect(page.getByRole('button', { name: 'Default' })).toBeFocused();
    });

    test('disappears after its duration', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await recordClose(page);
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.mouse.move(0, 0);

        await page.clock.runFor(4000);

        await expect(toasts(page)).toHaveCount(1);

        await page.clock.runFor(2000);

        await expect(toasts(page)).toHaveCount(0);
        await expect(page.locator('body')).toHaveAttribute('data-close-reason', 'timeout');
    });

    test('still disappears after its duration once moved in the DOM', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.mouse.move(0, 0);
        await page.locator('[data-controller="toast"]').evaluate(async (element) => {
            const parent = element.parentNode!;
            const next = element.nextSibling;
            element.remove();
            await new Promise((resolve) => setTimeout(resolve, 50));
            parent.insertBefore(element, next);
        });

        await page.clock.runFor(6000);

        await expect(toasts(page)).toHaveCount(0);
    });

    test('pauses the countdown while hovered', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.clock.runFor(100);
        await toasts(page).hover();

        await page.clock.runFor(10_000);

        await expect(toasts(page)).toHaveCount(1);

        await page.mouse.move(0, 0);
        await page.clock.runFor(6000);

        await expect(toasts(page)).toHaveCount(0);
    });

    test('pauses the countdown while it holds the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.mouse.move(0, 0);

        await toasts(page).getByRole('button', { name: 'Undo' }).focus();
        await page.keyboard.press('Tab');
        await expect(toasts(page).getByRole('button', { name: 'Close toast' })).toBeFocused();
        await page.clock.runFor(10_000);

        await expect(toasts(page)).toHaveCount(1);

        await page.getByRole('button', { name: 'Show Toast' }).focus();
        await page.clock.runFor(6000);

        await expect(toasts(page)).toHaveCount(0);
    });

    test('closes with its close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await recordClose(page);
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.clock.runFor(100);

        await toasts(page).getByRole('button', { name: 'Close toast' }).click();
        await page.clock.runFor(1000);

        await expect(toasts(page)).toHaveCount(0);
        await expect(page.locator('body')).toHaveAttribute('data-close-reason', 'close');
    });

    test('closes on Escape while it holds the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await recordClose(page);
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.clock.runFor(100);

        await toasts(page).getByRole('button', { name: 'Undo' }).focus();
        await page.keyboard.press('Escape');
        await page.clock.runFor(1000);

        await expect(toasts(page)).toHaveCount(0);
        await expect(page.locator('body')).toHaveAttribute('data-close-reason', 'escape');
    });

    test('closes on its action and emits the toast id', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/with-action', { timers: 'fake' });
        await recordClose(page);
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await page.clock.runFor(100);
        const id = await toasts(page).getAttribute('data-toast-id');

        await toasts(page).getByRole('button', { name: 'Undo' }).click();
        await page.clock.runFor(1000);

        await expect(toasts(page)).toHaveCount(0);
        await expect(page.locator('body')).toHaveAttribute('data-closed-toast', id!);
        await expect(page.locator('body')).toHaveAttribute('data-close-reason', 'action');
    });

    test('closes on a swipe to the right', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await recordClose(page);
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await waitForEntrance(page);
        const box = await toasts(page).boundingBox();
        expect(box).not.toBeNull();

        await page.mouse.move(box!.x + 20, box!.y + box!.height / 2);
        await page.mouse.down();
        await page.mouse.move(box!.x + 140, box!.y + box!.height / 2, { steps: 5 });
        await page.mouse.up();

        await expect(toasts(page)).toHaveAttribute('data-swipe-direction', 'right');

        await page.clock.runFor(1000);

        await expect(toasts(page)).toHaveCount(0);
        await expect(page.locator('body')).toHaveAttribute('data-close-reason', 'swipe');
    });

    test('keeps a toast on a short drag', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await page.getByRole('button', { name: 'Show Toast' }).click();
        await waitForEntrance(page);
        const box = await toasts(page).boundingBox();
        expect(box).not.toBeNull();

        await page.mouse.move(box!.x + 20, box!.y + box!.height / 2);
        await page.mouse.down();
        await page.mouse.move(box!.x + 40, box!.y + box!.height / 2, { steps: 5 });
        await page.mouse.up();
        await page.clock.runFor(1000);

        await expect(toasts(page)).toHaveCount(1);
        await expect(toasts(page)).not.toHaveAttribute('data-ending-style');
    });

    test('turns a failing promise into an error', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/promise', { timers: 'fake' });

        await page.getByRole('button', { name: 'Create Event (Failing)' }).click();
        await page.clock.runFor(100);

        await expect(toasts(page)).toHaveAttribute('data-type', 'loading');
        await expect(toasts(page)).toContainText('Creating event…');

        // A loading toast has no duration: it waits for the task however long it takes.
        await page.clock.runFor(2000);

        await expect(toasts(page)).toHaveCount(1);
        await expect(page.getByRole('alert')).toHaveText('Could not create event.');
    });

    test('keeps apart two promises started in the same millisecond', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/default', { timers: 'fake' });
        await page.clock.pauseAt(new Date('2026-03-15T10:01:00Z'));

        await page.evaluate(async () => {
            const { imports } = JSON.parse(document.querySelector('script[type="importmap"]')!.textContent!);
            const specifier = Object.keys(imports).find((key) => key.endsWith('/toast_controller.js'))!;
            const { toast } = await import(specifier);
            toast.promise(new Promise(() => {}), { loading: 'Uploading photo…' });
            toast.promise(new Promise(() => {}), { loading: 'Uploading video…' });
        });

        await expect(toasts(page)).toHaveCount(2);
    });

    test('keeps server-rendered toasts until they are dismissed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/server-rendered', { timers: 'fake' });
        const saved = toasts(page).filter({ hasText: 'Profile saved' });
        const welcome = toasts(page).filter({ hasText: 'Welcome back, Alice!' });
        await expect(saved).not.toHaveAttribute('data-starting-style');

        await page.clock.runFor(10_000);

        await expect(toasts(page)).toHaveCount(2);

        await saved.getByRole('button', { name: 'Close toast' }).click();
        await page.clock.runFor(1000);

        await expect(saved).toHaveCount(0);
        await expect(welcome).toBeVisible();
    });

    test('picks up a toast inserted after the page has loaded', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toast/server-rendered', { timers: 'fake' });

        // Stands in for a Turbo Stream prepending a `Toast` to the viewport.
        await page.evaluate(() => {
            const template = document.querySelector<HTMLTemplateElement>('template[data-toast-target="template"]')!;
            const toast = template.content.firstElementChild!.cloneNode(true) as HTMLElement;
            toast.querySelector('[data-slot="toast-title"]')!.textContent = 'Streamed in';
            document.querySelector('[data-slot="toast-viewport"]')!.prepend(toast);
        });
        await page.mouse.move(0, 0);
        await page.clock.runFor(100);

        const streamed = toasts(page).filter({ hasText: 'Streamed in' });
        await expect(streamed).not.toHaveAttribute('data-starting-style');
        await expect(streamed).toHaveAttribute('style', /--toast-index: 0/);

        await page.clock.runFor(6000);

        await expect(streamed).toHaveCount(0);
        await expect(toasts(page)).toHaveCount(2);
    });
});
