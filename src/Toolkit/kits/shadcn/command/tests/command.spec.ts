import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/command', () => {
    testState('filters the items as you type', {
        example: 'default',
        state: 'filtered',
        act: async (page) => {
            const input = page.getByRole('combobox');

            await input.fill('se');

            await expect(page.getByRole('option')).toHaveText([/Search Emoji/, /Settings/]);
            await expect(page.getByRole('separator')).toBeHidden();
            await expect(page.getByRole('option', { name: 'Search Emoji' })).toHaveAttribute('aria-selected', 'true');
        },
    });

    testState('shows the empty message when nothing matches', {
        example: 'default',
        state: 'empty',
        act: async (page) => {
            await page.getByRole('combobox').fill('xyz');

            await expect(page.getByText('No results found.')).toBeVisible();
            await expect(page.getByRole('option')).toHaveCount(0);
            await expect(page.getByRole('combobox')).not.toHaveAttribute('aria-activedescendant');
        },
    });

    test('selects the first item on load', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/default');
        const input = page.getByRole('combobox');
        const calendar = page.getByRole('option', { name: 'Calendar' });

        await expect(calendar).toHaveAttribute('aria-selected', 'true');
        await expect(input).toHaveAttribute('aria-activedescendant', (await calendar.getAttribute('id')) ?? '');
        await expect(input).toHaveAttribute(
            'aria-controls',
            (await page.getByRole('listbox').getAttribute('id')) ?? ''
        );
    });

    test('moves the selection with the keyboard and skips disabled items', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/default');
        const input = page.getByRole('combobox');
        await input.focus();

        await input.press('ArrowDown');
        await input.press('ArrowDown');
        await expect(page.getByRole('option', { name: 'Profile' })).toHaveAttribute('aria-selected', 'true');

        await input.press('End');
        await expect(page.getByRole('option', { name: 'Settings' })).toHaveAttribute('aria-selected', 'true');

        await input.press('ArrowDown');
        await expect(page.getByRole('option', { name: 'Settings' })).toHaveAttribute('aria-selected', 'true');

        await input.press('Home');
        await expect(page.getByRole('option', { name: 'Calendar' })).toHaveAttribute('aria-selected', 'true');

        await input.press('ArrowUp');
        await expect(page.getByRole('option', { name: 'Calendar' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.locator('[role="option"][aria-selected="true"]')).toHaveCount(1);
    });

    test('jumps between groups with Alt and the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/default');
        const input = page.getByRole('combobox');
        await input.focus();

        await input.press('Alt+ArrowDown');
        await expect(page.getByRole('option', { name: 'Profile' })).toHaveAttribute('aria-selected', 'true');

        await input.press('Alt+ArrowUp');
        await expect(page.getByRole('option', { name: 'Calendar' })).toHaveAttribute('aria-selected', 'true');
    });

    test('selects the item under the pointer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/default');

        await page.getByRole('option', { name: 'Billing' }).hover();

        await expect(page.getByRole('option', { name: 'Billing' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('option', { name: 'Calendar' })).toHaveAttribute('aria-selected', 'false');
    });

    test('gives an id to an item added later', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/default');
        await page.getByRole('option', { name: 'Billing' }).evaluate((billing) => {
            const invoices = billing.cloneNode(true) as HTMLElement;
            invoices.removeAttribute('id');
            invoices.querySelector('span')!.textContent = 'Invoices';
            billing.after(invoices);
        });
        const input = page.getByRole('combobox');

        await input.fill('invoices');

        const invoices = page.getByRole('option', { name: 'Invoices' });
        await expect(invoices).toHaveAttribute('id', /.+/);
        await expect(input).toHaveAttribute('aria-activedescendant', (await invoices.getAttribute('id')) ?? '');
    });

    test('clicks the selected item on Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/default');
        const profile = page.getByRole('option', { name: 'Profile' });
        await profile.evaluate((item) => item.addEventListener('click', () => item.setAttribute('data-clicked', '')));
        const input = page.getByRole('combobox');
        await input.fill('prof');

        await input.press('Enter');

        await expect(profile).toHaveAttribute('data-clicked');
    });

    test('follows the link of an item on Enter', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/links');
        const input = page.getByRole('combobox');
        await input.fill('inbox');

        await input.press('Enter');

        await expect(page).toHaveURL(/#inbox$/);
    });

    test('keeps the link items out of the tab order', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/links');
        const input = page.getByRole('combobox');
        await input.focus();

        await input.press('Tab');

        await expect(page.getByRole('option', { name: 'Home' })).not.toBeFocused();
    });

    testState('opens in a dialog', {
        example: 'basic',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open Menu' }).click();

            await expect(page.getByRole('dialog', { name: 'Command Palette' })).toBeVisible();
            await expect(page.getByRole('combobox')).toBeFocused();
            await expect(page.getByRole('option', { name: 'Calendar' })).toHaveAttribute('aria-selected', 'true');
        },
    });

    testState('scrolls a long list in a dialog', {
        example: 'scrollable',
        state: 'open',
        act: async (page) => {
            await page.getByRole('button', { name: 'Open Menu' }).click();
            const input = page.getByRole('combobox');
            await expect(input).toBeFocused();

            await input.press('End');

            const codeEditor = page.getByRole('option', { name: 'Code Editor' });
            await expect(codeEditor).toHaveAttribute('aria-selected', 'true');
            await page
                .getByRole('dialog')
                .evaluate((dialog) => Promise.all(dialog.getAnimations().map((animation) => animation.finished)));
            const gap = await codeEditor.evaluate(
                (item) =>
                    item.closest('[role="listbox"]')!.getBoundingClientRect().bottom -
                    item.getBoundingClientRect().bottom
            );
            expect(Math.abs(gap)).toBeLessThan(1);
        },
    });

    test('does not scroll the list to the item under the pointer', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/scrollable');
        await page.getByRole('button', { name: 'Open Menu' }).click();
        await expect(page.getByRole('combobox')).toBeFocused();
        await page
            .getByRole('dialog')
            .evaluate((dialog) => Promise.all(dialog.getAnimations().map((animation) => animation.finished)));
        const list = page.getByRole('listbox');
        const box = (await list.boundingBox())!;

        await page.mouse.move(box.x + box.width / 2, box.y + box.height - 2);

        await expect(page.getByRole('option', { name: 'Copy' })).toHaveAttribute('aria-selected', 'true');
        await expect(list).toHaveJSProperty('scrollTop', 0);
    });

    test('starts over when the dialog is opened again', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/basic');
        const trigger = page.getByRole('button', { name: 'Open Menu' });
        await trigger.click();
        const input = page.getByRole('combobox');
        await input.fill('emoji');
        await expect(page.getByRole('option')).toHaveCount(1);

        await input.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();
        await trigger.click();

        await expect(input).toHaveValue('');
        await expect(page.getByRole('option')).toHaveCount(3);
        await expect(page.getByRole('option', { name: 'Calendar' })).toHaveAttribute('aria-selected', 'true');
    });

    test('scrolls a long list back to the top when the dialog is opened again', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/command/scrollable');
        const trigger = page.getByRole('button', { name: 'Open Menu' });
        await trigger.click();
        const input = page.getByRole('combobox');
        await expect(input).toBeFocused();
        await input.press('End');
        await expect(page.getByRole('option', { name: 'Code Editor' })).toBeInViewport();

        await input.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();
        await trigger.click();

        await expect(page.getByRole('option', { name: 'Home' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('option', { name: 'Home' })).toBeInViewport();
        await expect(page.getByRole('listbox')).toHaveJSProperty('scrollTop', 0);
    });
});
