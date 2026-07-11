import { test, expect } from '@playwright/test';

test('typing then waiting 800ms produces a saved-at indicator', async ({ page }) => {
    await page.goto('/editor/live');
    await page.locator('.ck-editor__editable').click();
    await page.keyboard.type('autosave probe');
    await page.waitForTimeout(900);
    await expect(page.locator('[data-test-id="dirty-state"]')).toContainText('saved at');
});
