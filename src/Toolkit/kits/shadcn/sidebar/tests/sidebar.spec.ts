import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/sidebar', () => {
    testState('collapses to icons on a click on the trigger', {
        example: 'default',
        state: 'collapsed',
        act: async (page) => {
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });

            await trigger.click();
            await page.mouse.move(400, 300);

            await expect(trigger).toHaveAttribute('aria-expanded', 'false');
            await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-collapsible', 'icon');
            await expect(page.getByText('Models')).toBeHidden();
            await expect(page.getByRole('link', { name: 'Models' })).toBeVisible();
        },
    });

    test('expands again on a second click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');
        const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });
        const sidebar = page.locator('[data-slot="sidebar"]');
        await trigger.click();
        await expect(sidebar).toHaveAttribute('data-state', 'collapsed');

        await trigger.click();

        await expect(sidebar).toHaveAttribute('data-state', 'expanded');
        await expect(sidebar).toHaveAttribute('data-collapsible', '');
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByText('Models')).toBeVisible();
    });

    test('toggles with Ctrl+B', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');
        const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });
        const sidebar = page.locator('[data-slot="sidebar"]');

        await page.keyboard.press('Control+b');

        await expect(sidebar).toHaveAttribute('data-state', 'collapsed');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');

        await page.keyboard.press('Control+b');

        await expect(sidebar).toHaveAttribute('data-state', 'expanded');
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    });

    test('ignores B without Ctrl or Meta', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');

        await page.keyboard.press('b');

        await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-state', 'expanded');
    });

    test('toggles on a click on the rail', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');
        const rail = page
            .getByRole('complementary', { name: 'Sidebar' })
            .getByRole('button', { name: 'Toggle Sidebar' });

        await rail.click();

        await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-state', 'collapsed');
        await expect(page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' })).toHaveAttribute(
            'aria-expanded',
            'false'
        );
    });

    test('does not write a cookie when cookieName is empty', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');
        const sidebar = page.locator('[data-slot="sidebar"]');

        await page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' }).click();
        await expect(sidebar).toHaveAttribute('data-state', 'collapsed');
        await page.reload();

        expect((await page.context().cookies()).map(({ name }) => name)).not.toContain('sidebar:state');
        await expect(sidebar).toHaveAttribute('data-state', 'expanded');
    });

    test('collapses and expands a submenu', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');
        const playground = page.getByRole('button', { name: 'Playground' });
        await expect(page.getByRole('link', { name: 'History' })).toBeVisible();

        await playground.click();

        await expect(playground).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('link', { name: 'History' })).toBeHidden();

        await playground.click();

        await expect(playground).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: 'History' })).toBeVisible();
    });

    test('leaves room for a menu action that opens a dropdown menu', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');

        const button = page
            .getByRole('complementary', { name: 'Sidebar' })
            .getByRole('link', { name: 'Sales & Marketing' });

        await expect(button).toHaveCSS('padding-inline-end', '32px');
    });

    test('does not clip the dropdown menu of a menu action', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/sidebar/default');

        await page
            .getByRole('complementary', { name: 'Sidebar' })
            .getByRole('button', { name: 'More' })
            .first()
            .click();

        const viewProject = page.getByRole('menuitem', { name: 'View project' });
        await expect(viewProject).toBeVisible();
        await expect.poll(() => isUnderPointer(viewProject)).toBe(true);
    });

    testState('hides the sidebar off-canvas on a click on the trigger', {
        example: 'off-canvas-collapsing',
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

    testState('opens as a sheet on mobile', {
        example: 'default',
        state: 'mobile-open',
        act: async (page) => {
            await page.setViewportSize({ width: 500, height: 700 });
            const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });
            await expect(page.getByRole('complementary', { name: 'Sidebar' })).toBeHidden();

            await trigger.click();

            const sheet = page.getByRole('dialog', { name: 'Sidebar' });
            await expect(sheet).toBeVisible();
            await expect(sheet.getByRole('link', { name: 'Models' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('closes the mobile sheet on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 500, height: 700 });
        await gotoExample('shadcn/sidebar/default');
        const trigger = page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' });
        await trigger.click();
        await expect(page.getByRole('dialog', { name: 'Sidebar' })).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('dialog', { name: 'Sidebar' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
        await expect(page.locator('[data-slot="sidebar"]')).toHaveAttribute('data-state', 'expanded');
    });

    test('moves the menu back into the desktop sidebar above the breakpoint', async ({ page, gotoExample }) => {
        await page.setViewportSize({ width: 500, height: 700 });
        await gotoExample('shadcn/sidebar/default');
        await page.getByRole('main').getByRole('button', { name: 'Toggle Sidebar' }).click();
        await expect(page.getByRole('dialog', { name: 'Sidebar' })).toBeVisible();

        await page.setViewportSize({ width: 800, height: 700 });

        await expect(page.getByRole('dialog', { name: 'Sidebar' })).toBeHidden();
        await expect(
            page.getByRole('complementary', { name: 'Sidebar' }).getByRole('link', { name: 'Models' })
        ).toBeVisible();
    });
});
