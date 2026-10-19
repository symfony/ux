import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-05', () => {
    testState('expands a submenu on a click on its button', {
        example: 'default',
        state: 'submenu-expanded',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Getting Started' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('link', { name: 'Installation' })).toBeVisible();
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
