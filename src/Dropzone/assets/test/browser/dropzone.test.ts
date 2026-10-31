import { expect, type Page, test } from '@playwright/test';

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

// Playwright cannot drop files on a page: replay what the browser does, a drop event, then the files and "change" in the same task
const dropFiles = (page: Page, files: Array<{ name: string; type: string }>) =>
    page.locator('input[type="file"]').evaluate((input: HTMLInputElement, files) => {
        input.dispatchEvent(new DragEvent('drop', { bubbles: true }));
        const dataTransfer = new DataTransfer();
        for (const file of files) {
            dataTransfer.items.add(new File([file.name], file.name, { type: file.type }));
        }
        input.files = dataTransfer.files;
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }, files);

test('multiple: rejects the dropped files that do not match accept or go over max files', async ({ page }) => {
    await page.goto('/ux-dropzone/multiple');
    await page.locator('.dropzone-container').evaluate((container) => {
        container.querySelector('input')?.setAttribute('accept', '.txt');
        container.setAttribute('data-symfony--ux-dropzone--dropzone-max-files-value', '2');
        (window as any).rejections = [];
        container.addEventListener('dropzone:reject', (event: any) => {
            (window as any).rejections.push({
                reason: event.detail.reason,
                files: event.detail.files.map((file: File) => file.name),
            });
        });
    });

    await dropFiles(page, [
        { name: 'a.txt', type: 'text/plain' },
        { name: 'b.png', type: 'image/png' },
        { name: 'c.txt', type: 'text/plain' },
        { name: 'd.txt', type: 'text/plain' },
    ]);

    await expect(page.locator('.dropzone-preview-list-item .dropzone-preview-filename')).toHaveText(['a.txt', 'c.txt']);
    expect(await page.evaluate(() => (window as any).rejections)).toStrictEqual([
        { reason: 'accept', files: ['b.png'] },
        { reason: 'max_files', files: ['d.txt'] },
    ]);

    await page.locator('#upload-submit').click();
    await expect(page.locator('#upload-success li')).toHaveText(['a.txt', 'c.txt']);
});

test('multiple: does not filter the picked files by accept', async ({ page }) => {
    await page.goto('/ux-dropzone/multiple');
    await page.locator('input[type="file"]').evaluate((input) => input.setAttribute('accept', 'text/csv'));

    await page
        .locator('input[type="file"]')
        .setInputFiles([{ name: 'data.csv', mimeType: 'application/vnd.ms-excel', buffer: Buffer.from('a,b') }]);

    await expect(page.locator('.dropzone-preview-list-item .dropzone-preview-filename')).toHaveText(['data.csv']);
});

test('single: keeps the previous file when a dropped file does not match accept', async ({ page }) => {
    await page.goto('/ux-dropzone/single');
    await page.locator('input[type="file"]').evaluate((input) => input.setAttribute('accept', 'image/*'));
    await page
        .locator('input[type="file"]')
        .setInputFiles([{ name: 'photo.png', mimeType: 'image/png', buffer: Buffer.from('png') }]);

    await dropFiles(page, [{ name: 'a.txt', type: 'text/plain' }]);

    await expect(page.locator('.dropzone-preview-filename')).toHaveText('photo.png');
    await page.locator('#upload-submit').click();
    await expect(page.locator('#upload-success')).toContainText('Uploaded: photo.png');
});
