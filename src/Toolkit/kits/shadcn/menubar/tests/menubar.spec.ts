import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/menubar', () => {
    testState('opens a menu on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('menuitem', { name: 'File', exact: true });

            await trigger.click();

            await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await page.mouse.move(0, 0);
        },
    });

    test('closes the menu on a second click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const trigger = page.getByRole('menuitem', { name: 'File', exact: true });
        await trigger.click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens a menu with the keyboard and closes it on Escape', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const trigger = page.getByRole('menuitem', { name: 'Edit' });
        await trigger.focus();

        await page.keyboard.press('Enter');

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes the menu on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'File', exact: true })).toHaveAttribute(
            'aria-expanded',
            'false'
        );
    });

    test('does not open a menu on hover while every menu is closed', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');

        await page.getByRole('menuitem', { name: 'Edit' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Edit' })).toHaveAttribute('aria-expanded', 'false');
    });

    test('switches to the hovered menu once a menu is open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/default');
        const file = page.getByRole('menuitem', { name: 'File', exact: true });
        const edit = page.getByRole('menuitem', { name: 'Edit' });
        await file.click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeVisible();

        await edit.hover();

        await expect(page.getByRole('menuitem', { name: 'Undo' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeHidden();
        await expect(edit).toHaveAttribute('aria-expanded', 'true');
        await expect(file).toHaveAttribute('aria-expanded', 'false');
    });

    testState('opens a submenu on hover', {
        example: 'submenus',
        state: 'submenu-open',
        act: async (page) => {
            await page.getByRole('menuitem', { name: 'Edit' }).click();

            await page.getByRole('menuitem', { name: 'Find', exact: true }).hover();

            await expect(page.getByRole('menuitem', { name: 'Find Previous' })).toBeVisible();
        },
    });

    test('closes a submenu when the pointer leaves it', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await page.getByRole('menuitem', { name: 'Share' }).hover();
        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'Print...' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Print...' })).toBeVisible();
    });

    test('opens a submenu when its trigger gets the focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/submenus');
        await page.getByRole('menuitem', { name: 'File', exact: true }).click();
        await page.mouse.move(0, 0);

        await page.getByRole('menuitem', { name: 'Share' }).focus();

        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'Share' }).blur();

        await expect(page.getByRole('menuitem', { name: 'Email link' })).toBeHidden();
    });

    test('toggles a checkbox item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/menubar/checkbox-items');
        await page.getByRole('menuitem', { name: 'View' }).click();
        const bookmarks = page.getByRole('checkbox', { name: 'Always Show Bookmarks Bar' });
        await expect(bookmarks).not.toBeChecked();

        await page.getByText('Always Show Bookmarks Bar').click();

        await expect(bookmarks).toBeChecked();
        await expect(page.getByRole('checkbox', { name: 'Always Show Full URLs' })).toBeChecked();

        await page.getByText('Always Show Bookmarks Bar').click();

        await expect(bookmarks).not.toBeChecked();
    });

    testState('selects a radio item', {
        example: 'radio-group',
        state: 'selected',
        act: async (page) => {
            await page.getByRole('menuitem', { name: 'Profiles' }).click();
            await expect(page.getByRole('radio', { name: 'Benoit' })).toBeChecked();

            await page.getByText('Andy').click();

            await expect(page.getByRole('radio', { name: 'Andy' })).toBeChecked();
            await expect(page.getByRole('radio', { name: 'Benoit' })).not.toBeChecked();
            await expect(page.getByRole('menuitem', { name: 'Add Profile...' })).toBeVisible();
            await page.mouse.move(0, 0);
        },
    });
});
