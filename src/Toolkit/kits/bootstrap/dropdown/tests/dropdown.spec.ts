import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/dropdown', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Dropdown button' });

            await trigger.click();

            await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        },
    });

    testState('opens the dark menu on click', {
        example: 'dark-dropdowns',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Dropdown button' }).click();

            await expect(page.getByRole('link', { name: 'Separated link' })).toBeVisible();
        },
    });

    test('closes on a click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await trigger.click();
        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await trigger.click();
        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeVisible();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await trigger.click();
        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeVisible();

        await page.mouse.click(5, 5);

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click on an item', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await trigger.click();

        await page.getByRole('link', { name: 'Another action' }).click();

        await expect(page.getByRole('link', { name: 'Another action' })).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('opens with ArrowDown and moves between items with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/dropdown/default');
        const trigger = page.getByRole('button', { name: 'Dropdown button' });
        await trigger.focus();

        await page.keyboard.press('ArrowDown');

        await expect(trigger).toHaveAttribute('aria-expanded', 'true');
        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('link', { name: 'Another action' })).toBeFocused();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('link', { name: 'Action', exact: true })).toBeFocused();
    });

    test('stays open on an item click when it only closes on outside clicks', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/dropdown/auto-close-behavior');
        const trigger = page.getByRole('button', { name: 'Clickable outside' });
        await trigger.click();
        const item = page.getByRole('link', { name: 'Menu item' }).first();

        await item.click();

        await expect(item).toBeVisible();
        await expect(trigger).toHaveAttribute('aria-expanded', 'true');

        await page.mouse.click(5, 5);

        await expect(item).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });
});
