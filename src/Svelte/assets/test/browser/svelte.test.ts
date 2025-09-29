import { expect, test } from '@playwright/test';

test('Can see a rendered component', async ({ page }) => {
    await page.goto('/ux-svelte/');

    await expect(page.getByText('Hello Fabien from Svelte')).toBeVisible();
});
