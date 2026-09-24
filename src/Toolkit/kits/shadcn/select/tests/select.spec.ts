import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/select', () => {
    testState('opens on click and focuses the first item', {
        example: 'default',
        state: 'open',
        act: async (page) => {
            const trigger = page.getByRole('combobox');

            await trigger.click();

            await expect(page.getByRole('listbox')).toBeVisible();
            await expect(trigger).toHaveAttribute('aria-expanded', 'true');
            await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
        },
    });

    testState('opens the item-aligned popup with the selected item over the trigger', {
        example: 'align-item-with-trigger',
        state: 'item-aligned',
        act: async (page) => {
            const trigger = page.getByRole('combobox', { name: 'Item aligned' });

            await trigger.click();

            const banana = page.getByRole('option', { name: 'Banana', exact: true });
            await expect(banana).toBeFocused();
            const triggerBox = await trigger.boundingBox();
            const bananaBox = await banana.boundingBox();
            expect(Math.abs(bananaBox!.y - triggerBox!.y)).toBeLessThanOrEqual(4);
        },
    });

    testState('opens the popper popup below the trigger', {
        example: 'align-item-with-trigger',
        state: 'popper',
        act: async (page) => {
            const trigger = page.getByRole('combobox', { name: 'Popper' });

            await trigger.click();

            const listbox = page.getByRole('listbox');
            await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
            const triggerBox = await trigger.boundingBox();
            const listboxBox = await listbox.boundingBox();
            expect(listboxBox!.y).toBeGreaterThanOrEqual(triggerBox!.y + triggerBox!.height);
        },
    });

    testState('shows a scroll button when the list overflows', {
        example: 'scrollable',
        state: 'open',
        act: async (page) => {
            await page.getByRole('combobox').click();

            await expect(page.getByRole('option', { name: 'Eastern Standard Time', exact: true })).toBeFocused();
            await expect(page.locator('[data-slot="select-scroll-down-button"]')).toBeVisible();
            await expect(page.locator('[data-slot="select-scroll-up-button"]')).toBeHidden();
        },
    });

    testState('opens in right-to-left documents', {
        example: 'rtl',
        state: 'open',
        act: async (page) => {
            await page.getByRole('combobox').first().click();

            await expect(page.getByRole('option', { name: 'تفاح', exact: true })).toBeFocused();
        },
    });

    test('closes on Escape and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('Escape');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('closes on a click outside', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('listbox')).toBeVisible();

        await page.mouse.click(10, 500);

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('closes on Tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('Tab');

        await expect(page.getByRole('listbox')).toBeHidden();
    });

    test('selects an item with the mouse and gives focus back to the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        const changes = page.evaluate(
            () =>
                new Promise((resolve) => {
                    document.addEventListener('select:change', (event) => resolve(event.detail), { once: true });
                })
        );
        await trigger.click();

        await page.getByRole('option', { name: 'Blueberry', exact: true }).click();

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toBeFocused();
        await expect(trigger).toHaveText('Blueberry');
        await expect(trigger).toHaveAttribute('data-has-value', 'true');
        expect(await changes).toEqual({ value: 'blueberry', label: 'Blueberry' });

        await trigger.click();

        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toBeFocused();
        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toHaveAttribute(
            'aria-selected',
            'true'
        );
    });

    test('opens from the keyboard on the selected item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/align-item-with-trigger');
        const trigger = page.getByRole('combobox', { name: 'Popper' });

        for (const key of ['Enter', ' ', 'ArrowDown', 'ArrowUp']) {
            await trigger.focus();
            await page.keyboard.press(key);

            await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();

            await page.keyboard.press('Escape');
            await expect(page.getByRole('listbox')).toBeHidden();
        }
    });

    test('selects the focused item with Enter or Space', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('Enter');

        await expect(trigger).toHaveText('Banana');
        await expect(trigger).toBeFocused();

        await page.keyboard.press('Enter');
        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press(' ');

        await expect(page.getByRole('listbox')).toBeHidden();
        await expect(trigger).toHaveText('Blueberry');
    });

    test('moves between items with the arrow keys, Home and End, without wrapping', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/groups');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowUp');

        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');
        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('option', { name: 'Carrot', exact: true })).toBeFocused();

        await page.keyboard.press('End');

        await expect(page.getByRole('option', { name: 'Spinach', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('option', { name: 'Spinach', exact: true })).toBeFocused();

        await page.keyboard.press('Home');

        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();
    });

    test('moves the focus to the item under the pointer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.getByRole('option', { name: 'Blueberry', exact: true }).hover();

        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toBeFocused();

        await page.keyboard.press('ArrowDown');

        await expect(page.getByRole('option', { name: 'Grapes', exact: true })).toBeFocused();
    });

    test('focuses the first item matching the typed letters, cycling on a repeated letter', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/select/groups');
        await page.getByRole('combobox').click();
        await expect(page.getByRole('option', { name: 'Apple', exact: true })).toBeFocused();

        await page.keyboard.press('b');

        await expect(page.getByRole('option', { name: 'Banana', exact: true })).toBeFocused();

        await page.keyboard.press('b');

        await expect(page.getByRole('option', { name: 'Blueberry', exact: true })).toBeFocused();

        await page.keyboard.press('b');

        await expect(page.getByRole('option', { name: 'Broccoli', exact: true })).toBeFocused();
    });

    test('selects the item matching the letters typed on the closed trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');
        await trigger.focus();

        await page.keyboard.press('g');

        await expect(trigger).toHaveText('Grapes');
        await expect(trigger).toHaveAttribute('aria-expanded', 'false');
        await expect(trigger).toBeFocused();
    });

    test('names the trigger after its field label', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/invalid');

        const trigger = page.getByRole('combobox', { name: 'Fruit' });

        await expect(trigger).toBeVisible();
        await expect(trigger).toHaveAccessibleDescription('Please select a fruit.');
    });

    test('points the trigger at the listbox it controls', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/default');
        const trigger = page.getByRole('combobox');

        await trigger.click();

        const listboxId = await page.getByRole('listbox').getAttribute('id');
        expect(listboxId).not.toBeNull();
        await expect(trigger).toHaveAttribute('aria-controls', listboxId!);
    });

    test('requires a value when required', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/invalid');
        const input = page.locator('[name="fruit"]');
        expect(await input.evaluate((element: HTMLInputElement) => element.checkValidity())).toBe(false);

        await page.getByRole('combobox', { name: 'Fruit' }).click();
        await page.getByRole('option', { name: 'Banana', exact: true }).click();

        await expect(input).toHaveValue('banana');
        expect(await input.evaluate((element: HTMLInputElement) => element.checkValidity())).toBe(true);
    });

    test('places the selected item of a scrolled list over the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/scrollable');
        const trigger = page.getByRole('combobox');
        await trigger.click();
        await page.getByRole('option', { name: 'Fiji Time', exact: true }).click();
        await expect(trigger).toHaveText('Fiji Time');

        await trigger.click();

        const fiji = page.getByRole('option', { name: 'Fiji Time', exact: true });
        await expect(fiji).toBeFocused();
        const triggerBox = await trigger.boundingBox();
        const fijiBox = await fiji.boundingBox();
        expect(Math.abs(fijiBox!.y - triggerBox!.y)).toBeLessThanOrEqual(4);
    });

    test('widens the popup to fit items longer than the trigger', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/scrollable');
        await page.locator('[data-slot="select"]').evaluate((select: HTMLElement) => (select.style.width = '8rem'));

        await page.getByRole('combobox').click();

        await expect(page.getByRole('option', { name: 'Eastern Standard Time', exact: true })).toBeFocused();
        const heights = await page.evaluate(() =>
            ['est', 'gmt'].map(
                (value) =>
                    document.querySelector<HTMLElement>(`[data-slot="select-item"][data-value="${value}"]`)!
                        .offsetHeight
            )
        );
        expect(heights[0]).toBe(heights[1]);
    });

    test('disables the trigger and the disabled item', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/select/disabled');

        await expect(page.getByRole('combobox')).toBeDisabled();
        await expect(page.locator('[data-slot="select-item"][data-value="grapes"]')).toHaveAttribute(
            'aria-disabled',
            'true'
        );
    });
});
