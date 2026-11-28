import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

async function moveModal(page: Page): Promise<void> {
    await page.locator('[data-controller="modal"]').evaluate(async (element) => {
        const parent = element.parentNode!;
        const next = element.nextSibling;
        element.remove();
        await new Promise((resolve) => setTimeout(resolve, 50));
        parent.insertBefore(element, next);
    });
}

describeRecipe('daisyui-5/modal', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'open modal' });

            await trigger.click();

            const dialog = page.getByRole('dialog', { name: 'Hello!' });
            await expect(dialog).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            expect(await dialog.evaluate((element) => element.matches(':modal'))).toBe(true);
        },
    });

    testState('opens a modal with a close button at the corner', {
        example: 'close-button-at-corner',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'open modal' }).click();

            await expect(page.getByRole('dialog', { name: 'Hello!' })).toBeVisible();
        },
    });

    testState('opens a modal with a custom width', {
        example: 'custom-width',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'open modal' }).click();

            await expect(page.getByRole('dialog', { name: 'Hello!' })).toBeVisible();
        },
    });

    testState('opens a modal at the end of the screen', {
        example: 'placement',
        state: 'open-end',
        act: async (page) => {
            await page.getByRole('button', { name: 'End' }).click();

            await expect(page.getByRole('dialog', { name: 'Hello!' })).toBeVisible();
            await page.mouse.move(0, 0);
        },
    });

    testState('opens a right-to-left modal', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'افتح النافذة' }).click();

            await expect(page.getByRole('dialog', { name: 'مرحبًا!' })).toBeVisible();
        },
    });

    testState('stays modal after being moved in the DOM', {
        example: 'open-by-default',
        state: 'open-after-move',
        act: async (page) => {
            await moveModal(page);
            const dialog = page.getByRole('dialog', { name: 'Hello!' });

            await expect(dialog).toBeVisible();
            expect(await dialog.evaluate((element) => element.matches(':modal'))).toBe(true);
        },
    });

    test('stays closed and out of reach until opened', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/default');

        await expect(page.getByRole('dialog')).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Close' })).toBeHidden();
        await expect(page.getByRole('button', { name: 'open modal' })).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/default');
        const trigger = page.getByRole('button', { name: 'open modal' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes with the close button and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/default');
        const trigger = page.getByRole('button', { name: 'open modal' });
        await trigger.click();

        await page.getByRole('button', { name: 'Close' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes with the close button at the corner', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/close-button-at-corner');
        const trigger = page.getByRole('button', { name: 'open modal' });
        await trigger.click();

        await page.getByRole('button', { name: 'Close' }).click();

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('does not close on a click outside the box without a backdrop', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/default');
        await page.getByRole('button', { name: 'open modal' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(page.getByRole('dialog')).toBeVisible();
    });

    test('closes on a click outside the box with a backdrop', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/closes-when-clicked-outside');
        const trigger = page.getByRole('button', { name: 'open modal' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.getByText('Press ESC key or click outside to close').click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('keeps its state in sync over several openings', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/default');
        const trigger = page.getByRole('button', { name: 'open modal' });

        for (let i = 0; i < 3; i++) {
            await trigger.click();
            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');

            await page.keyboard.press('Escape');
            await expect(page.getByRole('dialog')).toBeHidden();
            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        }
    });

    test('stays modal after being moved in the DOM once opened by a click', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/default');
        const trigger = page.getByRole('button', { name: 'open modal' });
        await trigger.click();

        await moveModal(page);

        const dialog = page.getByRole('dialog', { name: 'Hello!' });
        await expect(dialog).toBeVisible();
        expect(await dialog.evaluate((element) => element.matches(':modal'))).toBe(true);
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('stays closed after being moved in the DOM once closed', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/open-by-default');
        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();

        await moveModal(page);

        await expect(page.getByRole('dialog')).toBeHidden();
    });

    test('is open on page load when asked to', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/modal/open-by-default');
        const trigger = page.getByRole('button', { name: 'open modal' });

        await expect(page.getByRole('dialog', { name: 'Hello!' })).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });
});
