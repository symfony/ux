import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-10', () => {
    testState('opens the team switcher', {
        example: 'default',
        state: 'team-switcher-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Acme Inc', exact: true });

            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('opens the actions of a favorite', {
        example: 'default',
        state: 'favorite-actions-open',
        fullPage: false,
        act: async (page) => {
            const favorite = page.getByRole('listitem').filter({ hasText: 'Book Notes & Reading List' });
            const trigger = favorite.getByRole('button', { name: 'More', exact: true });

            await favorite.hover();
            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('hides the sidebar off-canvas on a click on the trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(page.locator('[data-slot="sidebar"]').first()).toHaveAttribute(
                'data-collapsible',
                'offcanvas'
            );
        },
    });
});
