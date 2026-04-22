import { expect, type Page, test } from '@playwright/test';

const MERCURE_TIMEOUT = 10000;

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

test('Turbo Broadcast — create, update and remove an entity via Mercure', async ({ page }) => {
    const bookTitle = `The Ecology of Freedom ${Date.now()}`;

    await page.goto('/ux-turbo/broadcast/books');

    await page.fill('#book-title', bookTitle);
    await page.click('#book-submit');

    // Wait for the Mercure-broadcasted Turbo Stream to append the new book
    const newBook = page.locator('#books-list .book-item', { hasText: bookTitle });
    await expect(newBook).toBeVisible({ timeout: MERCURE_TIMEOUT });

    // Extract the assigned ID from the rendered text "title (#id)"
    const text = (await newBook.textContent()) ?? '';
    const match = text.match(/\(#(\d+)\)/);
    expect(match, 'Book ID not found in rendered text').not.toBeNull();
    const bookId = match![1];

    // Update
    await page.fill('#book-id', bookId);
    await page.fill('#book-title', 'updated');
    await page.click('#book-submit');

    await expect(page.locator(`#book_${bookId}`)).toContainText('updated', { timeout: MERCURE_TIMEOUT });

    // Remove
    await page.fill('#book-id', bookId);
    await page.locator('#book-remove').check();
    await page.click('#book-submit');

    await expect(page.locator(`#book_${bookId}`)).toHaveCount(0, { timeout: MERCURE_TIMEOUT });
});

test('Turbo Broadcast — Expression Language topics scope updates per artist', async ({ browser }) => {
    const context = await browser.newContext();
    const page = await context.newPage();

    const artist1Name = `Queen ${Date.now()}`;
    const artist2Name = `Elvis Presley ${Date.now()}`;
    const songTitle = `Bohemian Rhapsody ${Date.now()}`;

    await page.goto('/ux-turbo/broadcast/artists');

    // Create first artist
    await page.fill('#artist-name', artist1Name);
    await page.click('#artist-submit');
    const artist1Locator = page.locator('#artists-list .artist-item', { hasText: artist1Name });
    await expect(artist1Locator).toBeVisible({ timeout: MERCURE_TIMEOUT });

    // Create second artist
    await page.fill('#artist-name', artist2Name);
    await page.click('#artist-submit');
    const artist2Locator = page.locator('#artists-list .artist-item', { hasText: artist2Name });
    await expect(artist2Locator).toBeVisible({ timeout: MERCURE_TIMEOUT });

    const artist1Id = ((await artist1Locator.textContent()) ?? '').match(/\(#(\d+)\)/)![1];
    const artist2Id = ((await artist2Locator.textContent()) ?? '').match(/\(#(\d+)\)/)![1];

    // Open additional pages, one per artist
    const artist1Page = await context.newPage();
    await artist1Page.goto(`/ux-turbo/broadcast/artists/${artist1Id}`);

    const artist2Page = await context.newPage();
    await artist2Page.goto(`/ux-turbo/broadcast/artists/${artist2Id}`);

    // Submit a song attached to artist 1 from the songs page
    await page.goto('/ux-turbo/broadcast/songs');
    await page.fill('#song-title', songTitle);
    await page.fill('#song-artist-id', artist1Id);
    await page.click('#song-submit');

    // Artist 1 should receive the song via the per-artist topic
    await expect(artist1Page.locator('#songs-list')).toContainText(songTitle, { timeout: MERCURE_TIMEOUT });

    // Artist 2 should NOT receive the song (different topic).
    // Wait briefly to give Mercure a chance to (wrongly) deliver before asserting absence.
    await artist2Page.waitForTimeout(2000);
    await expect(artist2Page.locator('#songs-list')).not.toContainText(songTitle);

    await context.close();
});

test('Turbo Broadcast — entity stored as a proxy is broadcast on update', async ({ page }) => {
    await page.goto('/ux-turbo/broadcast/artist-from-song');

    // First submit creates the artist + song
    await page.click('#afs-submit');
    await expect(page.locator('#artists-list')).toContainText('testing artist', { timeout: MERCURE_TIMEOUT });

    // Second submit updates the artist (loaded as a Doctrine proxy via Song::artist)
    await page.click('#afs-submit');
    await expect(page.locator('#artists-list')).toContainText('testing artist after change', {
        timeout: MERCURE_TIMEOUT,
    });
    await expect(page.locator('#artists-list')).toContainText(', updated)', { timeout: MERCURE_TIMEOUT });
});
