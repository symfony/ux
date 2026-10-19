import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-06', () => {
    testState('opens a submenu as a dropdown', {
        example: 'default',
        state: 'submenu-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Build Your Application' });

            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(page.getByRole('menuitem', { name: 'Data Fetching' })).toBeVisible();
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
            await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-collapsible', 'offcanvas');
        },
    });
});
