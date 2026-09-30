import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/combobox', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const input = page.getByRole('combobox');

            await input.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(input).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('option')).toHaveCount(5);
        },
    });

    testState('filters the items as you type', {
        example: 'default',
        state: 'filtered',
        act: async (page) => {
            const input = page.getByRole('combobox');
            await input.click();

            await input.fill('xt');

            await expect(page.getByRole('option')).toHaveText(['Next.js', 'Nuxt.js']);
            await expect(input).not.toHaveAttribute('aria-activedescendant');
        },
    });

    testState('shows the empty message when nothing matches', {
        example: 'default',
        state: 'empty',
        act: async (page) => {
            const input = page.getByRole('combobox');
            await input.click();

            await input.fill('angular');

            await expect(page.getByText('No items found.')).toBeVisible();
            await expect(page.getByRole('option')).toHaveCount(0);
        },
    });

    test('does not open on focus', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');

        await input.focus();

        await expect(input).toBeFocused();
        await expect(input).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('selects an item with the mouse', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();

        await page.getByRole('option', { name: 'Remix' }).click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('Remix');
        await expect(input).toHaveAttribute('aria-expanded', 'false');
        await expect(input).toBeFocused();
    });

    test('moves through the items with the keyboard and selects with Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        const itemId = (index: number) => `combobox-framework-demo-list-item-${index}`;
        await input.focus();

        await input.press('ArrowDown');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(0));

        await input.press('ArrowDown');
        await input.press('ArrowDown');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(2));

        await input.press('ArrowUp');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(1));

        await input.press('End');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(4));

        await input.press('ArrowDown');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(4));

        await input.press('Home');
        await expect(input).toHaveAttribute('aria-activedescendant', itemId(0));

        await input.press('ArrowDown');
        await input.press('Enter');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('SvelteKit');
        await expect(input).toBeFocused();
        await expect(input).not.toHaveAttribute('aria-activedescendant');
    });

    test('opens on the last item with ArrowUp', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.focus();

        await input.press('ArrowUp');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(input).toHaveAttribute('aria-activedescendant', 'combobox-framework-demo-list-item-4');
    });

    test('highlights the first match while filtering with autoHighlight', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/auto-highlight');
        const input = page.getByRole('combobox');
        await input.click();

        await input.fill('r');

        await expect(page.getByRole('option')).toHaveText(['Remix', 'Astro']);
        await expect(input).toHaveAttribute('aria-activedescendant', 'combobox-framework-auto-highlight-list-item-3');

        await input.press('Enter');

        await expect(input).toHaveValue('Remix');
    });

    test('clears the typed text on Escape', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        await input.fill('zzz');

        await input.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('');
        await expect(input).toBeFocused();
    });

    test('restores the selection when it closes without a new choice', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/clear-button');
        const input = page.getByRole('combobox');
        await input.click();
        await input.fill('zzz');

        await page.mouse.click(700, 500);

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(input).toHaveValue('Next.js');
    });

    test('closes on a click on the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('button', { name: 'Toggle the list' });
        await trigger.click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await trigger.click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.mouse.click(700, 500);

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('resets the filter when it opens again', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const input = page.getByRole('combobox');
        await input.click();
        await input.fill('remix');
        await expect(page.getByRole('option')).toHaveCount(1);
        await input.press('Escape');

        await input.click();

        await expect(page.getByRole('option')).toHaveCount(5);
    });

    test('hides a group when none of its items match', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/groups');
        const input = page.getByRole('combobox');
        await input.click();
        await expect(page.getByRole('group', { name: /Americas|Europe|Asia/ })).toHaveCount(3);

        await input.fill('paris');

        await expect(page.getByRole('group', { name: /Americas|Europe|Asia/ })).toHaveCount(1);
        await expect(page.getByRole('group', { name: 'Europe' })).toBeVisible();
        await expect(page.getByRole('option')).toHaveText(['(GMT+1) Paris']);
    });

    testState('marks the selected item', {
        example: 'clear-button',
        state: 'open',
        act: async (page) => {
            await page.getByRole('combobox').click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(page.getByRole('option', { selected: true })).toHaveText('Next.js');
        },
    });

    test('clears the selection with the clear button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/clear-button');
        const input = page.getByRole('combobox');
        const clear = page.getByRole('button', { name: 'Clear selection' });
        const trigger = page.getByRole('button', { name: 'Toggle the list' });
        const hiddenInput = page.locator('input[type="hidden"][name="framework"]');
        await expect(input).toHaveValue('Next.js');
        await expect(hiddenInput).toHaveValue('Next.js');
        await expect(clear).toBeVisible();
        await expect(trigger).toBeHidden();

        await clear.click();

        await expect(input).toHaveValue('');
        await expect(hiddenInput).toHaveValue('');
        await expect(clear).toBeHidden();
        await expect(trigger).toBeVisible();
        await expect(input).toBeFocused();
    });

    test('does not open when disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/disabled');
        const input = page.getByRole('combobox');

        await input.click({ force: true });

        await expect(input).toBeDisabled();
        await expect(input).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('lines the list up with its input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const content = page.locator('[data-slot="combobox-content"]');
        const groupBox = await page.locator('[data-slot="input-group"]').boundingBox();

        await page.getByRole('combobox').click();

        // The content grows from 95% while it opens.
        await expect.poll(async () => (await content.boundingBox())?.width).toBeCloseTo(groupBox?.width ?? 0, 0);
        expect((await content.boundingBox())?.x).toBeCloseTo(groupBox?.x ?? 0, 0);
    });

    testState('adds the selected items as chips', {
        example: 'multiple',
        state: 'selected',
        act: async (page) => {
            await page.getByRole('combobox').click();

            await page.getByRole('option', { name: 'Remix' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(page.locator('[data-slot="combobox-chips"] [data-slot="combobox-chip"]')).toHaveText([
                'Next.js',
                'Remix',
            ]);
            await expect(page.getByRole('option', { selected: true })).toHaveText(['Next.js', 'Remix']);
            await expect(page.locator('input[type="hidden"][name="frameworks[]"]')).toHaveCount(2);
        },
    });

    test('removes a chip with its button and with Backspace', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/multiple');
        const input = page.getByRole('combobox');
        const chips = page.locator('[data-slot="combobox-chips"] [data-slot="combobox-chip"]');
        await input.click();
        await page.getByRole('option', { name: 'Astro' }).click();
        await expect(chips).toHaveText(['Next.js', 'Astro']);

        await page.getByRole('button', { name: 'Remove' }).first().click();

        await expect(chips).toHaveText(['Astro']);
        await expect(input).toBeFocused();

        await input.press('Backspace');

        await expect(chips).toHaveCount(0);
        await expect(page.locator('input[type="hidden"][name="frameworks[]"]')).toHaveCount(0);
    });

    testState('opens from a button', {
        example: 'popup',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('button', { name: 'Select country' });

            await trigger.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('combobox')).toBeFocused();
        },
    });

    test('gives the focus back to the button of a popup', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/popup');
        const search = page.getByRole('combobox');
        await page.getByRole('button', { name: 'Select country' }).click();
        await expect(search).toBeFocused();

        await search.fill('fr');
        await search.press('ArrowDown');
        await search.press('Enter');

        const trigger = page.getByRole('button', { name: 'France' });
        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toBeFocused();

        await trigger.click();
        await expect(search).toBeFocused();
        await expect(search).toHaveValue('');

        await search.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toBeFocused();
    });
});
