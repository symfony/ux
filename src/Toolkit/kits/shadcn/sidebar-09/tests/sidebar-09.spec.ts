import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar-09', () => {
    testState('collapses to the icon rail on a click on the trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });

            await trigger.click();
            await page.mouse.move(400, 300);

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(page.locator('[data-slot="sidebar"]').first()).toHaveAttribute('data-collapsible', 'icon');
            await expect(page.getByPlaceholder('Type to search...')).toBeHidden();
            await expect(page.getByRole('button', { name: 'Drafts' })).toBeVisible();
        },
    });

    test('expands the sidebar on a click on a rail item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar-09/default');
        const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });
        await trigger.click();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await page.getByRole('button', { name: 'Drafts' }).click();

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByPlaceholder('Type to search...')).toBeVisible();
    });

    testState('opens the user menu from the collapsed rail', {
        example: 'default',
        state: 'collapsed-user-menu-open',
        fullPage: false,
        act: async (page) => {
            await page.keyboard.press('Control+b');
            await expect(page.locator('[data-slot="sidebar"]').first()).toHaveAttribute('data-collapsible', 'icon');
            const trigger = page.getByRole('button', { name: 'shadcn' });

            await trigger.click();

            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeInViewport();
            await expect(page.getByPlaceholder('Type to search...')).toBeHidden();
        },
    });

    test('exposes the nested sidebars as part of a single landmark', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar-09/default');

        await expect(page.getByRole('complementary')).toHaveCount(1);
    });
});
