import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/popover', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open Popover' });

            await trigger.click();

            await expect(page.getByRole('dialog')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByLabel('Width', { exact: true })).toBeFocused();
        },
    });

    test('focuses the content even when it becomes visible late', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        // Stands in for a busy browser, where the visibility transition lags behind the frames.
        await page.addStyleTag({ content: '[data-popover-target="content"] { transition-delay: 100ms; }' });

        await page.getByRole('button', { name: 'Open Popover' }).click();

        await expect(page.getByLabel('Width', { exact: true })).toBeFocused();
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        const trigger = page.getByRole('button', { name: 'Open Popover' });
        await trigger.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/popover/default');
        await page.getByRole('button', { name: 'Open Popover' }).click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('dialog')).toBeHidden();
    });
});
