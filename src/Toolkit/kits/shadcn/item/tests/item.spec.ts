import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/item', () => {
    testState('shows items as the entries of a dropdown menu', {
        example: 'dropdown',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Select' });

            await trigger.click();

            const menu = page.getByRole('menu', { name: 'Select' });
            await expect(menu).toBeVisible();
            await expect(menu.locator('[data-slot="item"]')).toHaveCount(3);
            await expect(menu.getByText('maxleiter@vercel.com')).toBeVisible();
            await page.mouse.move(0, 0);
        },
    });
});
