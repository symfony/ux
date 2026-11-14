import type { Locator } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

async function recordExit(drawer: Locator): Promise<void> {
    await drawer.evaluate((element) => {
        new MutationObserver(() => {
            if (element.hasAttribute('data-closing')) {
                element.dataset.exitPlayed = '';
            }
        }).observe(element, { attributeFilter: ['data-closing'] });
    });
}

describeRecipe('shadcn/drawer', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Drawer' });

            await trigger.click();

            await expect(page.getByRole('dialog', { name: 'Move Goal' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('opens a drawer from the right edge', {
        example: 'directions',
        state: 'right-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'right', exact: true }).click();

            await expect(page.getByRole('dialog', { name: 'Move Goal' })).toBeVisible();
        },
    });

    testState('opens a right-to-left drawer', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'فتح' }).click();

            await expect(page.getByRole('dialog', { name: 'تعديل الهدف' })).toBeVisible();
        },
    });

    test('exposes the title and the description', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');

        await page.getByRole('button', { name: 'Open Drawer' }).click();

        await expect(page.getByRole('dialog', { name: 'Move Goal' })).toHaveAccessibleDescription(
            'Set your daily activity goal.'
        );
    });

    for (const direction of ['top', 'right', 'bottom', 'left'] as const) {
        test(`slides in against the ${direction} edge`, async ({ page, gotoExample }) => {
            await gotoExample('shadcn/drawer/directions');
            const viewport = page.viewportSize()!;

            await page.getByRole('button', { name: direction, exact: true }).click();

            const dialog = page.getByRole('dialog', { name: 'Move Goal' });
            await expect(dialog).toBeVisible();
            await dialog.evaluate((element) =>
                Promise.all(element.getAnimations().map((animation) => animation.finished))
            );
            const box = (await dialog.boundingBox())!;
            const distances = {
                top: box.y,
                right: viewport.width - (box.x + box.width),
                bottom: viewport.height - (box.y + box.height),
                left: box.x,
            };
            expect(Math.abs(distances[direction])).toBeLessThan(1);
        });
    }

    test('opens from a command button outside of it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        await page.evaluate(() => {
            const button = document.createElement('button');
            button.textContent = 'Move from elsewhere';
            button.setAttribute('command', 'show-modal');
            button.setAttribute('commandfor', 'drawer-drawer-demo');
            document.body.append(button);
        });
        const invoker = page.getByRole('button', { name: 'Move from elsewhere' });

        await invoker.click();

        await expect(page.getByRole('dialog', { name: 'Move Goal' })).toBeVisible();
        await expect(invoker).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(invoker).toHaveAttribute('aria-expanded', 'false');
        await expect(invoker).toBeFocused();
    });

    test('slides out when a command button closes it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        const drawer = page.locator('[data-slot="drawer-content"]');
        await expect(drawer).toBeVisible();
        await recordExit(drawer);
        await drawer.evaluate((element) => {
            const button = document.createElement('button');
            button.textContent = 'Done';
            button.setAttribute('command', 'close');
            button.setAttribute('commandfor', element.id);
            element.append(button);
        });

        await drawer.getByRole('button', { name: 'Done' }).click();

        await expect(drawer).toBeHidden();
        await expect(drawer).toHaveAttribute('data-exit-played');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('slides out when Escape closes it while nothing inside holds the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        const drawer = page.locator('[data-slot="drawer-content"]');
        await expect(drawer).toBeVisible();
        await recordExit(drawer);
        await page.evaluate(() => (document.activeElement as HTMLElement).blur());

        await page.keyboard.press('Escape');

        await expect(drawer).toBeHidden();
        await expect(drawer).toHaveAttribute('data-exit-played');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes with a Drawer:Close button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByRole('button', { name: 'Cancel' }).click();

        await expect(dialog).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click on the backdrop', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        const trigger = page.getByRole('button', { name: 'Open Drawer' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('stays open on a click inside the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        await page.getByRole('button', { name: 'Open Drawer' }).click();
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();

        await dialog.getByText('Set your daily activity goal.').click();

        await expect(dialog).toBeVisible();
    });

    test('leaves the top layer as soon as it closes', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/drawer/default');
        await page.getByRole('button', { name: 'Open Drawer' }).click();
        const dialog = page.locator('[data-slot="drawer-content"]');
        await expect(dialog).toBeVisible();
        await dialog.evaluate((element) => {
            element.addEventListener(
                'close',
                () => {
                    element.dataset.displayOnClose = getComputedStyle(element).display;
                },
                { once: true }
            );
        });

        await page.keyboard.press('Escape');

        await expect(dialog).toHaveAttribute('data-display-on-close', 'none');
    });

    test.describe('without JavaScript', () => {
        test.use({ javaScriptEnabled: false });

        test('opens from its trigger and closes from its close button', async ({ page }) => {
            await page.goto('/shadcn/drawer/default');

            await page.getByRole('button', { name: 'Open Drawer' }).click();

            const drawer = page.getByRole('dialog', { name: 'Move Goal' });
            await expect(drawer).toBeVisible();

            await drawer.getByRole('button', { name: 'Cancel' }).click();

            await expect(drawer).toBeHidden();
        });
    });
});
