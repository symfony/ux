import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/list-group', () => {
    testState('shows the panel of a clicked item', {
        example: 'javascript-behavior',
        state: 'profile-selected',
        act: async (page) => {
            const profile = page.getByRole('tab', { name: 'Profile' });

            await profile.click();
            await page.mouse.move(0, 0);

            await expect(profile).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tab', { name: 'Home' })).toHaveAttribute('aria-selected', 'false');
            await expect(page.getByText('Some placeholder content relating to Profile.')).toBeVisible();
            await expect(page.getByText('Some placeholder content relating to Home.')).toBeHidden();
        },
    });

    test('selects the first item on load', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/list-group/javascript-behavior');

        await expect(page.getByRole('tab', { name: 'Home' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel', { name: 'Home' })).toBeVisible();
        await expect(page.getByText('Some placeholder content relating to Profile.')).toBeHidden();
    });

    test('moves the selection with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/list-group/javascript-behavior');
        const home = page.getByRole('tab', { name: 'Home' });
        const profile = page.getByRole('tab', { name: 'Profile' });
        await home.focus();

        await page.keyboard.press('ArrowDown');

        await expect(profile).toBeFocused();
        await expect(profile).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel', { name: 'Profile' })).toBeVisible();

        await page.keyboard.press('ArrowUp');

        await expect(home).toBeFocused();
        await expect(home).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel', { name: 'Home' })).toBeVisible();
    });

    test('jumps to the last and first items with End and Home', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/list-group/javascript-behavior');
        const home = page.getByRole('tab', { name: 'Home' });
        const settings = page.getByRole('tab', { name: 'Settings' });
        await home.focus();

        await page.keyboard.press('End');

        await expect(settings).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Settings' })).toBeVisible();

        await page.keyboard.press('Home');

        await expect(home).toBeFocused();
        await expect(page.getByRole('tabpanel', { name: 'Home' })).toBeVisible();
    });

    test('keeps only the selected item in the tab order', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/list-group/javascript-behavior');

        await page.getByRole('tab', { name: 'Messages' }).click();

        await expect(page.getByRole('tab', { name: 'Messages' })).not.toHaveAttribute('tabindex', '-1');
        await expect(page.getByRole('tab', { name: 'Home' })).toHaveAttribute('tabindex', '-1');
        await expect(page.getByRole('tab', { name: 'Profile' })).toHaveAttribute('tabindex', '-1');
        await expect(page.getByRole('tab', { name: 'Settings' })).toHaveAttribute('tabindex', '-1');
    });

    testState('checks checkboxes and radios', {
        example: 'checkboxes-and-radios',
        state: 'checked',
        act: async (page) => {
            const checkbox = page.getByRole('checkbox', { name: 'Second checkbox' }).first();
            const radio = page.getByRole('radio', { name: 'Third radio' });

            await page.getByText('Second checkbox').first().click();
            await radio.click();
            await page.mouse.move(0, 0);

            await expect(checkbox).toBeChecked();
            await expect(radio).toBeChecked();
            await expect(page.getByRole('radio', { name: 'First radio' })).not.toBeChecked();
        },
    });

    test('toggles a stretched checkbox from anywhere in its item', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/list-group/checkboxes-and-radios');
        const checkbox = page.getByRole('checkbox', { name: 'Third checkbox' }).last();
        const box = await page.getByRole('listitem').last().boundingBox();

        await page.mouse.click(box!.x + box!.width - 10, box!.y + box!.height / 2);

        await expect(checkbox).toBeChecked();

        await page.mouse.click(box!.x + box!.width - 10, box!.y + box!.height / 2);

        await expect(checkbox).not.toBeChecked();
    });
});
