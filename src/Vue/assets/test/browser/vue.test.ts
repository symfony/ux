import { expect, test } from '@playwright/test';

test('Can see a rendered component', async ({ page }) => {
    await page.goto('/ux-vue/');

    await expect(page.getByText('Hello Fabien from Vue.js')).toBeVisible();
});
