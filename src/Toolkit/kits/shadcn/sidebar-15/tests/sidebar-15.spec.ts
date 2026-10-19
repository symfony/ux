import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-15', () => {
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
            const favorite = page.getByRole('listitem').filter({ hasText: 'Family Recipe Collection' });
            const trigger = favorite.getByRole('button', { name: 'More' });

            await favorite.hover();
            await trigger.click();

            await expect(page.getByRole('menuitem', { name: 'Remove from Favorites' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('hides the left sidebar off-canvas on a click on the trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });
            const sidebars = page.locator('[data-slot="sidebar"]');

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(sidebars.first()).toHaveAttribute('data-collapsible', 'offcanvas');
            await expect(sidebars.last()).toHaveAttribute('data-collapsible', '');
        },
    });

    test('names the right sidebar apart from the left one', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 1280, height: 800 });
        await gotoExample('shadcn/sidebar-15/default');

        await expect(page.getByRole('complementary', { name: 'Sidebar', exact: true })).toHaveCount(1);
        await expect(page.getByRole('complementary', { name: 'Calendars' })).toBeVisible();
    });
});
