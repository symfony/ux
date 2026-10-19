import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-07', () => {
    testState('opens the team switcher', {
        example: 'default',
        state: 'team-switcher-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Acme Inc Enterprise' });

            await trigger.click();

            await expect(page.getByRole('menu')).toBeVisible();
            await expect(page.getByRole('menuitem', { name: 'Evil Corp.' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('collapses and expands the collapsible menu entries', {
        example: 'default',
        state: 'models-expanded',
        fullPage: false,
        act: async (page) => {
            const playground = page.getByRole('button', { name: 'Playground' });
            const models = page.getByRole('button', { name: 'Models' });

            await playground.click();
            await models.click();

            await expect(playground).toHaveAttribute('aria-expanded', 'false');
            await expect(page.getByRole('link', { name: 'History' })).toBeHidden();
            await expect(models).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('link', { name: 'Genesis' })).toBeVisible();
        },
    });

    testState('collapses the sidebar to icons on a click on the trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-collapsible', 'icon');
            await expect(page.getByText('Projects', { exact: true })).toBeHidden();
        },
    });

    test('sizes the logo of the team switcher like the other menu icons', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar-07/default');

        const logo = page.getByRole('button', { name: 'Acme Inc' }).locator('svg').first();

        await expect(logo).toHaveCSS('width', '16px');
        await expect(logo).toHaveCSS('height', '16px');
    });
});
