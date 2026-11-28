import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('daisyui-5/tabs', () => {
    testState('shows the panel of the clicked tab', {
        example: 'lift-with-content',
        state: 'tab-3',
        act: async (page) => {
            const tab = page.getByRole('tab', { name: 'Tab 3' });

            await tab.click();
            await page.mouse.move(0, 0);

            await expect(tab).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('aria-selected', 'false');
            await expect(page.getByRole('tabpanel')).toHaveCount(1);
            await expect(page.getByRole('tabpanel', { name: 'Tab 3' })).toHaveText('Tab content 3');
        },
    });

    testState('selects the next tab with the right arrow key', {
        example: 'box-with-content',
        state: 'tab-3',
        act: async (page) => {
            await page.getByRole('tab', { name: 'Tab 2' }).press('ArrowRight');

            const tab = page.getByRole('tab', { name: 'Tab 3' });
            await expect(tab).toBeFocused();
            await expect(tab).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('aria-selected', 'false');
            await expect(page.getByRole('tabpanel', { name: 'Tab 3' })).toHaveText('Tab content 3');
        },
    });

    test('shows only the panel of the default tab on load', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/lift-with-content');

        await expect(page.getByRole('tablist')).toHaveCount(1);
        await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel')).toHaveCount(1);
        await expect(page.getByRole('tabpanel', { name: 'Tab 2' })).toHaveText('Tab content 2');
    });

    test('moves the selection with the arrow, Home and End keys', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/lift-with-content');
        const tab1 = page.getByRole('tab', { name: 'Tab 1' });
        const tab2 = page.getByRole('tab', { name: 'Tab 2' });
        const tab3 = page.getByRole('tab', { name: 'Tab 3' });

        await tab2.press('ArrowLeft');
        await expect(tab1).toBeFocused();
        await expect(tab1).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel', { name: 'Tab 1' })).toBeVisible();

        await page.keyboard.press('ArrowLeft');
        await expect(tab3).toBeFocused();
        await expect(tab3).toHaveAttribute('aria-selected', 'true');

        await page.keyboard.press('ArrowRight');
        await expect(tab1).toBeFocused();

        await page.keyboard.press('End');
        await expect(tab3).toBeFocused();
        await expect(tab3).toHaveAttribute('aria-selected', 'true');

        await page.keyboard.press('Home');
        await expect(tab1).toBeFocused();
        await expect(tab1).toHaveAttribute('aria-selected', 'true');
        await expect(tab2).toHaveAttribute('aria-selected', 'false');
        await expect(tab3).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByRole('tabpanel')).toHaveCount(1);
        await expect(page.getByRole('tabpanel', { name: 'Tab 1' })).toHaveText('Tab content 1');
    });

    test('keeps only the selected tab in the Tab sequence', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/lift-with-content');

        await page.keyboard.press('Tab');
        await expect(page.getByRole('tab', { name: 'Tab 2' })).toBeFocused();

        await page.keyboard.press('Tab');
        await expect(page.getByRole('tabpanel', { name: 'Tab 2' })).toBeFocused();

        await page.getByRole('tab', { name: 'Tab 3' }).click();
        await expect(page.getByRole('tab', { name: 'Tab 3' })).toHaveAttribute('tabindex', '0');
        await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('tabindex', '-1');
    });

    test('follows the reading direction with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/rtl');
        const first = page.getByRole('tab', { name: 'التبويب 1' });
        const second = page.getByRole('tab', { name: 'التبويب 2' });

        await second.press('ArrowRight');
        await expect(first).toBeFocused();
        await expect(first).toHaveAttribute('aria-selected', 'true');

        await first.press('ArrowLeft');
        await expect(second).toBeFocused();
        await expect(second).toHaveAttribute('aria-selected', 'true');
    });

    test('keeps each tab set independent', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/rtl');

        await page.getByRole('tab', { name: 'التبويب 3' }).click();

        await expect(page.getByRole('tab', { name: 'התווית 2' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tabpanel')).toHaveCount(2);
        await expect(page.getByRole('tabpanel', { name: 'التبويب 3' })).toBeVisible();
        await expect(page.getByRole('tabpanel', { name: 'התווית 2' })).toBeVisible();
    });

    test('skips a disabled tab', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/disabled');
        const disabled = page.getByRole('tab', { name: 'Tab 2' });

        await page.getByRole('tab', { name: 'Tab 1' }).press('ArrowRight');
        await expect(page.getByRole('tab', { name: 'Tab 3' })).toBeFocused();
        await expect(page.getByRole('tab', { name: 'Tab 3' })).toHaveAttribute('aria-selected', 'true');

        await page.keyboard.press('ArrowLeft');
        await expect(page.getByRole('tab', { name: 'Tab 1' })).toBeFocused();

        await disabled.click({ force: true });
        await expect(disabled).toBeDisabled();
        await expect(disabled).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByRole('tab', { name: 'Tab 1' })).toHaveAttribute('aria-selected', 'true');
    });

    test('keeps the first tab reachable when no tab is selected', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/lift-with-content');

        await page.locator('[data-controller="tabs"]').evaluate((element) => {
            element.dataset.tabsActiveValue = '';
        });

        await expect(page.getByRole('tab', { name: 'Tab 1' })).toHaveAttribute('tabindex', '0');
        await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('tabindex', '-1');
        await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByRole('tabpanel')).toHaveCount(0);
    });

    test('selects a tab of a list without panels', async ({ page, gotoExample }) => {
        await gotoExample('daisyui-5/tabs/default');
        const tab = page.getByRole('tab', { name: 'Tab 3' });

        await tab.click();

        await expect(tab).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tab', { name: 'Tab 2' })).toHaveAttribute('aria-selected', 'false');
        await expect(tab).not.toHaveAttribute('aria-controls');
    });
});
