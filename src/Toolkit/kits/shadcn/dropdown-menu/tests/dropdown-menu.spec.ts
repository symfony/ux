import { describeRecipe, expect, isUnderPointer, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/dropdown-menu', () => {
    testState('opens on click and focuses the first item', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Open' });

            await trigger.click();

            await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();
        },
    });

    test('closes on a second click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await page.mouse.click(10, 10);

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
    });

    test('closes on Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
    });

    test('closes when an item is selected and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });
        await trigger.click();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'Billing' }).click();

        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('opens from the keyboard on the first or the last item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        const trigger = page.getByRole('button', { name: 'Open' });

        await trigger.press('ArrowDown');

        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('Escape');
        await trigger.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeFocused();
    });

    test('moves between items with the arrow keys, Home and End, skipping the disabled one', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/dropdown-menu/default');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Billing' })).toBeFocused();

        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeFocused();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('menuitem', { name: 'New Team' })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();

        await page.keyboard.press('End');

        await expect(page.getByRole('menuitem', { name: 'Log out' })).toBeFocused();

        await page.keyboard.press('Home');

        await expect(page.getByRole('menuitem', { name: 'Profile' })).toBeFocused();
    });

    test('marks the disabled item as disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/default');

        await page.getByRole('button', { name: 'Open' }).click();

        await expect(page.getByRole('menuitem', { name: 'API' })).toHaveAttribute('aria-disabled', 'true');
    });

    test('toggles a checkbox item and keeps the menu open', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/checkboxes');
        await page.getByRole('button', { name: 'Open' }).click();
        const panel = page.getByRole('menuitemcheckbox', { name: 'Panel' });
        const statusBar = page.getByRole('menuitemcheckbox', { name: 'Status Bar' });
        await expect(panel).not.toBeChecked();
        await expect(statusBar).toBeChecked();

        await panel.click();
        await statusBar.click();

        await expect(panel).toBeChecked();
        await expect(statusBar).not.toBeChecked();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        await expect(page.getByRole('menuitemcheckbox', { name: 'Full Screen' })).toHaveAttribute(
            'aria-disabled',
            'true'
        );
    });

    testState('selects a radio item and keeps the menu open', {
        example: 'radio-group',
        state: 'top-selected',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open' }).click();
            const top = page.getByRole('menuitemradio', { name: 'Top' });
            await expect(top).toBeVisible();

            await top.click();
            await page.mouse.move(0, 0);

            await expect(top).toBeChecked();
            await expect(page.getByRole('menuitemradio', { name: 'Bottom' })).not.toBeChecked();
            await expect(page.getByRole('menuitemradio', { name: 'Right' })).not.toBeChecked();
            await expect(page.getByRole('menu', { name: 'Open' })).toBeVisible();
        },
    });

    testState('shows icons, shortcuts and a destructive item', {
        example: 'complex',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Complex Menu' }).click();

            await expect(page.getByRole('menuitem', { name: 'New File' })).toBeFocused();
            await expect(page.getByRole('menuitem', { name: 'Sign Out' })).toBeVisible();
            await expect(page.getByRole('menuitem', { name: 'Sign Out' })).toHaveAttribute(
                'data-variant',
                'destructive'
            );
        },
    });

    testState('opens a nested radio group in a submenu', {
        example: 'complex',
        state: 'theme-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Complex Menu' }).click();
            await expect(page.getByRole('menuitem', { name: 'New File' })).toBeFocused();
            const theme = page.getByRole('menuitem', { name: 'Theme' });

            await theme.hover();

            const light = page.getByRole('menuitemradio', { name: 'Light' });
            await expect(theme).toHaveAttribute('data-state', 'open');
            await expect.poll(() => isUnderPointer(light)).toBe(true);
            await expect(light).toBeChecked();
        },
    });

    testState('opens the destructive example', {
        example: 'destructive',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Actions' }).click();

            await expect(page.getByRole('menuitem', { name: 'Delete' })).toBeVisible();
        },
    });

    testState('opens the menu aligned to the end of an avatar trigger', {
        example: 'avatar',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'shadcn' }).click();

            await expect(page.getByRole('menuitem', { name: 'Sign Out' })).toBeVisible();
        },
    });

    testState('opens a submenu on hover', {
        example: 'submenus',
        state: 'submenu-open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open' }).click();
            const share = page.getByRole('menuitem', { name: 'Share' });
            await expect(share).toBeVisible();

            await share.hover();

            const copyLink = page.getByRole('menuitem', { name: 'Copy link' });
            await expect(copyLink).toBeVisible();
            await expect(share).toHaveAttribute('data-state', 'open');
            await expect.poll(() => isUnderPointer(copyLink)).toBe(true);
        },
    });

    test('opens a nested submenu on hover and closes it when the pointer leaves', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        await page.getByRole('menuitem', { name: 'Share' }).hover();

        await page.getByRole('menuitem', { name: 'More options' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Messages' })).toBeVisible();

        await page.getByRole('menuitem', { name: 'New Tab' }).hover();

        await expect(page.getByRole('menuitem', { name: 'Messages' })).toBeHidden();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeHidden();
    });

    test('opens a submenu when its trigger gets keyboard focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/submenus');
        await page.getByRole('button', { name: 'Open' }).click();
        await expect(page.getByRole('menuitem', { name: 'New Tab' })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Share' })).toBeFocused();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeVisible();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('menuitem', { name: 'Print' })).toBeFocused();
        await expect(page.getByRole('menuitem', { name: 'Copy link' })).toBeHidden();
    });

    test('opens a submenu on the left in RTL', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/rtl');
        await page.getByRole('button', { name: 'افتح القائمة' }).click();
        const share = page.getByRole('menuitem', { name: 'مشاركة' });

        await share.hover();

        const email = page.getByRole('menuitem', { name: 'بريد إلكتروني' });
        await expect(email).toBeVisible();
        const shareBox = await share.boundingBox();
        const emailBox = await email.boundingBox();
        expect(emailBox!.x + emailBox!.width).toBeLessThan(shareBox!.x);
    });

    test('closes the menu and opens the dialog from the same item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/dropdown-menu/with-dialog');
        await page.getByRole('button', { name: 'Open' }).click();

        await page.getByRole('menuitem', { name: 'Invite users' }).click();

        await expect(page.getByRole('dialog', { name: 'Invite team members' })).toBeVisible();
        await expect(page.getByRole('menu', { name: 'Open' })).toBeHidden();
    });
});
