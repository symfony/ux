import { expect, test } from '@playwright/test';

const credentials = { username: 'admin', password: 'admin' };
const uniqueReference = `ref_${Date.now()}_${Math.random().toString(36).slice(2)}`;

test('reveals the protected email on click', async ({ page }) => {
    await page.goto(`/ux-disclose?r=success`, { httpCredentials: credentials });

    const trigger = page.locator('[data-disclose-target="button"]').first();
    const value = page.locator('[data-disclose-target="value"]').first();

    await expect(trigger).toHaveText('••••••');
    await trigger.click();

    await expect(value).toHaveText('bruce@wayne.example');
    await expect(trigger).toBeHidden();
});

test('shows the rate-limited state after the limit is reached', async ({ page }) => {
    await page.goto(`/ux-disclose?r=flood_${uniqueReference}`, { httpCredentials: credentials });

    const trigger = page.locator('[data-disclose-target="button"]').first();
    const error = page.locator('[data-disclose-target="error"]').first();

    // The demo rate limiter allows 3 disclosures per visitor, then answers
    // 429. Click until the client shows the rate-limited state.
    for (let i = 0; i < 8 && !(await error.isVisible()); i++) {
        await trigger.click({ noWaitAfter: true });
    }

    await expect(error).toBeVisible();
    await expect(error).toHaveText(/rate limit|quota/i);
});
