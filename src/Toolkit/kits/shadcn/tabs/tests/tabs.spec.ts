import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/tabs', () => {
    testState('shows the panel of the clicked tab', {
        example: 'default',
        state: 'analytics',
        act: async (page) => {
            const analytics = page.getByRole('tab', { name: 'Analytics' });

            await analytics.click();
            await page.mouse.move(0, 0);

            await expect(analytics).toHaveAttribute('aria-selected', 'true');
            await expect(page.getByRole('tab', { name: 'Overview' })).toHaveAttribute('aria-selected', 'false');
            await expect(page.getByRole('tabpanel', { name: 'Analytics' })).toBeVisible();
            await expect(page.getByText('Page views are up 25% compared to last month.')).toBeVisible();
            await expect(page.getByText('You have 12 active projects and 3 pending tasks.')).toBeHidden();
        },
    });

    testState('moves the line indicator to the clicked tab', {
        example: 'line',
        state: 'reports',
        act: async (page) => {
            const reports = page.getByRole('tab', { name: 'Reports' });

            await reports.click();
            await page.mouse.move(0, 0);

            await expect(reports).toHaveAttribute('aria-selected', 'true');
            await expect(reports).toHaveAttribute('data-active', '');
            await expect(page.getByRole('tab', { name: 'Overview' })).toHaveAttribute('aria-selected', 'false');
            await expect(page.getByRole('tab', { name: 'Overview' })).not.toHaveAttribute('data-active');
        },
    });

    test('selects a tab with Enter and with Space', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tabs/default');
        await page.getByRole('tab', { name: 'Overview' }).focus();

        await page.keyboard.press('Tab');
        await page.keyboard.press('Enter');

        await expect(page.getByRole('tab', { name: 'Analytics' })).toBeFocused();
        await expect(page.getByRole('tab', { name: 'Analytics' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByText('Page views are up 25% compared to last month.')).toBeVisible();

        await page.keyboard.press('Tab');
        await page.keyboard.press('Space');

        await expect(page.getByRole('tab', { name: 'Reports' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tab', { name: 'Analytics' })).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByText('You have 5 reports ready and available to export.')).toBeVisible();
        await expect(page.getByText('Page views are up 25% compared to last month.')).toBeHidden();
    });

    test('selects a tab of a vertical list', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tabs/vertical');

        await page.getByRole('tab', { name: 'Password' }).click();

        await expect(page.getByRole('tab', { name: 'Password' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByRole('tab', { name: 'Account' })).toHaveAttribute('aria-selected', 'false');
    });

    test('does not select a disabled tab', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tabs/disabled');
        const disabled = page.getByRole('tab', { name: 'Disabled' });

        await disabled.click({ force: true });

        await expect(disabled).toBeDisabled();
        await expect(disabled).toHaveAttribute('aria-selected', 'false');
        await expect(page.getByRole('tab', { name: 'Home' })).toHaveAttribute('aria-selected', 'true');
    });

    test('keeps each tab set independent', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/tabs/rtl');

        await page.getByRole('tab', { name: 'التحليلات' }).click();

        await expect(page.getByRole('tab', { name: 'التحليلات' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByText('زادت مشاهدات الصفحة بنسبة ٢٥٪ مقارنة بالشهر الماضي.')).toBeVisible();
        await expect(page.getByRole('tab', { name: 'סקירה כללית' })).toHaveAttribute('aria-selected', 'true');
        await expect(page.getByText('יש לך 12 מיזמים נגישים ו-3 משימות ממתינות.')).toBeVisible();
        await expect(page.getByText('הגידול עמד על 25% בהשוואה לחודש שעבר.')).toBeHidden();
    });
});
