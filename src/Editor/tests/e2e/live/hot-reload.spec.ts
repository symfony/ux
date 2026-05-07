import { test, expect } from '@playwright/test';

test('toggling readOnly applies in-place (no remount)', async ({ page }) => {
    await page.goto('/editor/live');
    await expect(page.locator('.ck-editor__editable[contenteditable="true"]')).toBeVisible();
    await page.locator('[data-test-id="toggle-readonly"]').check();
    await expect(page.locator('.ck-editor__editable[contenteditable="false"]')).toBeVisible();
});
