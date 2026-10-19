import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-12', () => {
    testState('opens the user menu', {
        example: 'default',
        state: 'user-menu-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: /m@example\.com/ });

            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('expands a calendar group', {
        example: 'default',
        state: 'favorites-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Favorites' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('button', { name: 'Birthdays' })).toBeVisible();
        },
    });
});
