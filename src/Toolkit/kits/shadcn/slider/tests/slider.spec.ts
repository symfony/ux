import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';
import type { Locator, Page } from '@playwright/test';

async function pointAt(locator: Locator, ratioX: number, ratioY = 0.5): Promise<{ x: number; y: number }> {
    const box = await locator.boundingBox();
    if (null === box) {
        throw new Error('The element has no bounding box.');
    }

    return { x: box.x + box.width * ratioX, y: box.y + box.height * ratioY };
}

async function drag(page: Page, from: { x: number; y: number }, to: { x: number; y: number }): Promise<void> {
    await page.mouse.move(from.x, from.y);
    await page.mouse.down();
    await page.mouse.move((from.x + to.x) / 2, (from.y + to.y) / 2);
    await page.mouse.move(to.x, to.y);
    await page.mouse.up();
}

describeRecipe('shadcn/slider', () => {
    testState('drags the thumb with the mouse', {
        example: 'default',
        state: 'dragged',
        act: async (page) => {
            const slider = page.getByRole('slider', { name: 'Slider' });
            const track = page.locator('[data-slot="slider-track"]');

            await drag(page, await pointAt(slider, 0.5), await pointAt(track, 0.25));
            await page.mouse.move(0, 0);

            await expect(slider).toHaveAttribute('aria-valuenow', '25');
            await expect(slider).toBeFocused();
        },
    });

    testState('updates the displayed values when a thumb moves', {
        example: 'controlled',
        state: 'updated',
        act: async (page) => {
            const thumb = page.getByRole('slider').last();
            await thumb.focus();

            await page.keyboard.press('ArrowRight');
            await page.keyboard.press('ArrowRight');

            await expect(thumb).toHaveAttribute('aria-valuenow', '0.9');
            await expect(page.getByText('0.3, 0.9')).toBeVisible();
        },
    });

    test('moves by one step with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/default');
        const slider = page.getByRole('slider', { name: 'Slider' });
        await page.keyboard.press('Tab');
        await expect(slider).toBeFocused();

        await page.keyboard.press('ArrowRight');
        await expect(slider).toHaveAttribute('aria-valuenow', '76');
        await page.keyboard.press('ArrowUp');
        await expect(slider).toHaveAttribute('aria-valuenow', '77');
        await page.keyboard.press('ArrowLeft');
        await expect(slider).toHaveAttribute('aria-valuenow', '76');
        await page.keyboard.press('ArrowDown');

        await expect(slider).toHaveAttribute('aria-valuenow', '75');
    });

    test('moves by ten steps with PageUp and PageDown', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/default');
        const slider = page.getByRole('slider', { name: 'Slider' });
        await slider.focus();

        await page.keyboard.press('PageDown');
        await expect(slider).toHaveAttribute('aria-valuenow', '65');
        await page.keyboard.press('PageUp');

        await expect(slider).toHaveAttribute('aria-valuenow', '75');
    });

    test('jumps to the minimum and maximum with Home and End', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/default');
        const slider = page.getByRole('slider', { name: 'Slider' });
        await slider.focus();

        await page.keyboard.press('Home');
        await expect(slider).toHaveAttribute('aria-valuenow', '0');
        await page.keyboard.press('End');

        await expect(slider).toHaveAttribute('aria-valuenow', '100');
    });

    test('clamps the value between the minimum and the maximum', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/default');
        const slider = page.getByRole('slider', { name: 'Slider' });
        await slider.focus();

        await page.keyboard.press('PageUp');
        await page.keyboard.press('PageUp');
        await page.keyboard.press('PageUp');
        await expect(slider).toHaveAttribute('aria-valuenow', '100');
        await page.keyboard.press('ArrowRight');
        await expect(slider).toHaveAttribute('aria-valuenow', '100');
        await page.keyboard.press('Home');
        await page.keyboard.press('ArrowLeft');
        await page.keyboard.press('PageDown');

        await expect(slider).toHaveAttribute('aria-valuenow', '0');
    });

    test('clamps a drag past the end of the track', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/default');
        const slider = page.getByRole('slider', { name: 'Slider' });
        const track = page.locator('[data-slot="slider-track"]');
        const end = await pointAt(track, 1);

        await drag(page, await pointAt(slider, 0.5), { x: end.x + 100, y: end.y });

        await expect(slider).toHaveAttribute('aria-valuenow', '100');
    });

    test('writes the value into the hidden input', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/default');
        await page.getByRole('slider', { name: 'Slider' }).focus();

        await page.keyboard.press('ArrowRight');

        await expect(page.locator('input[name="demo"]')).toHaveValue('76');
    });

    test('moves by the configured step', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/range');
        const min = page.getByRole('slider', { name: 'Min' });
        await min.focus();

        await page.keyboard.press('ArrowRight');

        await expect(min).toHaveAttribute('aria-valuenow', '30');
    });

    test('snaps a drag to the configured step', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/range');
        const max = page.getByRole('slider', { name: 'Max' });
        const track = page.locator('[data-slot="slider-track"]');

        await drag(page, await pointAt(max, 0.5), await pointAt(track, 0.73));

        await expect(max).toHaveAttribute('aria-valuenow', '75');
    });

    test('moves each thumb of a range independently', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/range');
        const min = page.getByRole('slider', { name: 'Min' });
        const max = page.getByRole('slider', { name: 'Max' });
        await page.keyboard.press('Tab');
        await expect(min).toBeFocused();

        await page.keyboard.press('ArrowLeft');
        await page.keyboard.press('Tab');
        await expect(max).toBeFocused();
        await page.keyboard.press('ArrowRight');

        await expect(min).toHaveAttribute('aria-valuenow', '20');
        await expect(max).toHaveAttribute('aria-valuenow', '55');
        await expect(page.locator('input[name="range[]"]').first()).toHaveValue('20');
        await expect(page.locator('input[name="range[]"]').last()).toHaveValue('55');
    });

    test('keeps the thumbs of a range from crossing each other', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/range');
        const min = page.getByRole('slider', { name: 'Min' });
        const max = page.getByRole('slider', { name: 'Max' });

        await min.focus();
        await page.keyboard.press('End');
        await expect(min).toHaveAttribute('aria-valuenow', '50');
        await max.focus();
        await page.keyboard.press('Home');

        await expect(max).toHaveAttribute('aria-valuenow', '50');
    });

    test('moves the nearest thumb on a click on the track', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/range');
        const min = page.getByRole('slider', { name: 'Min' });
        const max = page.getByRole('slider', { name: 'Max' });
        const track = page.locator('[data-slot="slider-track"]');
        const point = await pointAt(track, 0.9);

        await page.mouse.click(point.x, point.y);

        await expect(max).toHaveAttribute('aria-valuenow', '90');
        await expect(max).toBeFocused();
        await expect(min).toHaveAttribute('aria-valuenow', '25');
    });

    test('drags a middle thumb between its neighbours', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/multiple-thumbs');
        const low = page.getByRole('slider', { name: 'Low' });
        const medium = page.getByRole('slider', { name: 'Medium' });
        const high = page.getByRole('slider', { name: 'High' });
        const track = page.locator('[data-slot="slider-track"]');

        await drag(page, await pointAt(medium, 0.5), await pointAt(track, 0.5));
        await expect(medium).toHaveAttribute('aria-valuenow', '50');
        await drag(page, await pointAt(medium, 0.5), await pointAt(track, 0.95));

        await expect(medium).toHaveAttribute('aria-valuenow', '70');
        await expect(low).toHaveAttribute('aria-valuenow', '10');
        await expect(high).toHaveAttribute('aria-valuenow', '70');
    });

    test('drags a vertical slider upwards to increase the value', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/vertical');
        const slider = page.getByRole('slider').first();
        const track = page.locator('[data-slot="slider-track"]').first();

        await drag(page, await pointAt(slider, 0.5), await pointAt(track, 0.5, 0.2));

        await expect(slider).toHaveAttribute('aria-orientation', 'vertical');
        await expect(slider).toHaveAttribute('aria-valuenow', '80');
    });

    test('drags a right-to-left slider towards the left to increase the value', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/rtl');
        const slider = page.getByRole('slider', { name: 'Slider' });
        const track = page.locator('[data-slot="slider-track"]');

        await drag(page, await pointAt(slider, 0.5), await pointAt(track, 0.1));

        await expect(slider).toHaveAttribute('aria-valuenow', '90');
    });

    test('ignores the mouse when disabled', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/slider/disabled');
        const slider = page.getByRole('slider', { name: 'Slider' });
        const track = page.locator('[data-slot="slider-track"]');
        await expect(slider).toHaveAttribute('aria-disabled', 'true');
        const point = await pointAt(track, 0.9);

        await drag(page, await pointAt(slider, 0.5), await pointAt(track, 0.1));
        await page.mouse.click(point.x, point.y);

        await expect(slider).toHaveAttribute('aria-valuenow', '50');
        await expect(slider).not.toBeFocused();
    });

    test('leaves the thumb out of the tab order and ignores the keyboard when disabled', async ({
        page,
        gotoExample,
    }) => {
        await gotoExample('shadcn/slider/disabled');
        const slider = page.getByRole('slider', { name: 'Slider' });
        await expect(slider).toHaveAttribute('tabindex', '-1');
        // Assistive tech can still focus an element with tabindex="-1".
        await slider.focus();

        await page.keyboard.press('ArrowRight');
        await page.keyboard.press('End');

        await expect(slider).toHaveAttribute('aria-valuenow', '50');
        await expect(page.locator('input[name="disabled"]')).toHaveValue('50');
    });
});
