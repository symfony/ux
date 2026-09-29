import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('flowbite-4/tabs', () => {
    testState('shows the panel of a clicked tab', {
        example: 'default',
        state: 'dashboard',
        act: async (page) => {
            const tab = page.getByRole('tab', { name: 'Dashboard' });

            await tab.click();
            await page.mouse.move(0, 0);

            await expect(tab).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tabpanel')).toHaveCount(1);
            await expect(page.getByRole('tabpanel', { name: 'Dashboard' })).toBeVisible();
        },
    });

    testState('selects a pill on click', {
        example: 'pills-tabs',
        state: 'settings',
        act: async (page) => {
            const tab = page.getByRole('tab', { name: 'Settings' });

            await tab.click();
            await page.mouse.move(0, 0);

            await expect(tab).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tab', { name: 'Dashboard' })).toHaveAttribute('aria-selected', 'false');
        },
    });

    testState('shows the panel of a clicked tab in a vertical list', {
        example: 'vertical',
        state: 'contact',
        act: async (page) => {
            await page.getByRole('tab', { name: 'Contact' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('heading', { name: 'Contact Tab' })).toBeVisible();
            await expect(page.getByRole('heading', { name: 'Profile Tab' })).toBeHidden();
        },
    });

    test('shows only the panel of the default tab on load', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/tabs/default');

        await expect(page.getByRole('tab', { name: 'Profile' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tab', { name: 'Profile' })).toHaveAttribute('data-state', 'active');
        await expect(page.getByRole('tabpanel')).toHaveCount(1);
        await expect(page.getByRole('tabpanel', { name: 'Profile' })).toBeVisible();
    });

    test('moves the selection from the previous tab to the clicked one', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/tabs/default');
        const profile = page.getByRole('tab', { name: 'Profile' });
        const settings = page.getByRole('tab', { name: 'Settings' });

        await settings.click();

        await expect(settings).toHaveAttribute('aria-selected', 'true');
        await expect(settings).toHaveAttribute('data-state', 'active');
        await expect(profile).toHaveAttribute('aria-selected', 'false');
        await expect(profile).toHaveAttribute('data-state', 'inactive');
        await expect(page.getByRole('tabpanel', { name: 'Settings' })).toBeVisible();
        await expect(page.getByRole('tabpanel', { name: 'Profile' })).toBeHidden();
    });

    test('selects a tab with Enter and Space', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/tabs/default');

        await page.getByRole('tab', { name: 'Contact' }).press('Enter');

        await expect(page.getByRole('tabpanel', { name: 'Contact' })).toBeVisible();

        await page.getByRole('tab', { name: 'Dashboard' }).press('Space');

        await expect(page.getByRole('tabpanel', { name: 'Dashboard' })).toBeVisible();
        await expect(page.getByRole('tabpanel')).toHaveCount(1);
    });

    test('keeps the disabled tab out of reach', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/tabs/tabs-with-underline');
        const disabled = page.getByRole('tab', { name: 'Disabled' });

        await expect(disabled).toBeDisabled();
        await expect(disabled).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByRole('tab', { name: 'Dashboard' })).toHaveAttribute('aria-selected', 'true');
    });

    test('selects a tab of a list without panels', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/tabs/tabs-with-icons');
        const contacts = page.getByRole('tab', { name: 'Contacts' });

        await contacts.click();

        await expect(contacts).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tab', { name: 'Dashboard' })).toHaveAttribute('aria-selected', 'false');
    });
});
