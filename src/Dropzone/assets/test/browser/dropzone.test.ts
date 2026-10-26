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

test('single: submits the file a pre-add listener resolves', async ({ page }) => {
    await page.goto('/ux-dropzone/single');
    await page.locator('.dropzone-container').evaluate((container) => {
        container.addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(
                new Promise((resolve) => setTimeout(resolve, 100)).then(() =>
                    event.detail.files.map(
                        (file: File) =>
                            new File([file], `renamed-${file.name}`, {
                                type: file.type,
                                lastModified: file.lastModified,
                            })
                    )
                )
            );
        });
    });

    await page
        .locator('input[type="file"]')
        .setInputFiles([{ name: 'a.txt', mimeType: 'text/plain', buffer: Buffer.from('aaa') }]);
    await expect(page.locator('.dropzone-container')).toHaveAttribute('data-state', 'processing');
    await expect(page.locator('.dropzone-preview-filename')).toHaveText('renamed-a.txt');
    await expect(page.locator('.dropzone-container')).not.toHaveAttribute('data-state');

    await page.locator('#upload-submit').click();
    await expect(page.locator('#upload-success')).toContainText('Uploaded: renamed-a.txt');
});

test('multiple: submits the files a pre-add listener resolves', async ({ page }) => {
    await page.goto('/ux-dropzone/multiple');
    await page.locator('.dropzone-container').evaluate((container) => {
        container.addEventListener('dropzone:pre-add', (event: any) => {
            event.detail.waitUntil(
                Promise.resolve(
                    event.detail.files.map(
                        (file: File) =>
                            new File([file], `renamed-${file.name}`, {
                                type: file.type,
                                lastModified: file.lastModified,
                            })
                    )
                )
            );
        });
    });

    const input = page.locator('input[type="file"]');
    const filenames = page.locator('.dropzone-preview-list-item .dropzone-preview-filename');

    await input.setInputFiles([{ name: 'a.txt', mimeType: 'text/plain', buffer: Buffer.from('aaa') }]);
    await expect(filenames).toHaveText(['renamed-a.txt']);
    await input.setInputFiles([{ name: 'b.txt', mimeType: 'text/plain', buffer: Buffer.from('bbb') }]);
    await expect(filenames).toHaveText(['renamed-a.txt', 'renamed-b.txt']);

    await page.locator('#upload-submit').click();
    await expect(page.locator('#upload-success li')).toHaveText(['renamed-a.txt', 'renamed-b.txt']);
});
