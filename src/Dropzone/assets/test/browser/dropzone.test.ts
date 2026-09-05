import { expect, test } from '@playwright/test';

test('multiple: accumulates across picks, previews each, removes individually', async ({ page }) => {
    await page.goto('/ux-dropzone/multiple');

    const input = page.locator('input[type="file"]');
    const items = page.locator('.dropzone-preview-list-item');
    const filenames = page.locator('.dropzone-preview-list-item .dropzone-preview-filename');

    // First pick
    await input.setInputFiles([{ name: 'a.txt', mimeType: 'text/plain', buffer: Buffer.from('aaa') }]);
    await expect(items).toHaveCount(1);

    // Second pick: a native <input> would replace its selection; the controller accumulates.
    await input.setInputFiles([{ name: 'b.txt', mimeType: 'text/plain', buffer: Buffer.from('bbb') }]);
    await expect(items).toHaveCount(2);
    await expect(filenames).toHaveText(['a.txt', 'b.txt']);

    // Remove the first file.
    await page.locator('.dropzone-preview-list-remove').first().click();
    await expect(items).toHaveCount(1);
    await expect(filenames).toHaveText(['b.txt']);

    // Submit: the server must receive exactly the remaining file (proves input.files was written).
    await page.locator('#upload-submit').click();
    await expect(page.locator('#upload-success')).toContainText('b.txt');
    await expect(page.locator('#upload-success')).not.toContainText('a.txt');
});
