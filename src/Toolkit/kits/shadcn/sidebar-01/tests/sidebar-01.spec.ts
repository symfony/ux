import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-01', () => {
    testState('opens the version switcher', {
        example: 'default',
        state: 'version-switcher-open',
        fullPage: false,
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Documentation v1.0.1' });

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
            await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-collapsible', 'offcanvas');
        },
    });

    test('scrolls to the entries below the viewport', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar-01/default');
        const lastEntry = page.getByRole('complementary', { name: 'Sidebar' }).getByRole('link', { name: 'Turbopack' });

        await lastEntry.click();

        await expect(lastEntry).toBeInViewport();
    });
});
