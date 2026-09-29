import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/toggle-group', () => {
    testState('presses several items of a multiple group', {
        example: 'default',
        state: 'pressed',
        act: async (page) => {
            const bold = page.getByRole('button', { name: 'Toggle bold' });
            const italic = page.getByRole('button', { name: 'Toggle italic' });

            await bold.click();
            await italic.click();
            await page.mouse.move(0, 0);

            await expect(bold).toHaveAttribute('aria-pressed', 'true');
            await expect(bold).toHaveAttribute('data-state', 'on');
            await expect(italic).toHaveAttribute('aria-pressed', 'true');
            await expect(italic).toHaveAttribute('data-state', 'on');
            await expect(page.getByRole('button', { name: 'Toggle strikethrough' })).toHaveAttribute(
                'aria-pressed',
                'false'
            );
        },
    });

    testState('moves the selection of a single group to the clicked item', {
        example: 'outline',
        state: 'selected',
        act: async (page) => {
            const all = page.getByRole('button', { name: 'Toggle all' });
            const missed = page.getByRole('button', { name: 'Toggle missed' });
            await expect(all).toHaveAttribute('aria-pressed', 'true');

            await missed.click();
            await page.mouse.move(0, 0);

            await expect(missed).toHaveAttribute('aria-pressed', 'true');
            await expect(missed).toHaveAttribute('data-state', 'on');
            await expect(all).toHaveAttribute('aria-pressed', 'false');
            await expect(all).toHaveAttribute('data-state', 'off');
        },
    });

    test('releases the pressed item of a single group when it is clicked again', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle-group/outline');
        const all = page.getByRole('button', { name: 'Toggle all' });

        await all.click();

        await expect(all).toHaveAttribute('aria-pressed', 'false');
        await expect(page.getByRole('button', { name: 'Toggle missed' })).toHaveAttribute('aria-pressed', 'false');
    });

    test('releases one item of a multiple group without touching the others', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle-group/vertical');
        const bold = page.getByRole('button', { name: 'Toggle bold' });

        await bold.click();

        await expect(bold).toHaveAttribute('aria-pressed', 'false');
        await expect(bold).toHaveAttribute('data-state', 'off');
        await expect(page.getByRole('button', { name: 'Toggle italic' })).toHaveAttribute('aria-pressed', 'true');
    });

    test('keeps the selection of each single group separate', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle-group/size');
        const [smallGroup, defaultGroup] = await page.getByRole('group').all();

        await smallGroup.getByRole('button', { name: 'Toggle left' }).click();

        await expect(smallGroup.getByRole('button', { name: 'Toggle left' })).toHaveAttribute('aria-pressed', 'true');
        await expect(smallGroup.getByRole('button', { name: 'Toggle top' })).toHaveAttribute('aria-pressed', 'false');
        await expect(defaultGroup.getByRole('button', { name: 'Toggle top' })).toHaveAttribute('aria-pressed', 'true');
        await expect(defaultGroup.getByRole('button', { name: 'Toggle left' })).toHaveAttribute(
            'aria-pressed',
            'false'
        );
    });

    test('toggles items with Enter and Space and moves between them with Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle-group/default');
        const bold = page.getByRole('button', { name: 'Toggle bold' });
        const italic = page.getByRole('button', { name: 'Toggle italic' });
        await bold.focus();

        await page.keyboard.press('Enter');
        await page.keyboard.press('Tab');
        await page.keyboard.press('Space');

        await expect(bold).toHaveAttribute('aria-pressed', 'true');
        await expect(italic).toBeFocused();
        await expect(italic).toHaveAttribute('aria-pressed', 'true');
    });

    test('selects an item of a single group with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle-group/outline');
        const missed = page.getByRole('button', { name: 'Toggle missed' });
        await missed.focus();

        await page.keyboard.press('Space');

        await expect(missed).toHaveAttribute('aria-pressed', 'true');
        await expect(page.getByRole('button', { name: 'Toggle all' })).toHaveAttribute('aria-pressed', 'false');
    });

    test('disables every item of a disabled group', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/toggle-group/disabled');
        const items = page.getByRole('group').getByRole('button');

        await expect(items).toHaveCount(3);
        for (const item of await items.all()) {
            await expect(item).toBeDisabled();
            await expect(item).toHaveAttribute('aria-pressed', 'false');
        }
    });
});
