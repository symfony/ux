import { describeRecipe, expect, test } from '../../../../assets/test/browser/fixtures';

describeRecipe('bootstrap/alert', () => {
    test('removes the alert on a click on its close button', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/alert/dismissing');
        const alert = page.getByRole('alert');
        await expect(alert).toContainText('Holy guacamole!');

        await alert.getByRole('button', { name: 'Close' }).click();

        await expect(page.getByRole('alert')).toHaveCount(0);
    });

    test('removes the alert when its close button is activated with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/alert/default');
        const close = page.getByRole('alert').getByRole('button', { name: 'Close' });
        await close.focus();

        await page.keyboard.press('Enter');

        await expect(page.getByRole('alert')).toHaveCount(0);
    });

    test('dispatches closed.bs.alert once the alert is removed', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/alert/dismissing');
        await page.getByRole('alert').evaluate((alert) => {
            alert.addEventListener('closed.bs.alert', () => {
                document.body.dataset.alertClosed = String(!alert.isConnected);
            });
        });

        await page.getByRole('button', { name: 'Close' }).click();

        await expect(page.locator('body')).toHaveAttribute('data-alert-closed', 'true');
    });
});
