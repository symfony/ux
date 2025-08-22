import { expect, type Page, test } from '@playwright/test';
import { getSymfonyKernelVersionId } from '../../../../../../../../test/playwright-helpers';

async function expectMapToBeVisible(page: Page) {
    await expect(await page.getByTestId('map')).toBeVisible();
    await expect(await page.getByTestId('map').locator('.leaflet-pane').first()).toBeAttached();
    await expect(await page.getByTestId('map').locator('.leaflet-control-container')).toBeAttached();
}

async function expectOneInfoWindowToBeOpenedAndContainText(page: Page, text: string) {
    const popups = page.locator('.leaflet-popup');
    await expect(popups).toHaveCount(1);
    await expect(popups.first()).toBeInViewport();
    await expect(popups.first()).toContainText(text);
}

test('Can render basic map', async ({ page }) => {
    await page.goto('/ux-map/basic?renderer=leaflet');
    await expectMapToBeVisible(page);
});

test('Can render markers and fit bounds to marker', async ({ page }) => {
    await page.goto('/ux-map/with-markers-and-fit-bounds-to-markers?renderer=leaflet');
    await expectMapToBeVisible(page);

    const markers = page.getByTestId('map').locator('.leaflet-marker-icon');
    await expect(markers, '2 markers should be present').toHaveCount(2);
    for (const marker of await markers.all()) {
        await expect(marker).toBeInViewport();
    }

    await expect(markers.nth(0)).toHaveAttribute('title', 'Paris');
    await expect(markers.nth(1)).toHaveAttribute('title', 'Lyon');
});

test('Can render markers, zoomed on Paris, Lyon marker should be hidden', async ({ page }) => {
    await page.goto('/ux-map/with-markers-and-zoomed-on-paris?renderer=leaflet');
    await expectMapToBeVisible(page);

    // Ensure the two markers are rendered, but only the Paris marker should be visible in the viewport
    const markers = page.getByTestId('map').locator('.leaflet-marker-icon');
    await expect(markers).toHaveCount(2);
    await expect(markers.nth(0)).toHaveAttribute('title', 'Paris');
    await expect(markers.nth(0)).toBeInViewport();
    await expect(markers.nth(1)).toHaveAttribute('title', 'Lyon');
    await expect(markers.nth(1), 'The "Lyon" marker should not be visible').not.toBeInViewport();
});

test('Can render markers and info windows', async ({ page }) => {
    await page.goto('/ux-map/with-markers-and-info-windows?renderer=leaflet');
    await expectMapToBeVisible(page);

    const markers = page.getByTestId('map').locator('.leaflet-marker-icon');
    await expect(markers, '2 markers should be present').toHaveCount(2);
    for (const marker of await markers.all()) {
        await expect(marker).toBeInViewport();
    }

    await expect(markers.nth(0)).toHaveAttribute('title', 'Paris');
    await expect(markers.nth(1)).toHaveAttribute('title', 'Lyon');

    // Ensure only one popup is visible at a time, the popup for Paris should be opened by default
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'Capital of France');

    // Click on the Lyon marker to open its popup, the Paris popup should close
    await markers.nth(1).click();
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'Famous for its gastronomy');
});

test('Can render markers with custom icons', async ({ page }) => {
    await page.goto('/ux-map/with-markers-and-custom-icons?renderer=leaflet');
    await expectMapToBeVisible(page);

    const markers = page.getByTestId('map').locator('.leaflet-marker-icon');
    await expect(markers, '3 markers should be present').toHaveCount(3);
    for (const marker of await markers.all()) {
        await expect(marker).toBeInViewport();
    }

    await expect(markers.nth(0)).toHaveAttribute('title', 'Paris');
    expect(await markers.nth(0).innerHTML()).toEqual(
        '<svg viewBox="0 0 24 24" width="24" height="24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M8.21 17c.44-.85.85-1.84 1.23-3H9v-2h1c.61-2.6 1-5.87 1-10h2c0 4.13.4 7.4 1 10h1v2h-.44c.38 1.16.79 2.15 1.23 3H17v2l2 3h-2.42c-.77-1.76-2.53-3-4.58-3s-3.81 1.24-4.58 3H5l2-3l-.03-2zm4.38-3h-1.18a22 22 0 0 1-1.13 3h3.44c-.4-.87-.79-1.87-1.13-3"></path></svg>'
    );

    await expect(markers.nth(1)).toHaveAttribute('title', 'Lyon');
    expect(await markers.nth(1).innerHTML()).toEqual(
        '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24"><path fill="currentColor" d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4"></path></svg>'
    );

    await expect(markers.nth(2)).toHaveAttribute('title', 'Bordeaux');
    await expect(markers.nth(2)).toHaveAttribute(
        'src',
        getSymfonyKernelVersionId() >= 70200
            ? '/assets/icons/mdi/glass-wine-SOLVwOG.svg'
            : '/assets/icons/mdi/glass-wine-48e2d5c0e18f9b07dab82e113ec7490e.svg'
    );
});

