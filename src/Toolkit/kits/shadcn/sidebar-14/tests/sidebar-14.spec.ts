import { describeRecipe, expect, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-14', () => {
    testState('hides the sidebar off-canvas on the right on a click on the trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });

            await trigger.click();
            await page.mouse.move(400, 300);

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-collapsible', 'offcanvas');
            await expect(page.getByRole('complementary', { name: 'Sidebar' })).not.toBeInViewport();
        },
    });
});
