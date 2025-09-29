import { expect, test } from '@playwright/test';

test('Can render basic map', async ({ page }) => {
    await page.goto('/ux-map/basic?renderer=google');

    await expect(await page.getByTestId('map')).toBeVisible();

    // Since we can't test Google Maps rendering due to API costs, we only assert that Google Maps API is (wrongly) loaded
    await expect(await page.getByTestId('map')).toContainText('Oops! Something went wrong.');
    await expect(await page.getByTestId('map')).toContainText(
        "This page didn't load Google Maps correctly. See the JavaScript console for technical details."
    );
});