test('Can render polygons', async ({ page }) => {
    await page.goto('/ux-map/with-polygons?renderer=leaflet');
    await expectMapToBeVisible(page);

    const paths = page.getByTestId('map').locator('path.leaflet-interactive');
    await expect(paths, '2 polygons must be present').toHaveCount(2);
    await expect(paths.nth(0)).toHaveAttribute(
        'd',
        'M548 276L656 188L762 260L708 433L573 384zM615 352L696 354L678 236L640 250z'
    );
    await expect(paths.nth(1)).toHaveAttribute('d', 'M870 476L795 364L844 395L911 508z');

    // Workaround for `paths.nth(0).click({ relative: { ... } })` which does not work, it tries to click the center of the polygon,
    // but since it's empty, the popup can't be opened.
    const firstPathBoundingBox = await paths.nth(0).boundingBox();
    await page.mouse.click(firstPathBoundingBox.x + 40, firstPathBoundingBox.y + 100);
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A weird shape on the France');

    const secondPathBoundingBox = await paths.nth(1).boundingBox();
    await page.mouse.click(secondPathBoundingBox.x + 50, secondPathBoundingBox.y + 40);
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A polygon covering some of the main cities in Italy');
});

test('Can render polylines', async ({ page }) => {
    await page.goto('/ux-map/with-polylines?renderer=leaflet');
    await expectMapToBeVisible(page);

    const paths = page.getByTestId('map').locator('path.leaflet-interactive');
    await expect(paths, '2 polylines must be present').toHaveCount(2);
    await expect(paths.nth(0)).toHaveAttribute('d', 'M640 250L696 354L708 433L573 384');
    await expect(paths.nth(1)).toHaveAttribute('d', 'M548 276L551 306L603 302');

    // Workaround for `paths.nth(0).click({ relative: { ... } })` which does not work, it tries to click the center of the polygon,
    // but since it's empty, the popup can't be opened.
    const firstPathBoundingBox = await paths.nth(0).boundingBox();
    await page.mouse.click(firstPathBoundingBox.x + 95, firstPathBoundingBox.y + 50);
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A line passing through Paris, Lyon, Marseille, Bordeaux');

    const secondPathBoundingBox = await paths.nth(1).boundingBox();
    await page.mouse.click(secondPathBoundingBox.x + 5, secondPathBoundingBox.y + 25);
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A line passing through Rennes, Nantes and Tours');
});

test('Can render circles', async ({ page }) => {
    await page.goto('/ux-map/with-circles?renderer=leaflet');
    await expectMapToBeVisible(page);

    const paths = page.getByTestId('map').locator('path.leaflet-interactive');
    await expect(paths, '2 circles must be present').toHaveCount(2);
    await expect(paths.nth(0)).toHaveAttribute(
        'd',
        'M623.5256177777774,250.21082480695986a16,16 0 1,0 32,0 a16,16 0 1,0 -32,0 '
    );
    await expect(paths.nth(1)).toHaveAttribute(
        'd',
        'M687.0390399999997,354.0936387274919a9,9 0 1,0 18,0 a9,9 0 1,0 -18,0 '
    );

    await paths.nth(0).click();
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A 50km radius circle centered on Paris');

    await paths.nth(1).click();
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A 30km radius circle centered on Lyon');
});

test('Can render rectangles', async ({ page }) => {
    await page.goto('/ux-map/with-rectangles?renderer=leaflet');
    await expectMapToBeVisible(page);

    const paths = page.getByTestId('map').locator('path.leaflet-interactive');
    await expect(paths, '2 rectangles must be present').toHaveCount(2);
    await expect(paths.nth(0)).toHaveAttribute('d', 'M640 250L640 188L656 188L656 250z');
    await expect(paths.nth(1)).toHaveAttribute('d', 'M573 384L573 354L696 354L696 384z');

    await paths.nth(0).click();
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A rectangle from Paris to Lille');

    await paths.nth(1).click();
    await expectOneInfoWindowToBeOpenedAndContainText(page, 'A rectangle from Bordeaux to Lyon');
});
