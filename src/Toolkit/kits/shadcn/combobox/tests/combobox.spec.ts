import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/combobox', () => {
    testState('opens on click', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('combobox');

            await trigger.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('searchbox')).toBeFocused();
            await expect(page.getByRole('option')).toHaveCount(5);
        },
    });

    testState('filters the options as you type', {
        example: 'default',
        state: 'filtered',
        act: async (page) => {
            await page.getByRole('combobox').click();
            const search = page.getByRole('searchbox');
            await expect(search).toBeFocused();

            await search.fill('component');
            await page.mouse.move(0, 0);

            await expect(page.getByRole('option')).toHaveText(['UX Twig Component', 'UX Live Component']);
            await expect(search).toHaveAttribute('aria-activedescendant', 'package_option_1');
        },
    });

    testState('shows the empty message when nothing matches', {
        example: 'empty-state',
        state: 'empty',
        act: async (page) => {
            await page.getByRole('combobox').click();
            const search = page.getByRole('searchbox');
            await expect(search).toBeFocused();

            await search.fill('angular');

            await expect(page.getByText('No frameworks found. Try a different search.')).toBeVisible();
            await expect(page.getByRole('option')).toHaveCount(0);
            await expect(search).not.toHaveAttribute('aria-activedescendant');
        },
    });

    test('hides the empty message again when the filter matches', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/empty-state');
        await page.getByRole('combobox').click();
        const search = page.getByRole('searchbox');
        await search.fill('angular');
        await expect(page.getByText('No frameworks found. Try a different search.')).toBeVisible();

        await search.fill('svelte');

        await expect(page.getByText('No frameworks found. Try a different search.')).toBeHidden();
        await expect(page.getByRole('option')).toHaveText(['SvelteKit']);
    });

    test('hides a group when none of its options match', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/with-groups');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('group')).toHaveCount(3);

        await page.getByRole('searchbox').fill('svelte');

        await expect(page.getByRole('group')).toHaveCount(1);
        await expect(page.getByRole('group', { name: 'Frontend Frameworks' })).toBeVisible();
        await expect(page.getByRole('option')).toHaveText(['UX Svelte']);
    });

    test('selects an option with the mouse', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/with-form');
        const trigger = page.getByRole('combobox');
        const hiddenInput = page.locator('input[type="hidden"][name="package"]');
        await expect(hiddenInput).toHaveValue('');
        await trigger.click();

        await page.getByRole('option', { name: 'UX Turbo' }).click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveText('UX Turbo');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
        await expect(hiddenInput).toHaveValue('turbo');

        await trigger.click();

        await expect(page.getByRole('option', { name: 'UX Turbo' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('option', { name: 'UX Icons' })).toHaveAttribute('aria-selected', 'false');
    });

    testState('marks the selected option', {
        example: 'with-default-value',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('combobox');
            await expect(trigger).toHaveText('UX Live Component');

            await trigger.click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(page.getByRole('option', { selected: true })).toHaveText('UX Live Component');
        },
    });

    test('moves through the options with the keyboard and selects with Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/with-form');
        const trigger = page.getByRole('combobox');
        const search = page.getByRole('searchbox');
        await trigger.focus();

        await trigger.press('ArrowDown');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(search).toBeFocused();
        await expect(search).toHaveAttribute('aria-activedescendant', 'package-form_option_0');

        await search.press('ArrowDown');
        await search.press('ArrowDown');
        await expect(search).toHaveAttribute('aria-activedescendant', 'package-form_option_2');

        await search.press('ArrowUp');
        await expect(search).toHaveAttribute('aria-activedescendant', 'package-form_option_1');

        await search.press('End');
        await expect(search).toHaveAttribute('aria-activedescendant', 'package-form_option_4');

        await search.press('ArrowDown');
        await expect(search).toHaveAttribute('aria-activedescendant', 'package-form_option_4');

        await search.press('Home');
        await expect(search).toHaveAttribute('aria-activedescendant', 'package-form_option_0');

        await search.press('ArrowDown');
        await search.press('Enter');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveText('UX Twig Component');
        await expect(trigger).toBeFocused();
        await expect(page.locator('input[type="hidden"][name="package"]')).toHaveValue('twig-component');
    });

    test('opens on the last option with ArrowUp', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');
        await trigger.focus();

        await trigger.press('ArrowUp');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(page.getByRole('searchbox')).toHaveAttribute('aria-activedescendant', 'package_option_4');
    });

    test('opens with Enter without highlighting an option', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');
        await trigger.focus();

        await trigger.press('Enter');

        await expect(page.getByRole('listbox')).toBeVisible();
        await expect(page.getByRole('searchbox')).toBeFocused();
        await expect(page.getByRole('searchbox')).not.toHaveAttribute('aria-activedescendant');
    });

    test('selects the first match of the filter with Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        const search = page.getByRole('searchbox');
        await expect(search).toBeFocused();

        await search.fill('icons');
        await search.press('Enter');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveText('UX Icons');
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await expect(page.getByRole('searchbox')).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveText('Select package...');
    });

    test('closes on a click on its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');
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

        await page.mouse.click(600, 400);

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('resets the filter when it opens again', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await page.getByRole('searchbox').fill('turbo');
        await expect(page.getByRole('option')).toHaveCount(1);
        await page.keyboard.press('Escape');

        await trigger.click();

        await expect(page.getByRole('searchbox')).toHaveValue('');
        await expect(page.getByRole('option')).toHaveCount(5);
    });

    test('clears the selection with the clear button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/clearable');
        const trigger = page.getByRole('combobox');
        const clear = page.getByLabel('Clear selection');
        await expect(trigger).toHaveText('UX Live Component');
        await expect(clear).toBeVisible();

        await clear.click();

        await expect(trigger).toHaveText('Select package...');
        await expect(clear).toBeHidden();
        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('does not open when disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/disabled');
        const trigger = page.getByRole('combobox');

        await trigger.click({ force: true });

        await expect(trigger).toBeDisabled();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('lines the list up with its trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/combobox/default');
        const trigger = page.getByRole('combobox');

        await trigger.click();

        const triggerBox = await trigger.boundingBox();
        const listBox = await page.getByRole('listbox').boundingBox();
        expect(Math.abs((listBox?.x ?? 0) - (triggerBox?.x ?? 0))).toBeLessThanOrEqual(2);
        expect(Math.abs((listBox?.width ?? 0) - (triggerBox?.width ?? 0))).toBeLessThanOrEqual(2);
    });
});
