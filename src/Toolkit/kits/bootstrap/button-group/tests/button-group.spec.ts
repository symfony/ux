import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/button-group', () => {
    testState('toggles checkboxes and selects a radio', {
        example: 'checkbox-and-radio-button-groups',
        state: 'checked',
        act: async (page) => {
            await page.getByText('Checkbox 1', { exact: true }).click();
            await page.getByText('Checkbox 3', { exact: true }).click();
            await page.getByText('Radio 2', { exact: true }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('checkbox', { name: 'Checkbox 1' })).toBeChecked();
            await expect(page.getByRole('checkbox', { name: 'Checkbox 2' })).not.toBeChecked();
            await expect(page.getByRole('checkbox', { name: 'Checkbox 3' })).toBeChecked();
            await expect(page.getByRole('radio', { name: 'Radio 1' })).not.toBeChecked();
            await expect(page.getByRole('radio', { name: 'Radio 2' })).toBeChecked();
        },
    });

    test('unchecks a checkbox on a second click', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button-group/checkbox-and-radio-button-groups');
        const label = page.getByText('Checkbox 2', { exact: true });
        await label.click();
        await expect(page.getByRole('checkbox', { name: 'Checkbox 2' })).toBeChecked();

        await label.click();

        await expect(page.getByRole('checkbox', { name: 'Checkbox 2' })).not.toBeChecked();
    });

    test('moves the radio selection with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button-group/vertical-variation');
        const first = page.getByRole('radio', { name: 'Radio 1' });
        await first.focus();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('radio', { name: 'Radio 2' })).toBeChecked();
        await expect(page.getByRole('radio', { name: 'Radio 2' })).toBeFocused();
        await expect(first).not.toBeChecked();
    });

    testState('opens the nested dropdown on click', {
        example: 'nesting',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Dropdown' });

            await trigger.click();

            await expect(page.getByRole('link', { name: 'Dropdown link' }).first()).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    test('closes the nested dropdown on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button-group/nesting');
        const trigger = page.getByRole('button', { name: 'Dropdown' });
        await trigger.click();
        await expect(page.getByRole('link', { name: 'Dropdown link' }).first()).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('link', { name: 'Dropdown link' }).first()).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes the nested dropdown on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button-group/nesting');
        const trigger = page.getByRole('button', { name: 'Dropdown' });
        await trigger.click();
        await expect(page.getByRole('link', { name: 'Dropdown link' }).first()).toBeVisible();

        await page.mouse.click(600, 400);

        await expect(page.getByRole('link', { name: 'Dropdown link' }).first()).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens the nested dropdown with ArrowDown and moves through its links', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/button-group/nesting');
        const links = page.getByRole('link', { name: 'Dropdown link' });
        await page.getByRole('button', { name: 'Dropdown' }).focus();

        await page.keyboard.press('ArrowDown');

        await expect(links.first()).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(links.nth(1)).toBeFocused();
    });

    testState('opens a dropend menu in the vertical group', {
        example: 'vertical-variation',
        state: 'dropend-open',
        act: async (page) => {
            const group = page.getByRole('group', { name: 'Dropend menu' });
            const trigger = group.getByRole('button', { name: 'Dropdown' });

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(group.getByRole('link', { name: 'Dropdown link' }).first()).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });
});
