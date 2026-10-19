import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-04', () => {
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

    test('starts the page content where the sidebar ends', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar-04/default');

        const sidebar = (await page.locator('[data-slot="sidebar-container"]').boundingBox())!;
        const content = (await page.getByRole('main').boundingBox())!;

        expect(content.x).toBe(sidebar.x + sidebar.width);
    });
});
