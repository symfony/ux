import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-11', () => {
    testState('expands nested folders of the file tree', {
        example: 'default',
        state: 'app-expanded',
        fullPage: false,
        act: async (page) => {
            const app = page.getByRole('button', { name: 'app', exact: true });
            const api = page.getByRole('button', { name: 'api', exact: true });

            await app.click();
            await api.click();

            await expect(app).toHaveAttribute('aria-expanded', 'true');
            await expect(api).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('button', { name: 'blog', exact: true })).toBeVisible();
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
