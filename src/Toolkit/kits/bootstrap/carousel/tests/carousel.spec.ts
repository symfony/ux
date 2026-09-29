import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

// Slides have no accessible role, and Bootstrap marks the shown one with the `active` class once its transition ends.
const activeSlide = (page: Page) => page.locator('.carousel-item.active');

describeRecipe('bootstrap/carousel', () => {
    testState('shows the next slide on a click on Next', {
        example: 'basic-example',
        state: 'next',
        act: async (page) => {
            await page.getByRole('button', { name: 'Next', exact: true }).click();

            await expect(activeSlide(page)).toHaveText('Second slide');
            await page.mouse.move(0, 0);
        },
    });

    test('wraps to the last slide on a click on Previous from the first one', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/basic-example');
        await expect(activeSlide(page)).toHaveText('First slide');

        await page.getByRole('button', { name: 'Previous', exact: true }).click();

        await expect(activeSlide(page)).toHaveText('Third slide');
    });

    testState('shows the slide of a clicked indicator', {
        example: 'indicators',
        state: 'third',
        act: async (page) => {
            await page.getByRole('button', { name: 'Slide 3', exact: true }).click();

            await expect(activeSlide(page)).toHaveText('Third slide');
            await expect(page.getByRole('button', { name: 'Slide 3', exact: true })).toHaveAttribute(
                'aria-current',
                'true'
            );
            await expect(page.getByRole('button', { name: 'Slide 1', exact: true })).not.toHaveAttribute(
                'aria-current'
            );
            await page.mouse.move(0, 0);
        },
    });

    test('moves between slides with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/basic-example');
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(activeSlide(page)).toHaveText('Second slide');

        await page.keyboard.press('ArrowRight');

        await expect(activeSlide(page)).toHaveText('Third slide');

        await page.keyboard.press('ArrowLeft');

        await expect(activeSlide(page)).toHaveText('Second slide');
    });

    test('crossfades to the next slide', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/crossfade');

        await page.getByRole('button', { name: 'Next', exact: true }).click();

        await expect(activeSlide(page)).toHaveText('Second slide');
    });

    test('cycles through the slides on its own', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/autoplaying-carousels', { timers: 'fake' });

        await page.clock.runFor(2000);

        await expect(activeSlide(page)).toHaveText('First slide');

        await page.clock.runFor(3000);

        await expect(activeSlide(page)).toHaveText('Second slide');

        await page.clock.runFor(5000);

        await expect(activeSlide(page)).toHaveText('Third slide');
    });

    test('pauses while the pointer is over it', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/autoplaying-carousels', { timers: 'fake' });
        await page.getByText('First slide').hover();

        await page.clock.runFor(10000);

        await expect(activeSlide(page)).toHaveText('First slide');

        await page.mouse.move(0, 0);
        await page.clock.runFor(6000);

        await expect(activeSlide(page)).toHaveText('Second slide');
    });

    test('starts cycling only after the first interaction', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/ride-after-interaction', { timers: 'fake' });

        await page.clock.runFor(10000);

        await expect(activeSlide(page)).toHaveText('First slide');

        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(activeSlide(page)).toHaveText('Second slide');
        await page.mouse.move(0, 0);
        await page.clock.runFor(6000);

        await expect(activeSlide(page)).toHaveText('Third slide');
    });

    test('waits for the interval of each slide', async ({ page, gotoExample }) => {
        await gotoExample('bootstrap/carousel/individual-item-interval', { timers: 'fake' });

        await page.clock.runFor(7000);

        await expect(activeSlide(page)).toHaveText('10 seconds');

        await page.clock.runFor(3000);

        await expect(activeSlide(page)).toHaveText('2 seconds');

        await page.clock.runFor(2000);

        await expect(activeSlide(page)).toHaveText('Default interval');
    });
});
