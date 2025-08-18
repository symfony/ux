import { expect, test } from '@playwright/test';

test('Can see homepage', async ({ page }) => {
    await page.goto('/');

    await expect(page.getByText("Symfony UX's E2E App")).toBeVisible();
});
