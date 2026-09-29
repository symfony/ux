import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('flowbite-4/dropdown', () => {
    test('closes on a click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await expect(page.getByRole('menu')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('menu')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens with ArrowDown and closes with Escape', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await trigger.click();
        await expect(page.getByRole('menu')).toBeHidden();

        await trigger.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Dashboard' })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menu')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    testState('opens on hover', {
        example: 'dropdown-hover',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Dropdown button' }).hover();

            await expect(page.getByRole('menu')).toBeVisible();
        },
    });
});
