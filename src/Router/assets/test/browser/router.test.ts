import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/ux-router/basic');
    await page.getByRole('button', { name: 'Generate' }).click();
});

test('Can generate a path and follow it', async ({ page }) => {
    await expect(page.getByTestId('path')).toHaveText('/ux-router/blog/hello-world?page=2');
    await page.getByRole('link', { name: 'Blog post' }).click();

    await expect(page).toHaveURL(/\/ux-router\/blog\/hello-world\?page=2$/);
    await expect(page.getByRole('heading', { name: 'hello-world' })).toBeVisible();
});

test('Can generate a localized path', async ({ page }) => {
    await expect(page.getByTestId('localized')).toHaveText('/ux-router/a-propos');
});

test('Can generate an absolute URL', async ({ page }) => {
    await expect(page.getByTestId('url')).toHaveText('http://localhost:9876/ux-router/blog/hello-world');
});
