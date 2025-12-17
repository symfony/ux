import { expect, test } from '@playwright/test';

test('Can display and interact with Cropper.js', async ({ page }) => {
    await page.goto('/ux-cropperjs/crop');

    // Wait for the cropper to be initialized
    const cropperContainer = page.locator('.cropperjs');
    await expect(cropperContainer).toBeVisible();

    // Check that the canvas is created by Cropper.js
    const cropperCanvas = page.locator('.cropper-canvas');
    await expect(cropperCanvas).toBeVisible();

    // Check that the crop box is visible
    const cropBox = page.locator('.cropper-crop-box');
    await expect(cropBox).toBeVisible();

    const actionE = page.locator('[data-cropper-action="e"].cropper-line');
    const actionEBox = await cropBox.boundingBox();
    if (actionEBox) {
        // Drag the east handle to resize the crop box
        await actionE.hover({ force: true });
        await page.mouse.down();
        await page.mouse.move(actionEBox.x + actionEBox.width - 200, actionEBox.y + actionEBox.height / 2);
        await page.mouse.up();
    }

    // Submit the form to crop the image
    await page.click('#crop-submit');

    // Wait for the cropped image to be displayed
    await expect(page.locator('#crop-success')).toBeVisible();
    await expect(page.locator('#cropped-result')).toBeVisible();

    // Verify the cropped image is a valid base64 image
    const croppedImage = page.locator('#cropped-result');
    expect(await croppedImage.getAttribute('src')).toContain('data:image/jpeg;base64,');
    const croppedImageWidth = await croppedImage.evaluate((img: HTMLImageElement) => img.naturalWidth);
    const croppedImageHeight = await croppedImage.evaluate((img: HTMLImageElement) => img.naturalHeight);
    expect(croppedImageWidth).toBeGreaterThanOrEqual(540); // Chrome
    expect(croppedImageWidth).toBeLessThanOrEqual(541); // Firefox
    expect(croppedImageHeight).toEqual(461);
});

test('Can display Cropper.js with aspect ratio constraint', async ({ page }) => {
    await page.goto('/ux-cropperjs/crop-with-aspect-ratio');

    // Wait for the cropper to be initialized
    const cropperContainer = page.locator('.cropperjs');
    await expect(cropperContainer).toBeVisible();

    // Check that the canvas and crop box are visible
    await expect(page.locator('.cropper-canvas')).toBeVisible();
    await expect(page.locator('.cropper-crop-box')).toBeVisible();

    // Submit the form to crop the image
    await page.click('#crop-submit');

    // Wait for the cropped image to be displayed
    await expect(page.locator('#crop-success')).toBeVisible();
    await expect(page.locator('#cropped-result')).toBeVisible();

    // Verify the cropped image is a valid base64 image, and has the correct aspect ratio (16:9)
    const croppedImage = page.locator('#cropped-result');
    expect(await croppedImage.getAttribute('src')).toContain('data:image/jpeg;base64,');
    expect(await croppedImage.evaluate((img: HTMLImageElement) => img.naturalWidth / img.naturalHeight)).toBeCloseTo(
        16 / 9,
        1
    );
});
