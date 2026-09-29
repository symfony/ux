import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('flowbite-4/alert', () => {
    testState('hides an alert when its close button is clicked', {
        example: 'dismissing',
        state: 'danger-dismissed',
        act: async (page) => {
            const alert = page.getByRole('alert').filter({ hasText: 'A simple danger alert' });

            await alert.getByRole('button', { name: 'Close' }).click();
            await page.mouse.move(0, 0);

            await expect(alert).toBeHidden();
            await expect(page.getByRole('alert')).toHaveCount(4);
        },
    });

    test('dismisses each alert independently', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/alert/dismissing');
        const alerts = page.getByRole('alert');
        const info = alerts.filter({ hasText: 'A simple info alert' });
        const dark = alerts.filter({ hasText: 'A simple dark alert' });
        await expect(alerts).toHaveCount(5);

        await info.getByRole('button', { name: 'Close' }).click();
        await dark.getByRole('button', { name: 'Close' }).click();

        await expect(info).toBeHidden();
        await expect(dark).toBeHidden();
        await expect(alerts).toHaveCount(3);
        await expect(alerts.filter({ hasText: 'A simple success alert' })).toBeVisible();
    });

    test('dismisses an alert with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/alert/dismissing');
        const alert = page.getByRole('alert').filter({ hasText: 'A simple warning alert' });
        await alert.getByRole('button', { name: 'Close' }).focus();

        await page.keyboard.press('Enter');

        await expect(alert).toBeHidden();
    });
});
