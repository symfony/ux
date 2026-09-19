import { expect, test } from '@playwright/test';

test('Can render basic map', async ({ page }) => {
    await page.goto('/ux-map/basic?renderer=google');

    const map = page.getByTestId('map');
    await expect(map).toBeVisible();

    // CI has no reliable API key, so Google Maps may render normally or fail depending on the current key restrictions.
    const googleMapsContent = map
        .locator('.gm-style')
        .or(map.getByText(/Map data|Oops! Something went wrong\.|This page didn't load Google Maps correctly/));
    await expect(googleMapsContent.first()).toBeVisible();
});
