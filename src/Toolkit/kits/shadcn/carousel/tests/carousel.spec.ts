import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/carousel', () => {
    testState('moves to the next slide on a click on the next button', {
        example: 'default',
        state: 'next',
        act: async (page) => {
            await page.getByRole('button', { name: 'Next slide' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('2', { exact: true })).toBeInViewport();
            await expect(page.getByText('1', { exact: true })).not.toBeInViewport();
            await expect(page.getByRole('button', { name: 'Previous slide' })).toBeEnabled();
        },
    });

    test('moves back to the previous slide on a click on the previous button', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/api');
        await page.getByRole('button', { name: 'Next slide' }).click();
        await expect(page.getByText('Slide 2 of 5')).toBeVisible();

        await page.getByRole('button', { name: 'Previous slide' }).click();

        await expect(page.getByText('Slide 1 of 5')).toBeVisible();
        await expect(page.getByText('1', { exact: true })).toBeInViewport();
        await expect(page.getByText('2', { exact: true })).not.toBeInViewport();
    });

    test('disables the previous button on the first slide and the next button on the last one', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/carousel/api');
        const previous = page.getByRole('button', { name: 'Previous slide' });
        const next = page.getByRole('button', { name: 'Next slide' });
        await expect(previous).toBeDisabled();
        await expect(next).toBeEnabled();

        for (let i = 0; i < 4; i++) {
            await next.click();
        }

        await expect(page.getByText('Slide 5 of 5')).toBeVisible();
        await expect(page.getByText('5', { exact: true })).toBeInViewport();
        await expect(next).toBeDisabled();
        await expect(previous).toBeEnabled();
    });

    test('moves between slides with the left and right arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/api');
        const carousel = page.getByRole('region', { name: 'Carousel' });
        await carousel.focus();

        await carousel.press('ArrowLeft');

        await expect(page.getByText('Slide 1 of 5')).toBeVisible();

        await carousel.press('ArrowRight');
        await expect(page.getByText('Slide 2 of 5')).toBeVisible();
        await expect(page.getByText('2', { exact: true })).toBeInViewport();

        await carousel.press('ArrowRight');
        await expect(page.getByText('Slide 3 of 5')).toBeVisible();

        await carousel.press('ArrowLeft');
        await expect(page.getByText('Slide 2 of 5')).toBeVisible();
    });

    test('mirrors the arrow keys in a right-to-left carousel', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/rtl');
        const carousel = page.getByRole('region', { name: 'Carousel' }).first();
        await carousel.focus();

        await carousel.press('ArrowLeft');

        await expect(page.getByText('٢', { exact: true })).toBeInViewport();
        await expect(page.getByText('١', { exact: true })).not.toBeInViewport();

        await carousel.press('ArrowRight');

        await expect(page.getByText('١', { exact: true })).toBeInViewport();
        await expect(page.getByText('٢', { exact: true })).not.toBeInViewport();
    });

    testState('scrolls a vertical carousel with its buttons', {
        example: 'orientation',
        state: 'next',
        act: async (page) => {
            await page.getByRole('button', { name: 'Next slide' }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByText('1', { exact: true })).not.toBeInViewport();
            await expect(page.getByText('3', { exact: true })).toBeInViewport();
            await expect(page.getByRole('button', { name: 'Previous slide' })).toBeEnabled();
        },
    });

    test('moves a vertical carousel with the up and down arrow keys only', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/orientation');
        const carousel = page.getByRole('region', { name: 'Carousel' });
        await carousel.focus();

        await carousel.press('ArrowRight');

        await expect(page.getByText('1', { exact: true })).toBeInViewport();
        await expect(page.getByRole('button', { name: 'Previous slide' })).toBeDisabled();

        await carousel.press('ArrowDown');

        await expect(page.getByText('1', { exact: true })).not.toBeInViewport();
        await expect(page.getByText('3', { exact: true })).toBeInViewport();

        await carousel.press('ArrowUp');

        await expect(page.getByText('1', { exact: true })).toBeInViewport();
    });

    test('wraps around to the last slide when looping', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/loop');
        const previous = page.getByRole('button', { name: 'Previous slide' });
        await expect(previous).toBeEnabled();

        await previous.click();

        await expect(page.getByText('5', { exact: true })).toBeInViewport();
        await expect(page.getByText('3', { exact: true })).not.toBeInViewport();
        await expect(previous).toBeEnabled();
        await expect(page.getByRole('button', { name: 'Next slide' })).toBeEnabled();
    });

    test('advances on its own with autoplay', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/autoplay', { timers: 'fake' });
        await expect(page.getByText('1', { exact: true })).toBeInViewport();

        await page.clock.runFor(1500);

        await expect(page.getByText('2', { exact: true })).toBeInViewport();
        await expect(page.getByText('1', { exact: true })).not.toBeInViewport();

        await page.clock.runFor(2000);

        await expect(page.getByText('3', { exact: true })).toBeInViewport();
        await expect(page.getByText('2', { exact: true })).not.toBeInViewport();
    });

    test('pauses autoplay while the pointer is over the carousel', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/carousel/autoplay', { timers: 'fake' });
        await page.getByRole('region', { name: 'Carousel' }).hover();

        await page.clock.runFor(5000);

        await expect(page.getByText('1', { exact: true })).toBeInViewport();

        await page.mouse.move(0, 0);
        await page.clock.runFor(2500);

        await expect(page.getByText('2', { exact: true })).toBeInViewport();
        await expect(page.getByText('1', { exact: true })).not.toBeInViewport();
    });
});
