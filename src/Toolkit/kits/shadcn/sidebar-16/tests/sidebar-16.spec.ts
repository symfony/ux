import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-16', () => {
    testState('hides the sidebar off-canvas on a click on the site header trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('banner').getByRole('button', { name: 'Toggle Sidebar' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-collapsible', 'offcanvas');
        },
    });

    testState('expands a collapsed section of the main navigation', {
        example: 'default',
        state: 'models-expanded',
        fullPage: false,
        act: async (page) => {
            const item = page.getByRole('listitem').filter({ has: page.getByRole('link', { name: 'Models' }) });
            const toggle = item.getByRole('button', { name: 'Toggle' });

            await toggle.click();

            await expect(toggle).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('link', { name: 'Genesis' })).toBeVisible();
        },
    });

    testState('opens the user menu', {
        example: 'default',
        state: 'user-menu-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: /shadcn/ });

            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeInViewport();
        },
    });
});
