import { expect, type Page, test } from '@playwright/test';

/**
 * Inject a marker in the window object to detect full page reloads.
 * If the page is fully reloaded, this marker will disappear.
 */
async function markPageAsLoaded(page: Page): Promise<void> {
    await page.evaluate(() => {
        (window as any).__turboTestMarker = 'initial-load';
    });
}

/**
 * Verify that the page was not fully reloaded by checking if the marker is still present.
 * This proves that Turbo handled the navigation without a full page reload.
 */
async function expectNoFullPageReload(page: Page): Promise<void> {
    const markerStillPresent = await page.evaluate(() => {
        return (window as any).__turboTestMarker === 'initial-load';
    });
    expect(markerStillPresent).toBe(true);
}

test('Can navigate with Turbo Drive without full page reload', async ({ page }) => {
    await page.goto('/ux-turbo/drive');
    await markPageAsLoaded(page);

    // Check initial page content
    await expect(page.locator('h2')).toContainText('Turbo Drive Navigation - Page 1');
    const initialTime = await page.locator('#page-load-time').textContent();

    // Navigate to page 2
    await page.click('#navigate-to-page-2');

    // Wait for navigation to complete
    await expect(page.locator('h2')).toContainText('Turbo Drive Navigation - Page 2');

    // The time on page 2 should be different (it's a new request)
    const page2Time = await page.locator('#page-load-time').textContent();
    expect(page2Time).not.toBe(initialTime);

    // Navigate back to page 1
    await page.click('#navigate-back');
    await expect(page.locator('h2')).toContainText('Turbo Drive Navigation - Page 1');

    await expectNoFullPageReload(page);
});

test('Can navigate inside a Turbo Frame without affecting the rest of the page', async ({ page }) => {
    await page.goto('/ux-turbo/frame');
    await markPageAsLoaded(page);

    // Check initial state
    await expect(page.locator('#frame-initial-content')).toContainText('This is the initial frame content');
    await expect(page.locator('#content-outside-frame')).toContainText('This content is outside the frame');

    // Click link inside the frame
    await page.click('#load-frame-content');

    // Wait for frame content to update
    await expect(page.locator('#frame-updated-content')).toContainText('The frame content has been updated');

    // Verify content outside frame hasn't changed
    await expect(page.locator('#content-outside-frame')).toContainText('This content is outside the frame');

    // Verify the frame initial content is no longer visible
    await expect(page.locator('#frame-initial-content')).not.toBeVisible();

    await expectNoFullPageReload(page);
});

test('Can update page content with Turbo Streams after form submission', async ({ page }) => {
    await page.goto('/ux-turbo/stream');
    await markPageAsLoaded(page);
    // Submit the form
    await page.click('#submit-turbo-stream');

    // Wait for Turbo Stream to update the content
    await expect(page.locator('#updated-by-stream')).toContainText('This content was updated by a Turbo Stream');

    // Verify the form was removed by the Turbo Stream
    await expect(page.locator('#form-container')).not.toBeVisible();

    // Verify the target element still exists but with new content
    await expect(page.locator('#stream-target')).toBeVisible();
    await expect(page.locator('#stream-target')).toHaveClass(/alert-success/);

    await expectNoFullPageReload(page);
});
