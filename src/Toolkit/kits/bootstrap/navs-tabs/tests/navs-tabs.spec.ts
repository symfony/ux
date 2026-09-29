import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/navs-tabs', () => {
    testState('shows the panel of the clicked tab', {
        example: 'javascript-tabs',
        state: 'profile',
        act: async (page) => {
            const profile = page.getByRole('tab', { name: 'Profile' });

            await profile.click();

            await expect(page.getByRole('tabpanel', { name: 'Profile' })).toBeVisible();
            await expect(page.getByText('This is the Home tab content.')).toBeHidden();
            await expect(profile).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tab', { name: 'Home' })).toHaveAttribute('aria-selected', 'false');
        },
    });

    testState('shows the panel of the clicked pill', {
        example: 'vertical-pills',
        state: 'messages',
        act: async (page) => {
            const messages = page.getByRole('tab', { name: 'Messages' });

            await messages.click();

            await expect(page.getByRole('tabpanel', { name: 'Messages' })).toBeVisible();
            await expect(page.getByText('Home content.')).toBeHidden();
            await expect(messages).toHaveAttribute('aria-selected', 'true');
            await page.mouse.move(0, 0);
        },
    });

    testState('opens the dropdown of a nav item', {
        example: 'tabs-with-dropdowns',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Dropdown' });

            await trigger.click();

            await expect(page.getByRole('link', { name: 'Another action' })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('moves between tabs with the arrow keys and skips the disabled one', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navs-tabs/javascript-tabs');
        await page.getByRole('tab', { name: 'Home' }).focus();

        await page.keyboard.press('ArrowRight');

        await expect(page.getByRole('tab', { name: 'Profile' })).toBeFocused();
        await expect(page.getByRole('tab', { name: 'Profile' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel', { name: 'Profile' })).toBeVisible();

        await page.keyboard.press('ArrowRight');
        await page.keyboard.press('ArrowRight');

        await expect(page.getByRole('tab', { name: 'Home' })).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Home' })).toBeVisible();

        await page.keyboard.press('ArrowLeft');

        await expect(page.getByRole('tab', { name: 'Contact' })).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Contact' })).toBeVisible();
    });

    test('jumps to the first and last tabs with Home and End', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navs-tabs/javascript-nav');
        await page.getByRole('tab', { name: 'Profile' }).click();

        await page.keyboard.press('End');

        await expect(page.getByRole('tab', { name: 'Contact' })).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Contact' })).toBeVisible();

        await page.keyboard.press('Home');

        await expect(page.getByRole('tab', { name: 'Home' })).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Home' })).toBeVisible();
    });

    test('moves between vertical pills with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navs-tabs/vertical-pills');
        await page.getByRole('tab', { name: 'Profile' }).click();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('tab', { name: 'Messages' })).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Messages' })).toBeVisible();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('tab', { name: 'Profile' })).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Profile' })).toBeVisible();
    });

    test('keeps only the selected tab in the tab order', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navs-tabs/javascript-pills');
        await page.getByRole('tab', { name: 'Home' }).focus();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('tabpanel', { name: 'Home' })).toBeFocused();
    });

    test('closes the nav dropdown on Escape and gives focus back to its toggle', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/navs-tabs/pills-with-dropdowns');
        const trigger = page.getByRole('button', { name: 'Dropdown' });
        await trigger.click();
        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });
});
