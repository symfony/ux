import { expect, test } from '@playwright/test';

test.describe('Css', () => {
    test('Static css() calls get their styles from the generated stylesheet', async ({ page }) => {
        await page.goto('/ux-css/basic');

        const box = page.getByTestId('css-box');
        await expect(box).toHaveCSS('padding', '16px');
        await expect(box).toHaveCSS('color', 'color(srgb 0.8627 0.149 0.149)');

        await box.hover();
        await expect(box).toHaveCSS('color', 'color(srgb 0.1451 0.3882 0.9216)');
    });

    test('Breakpoints apply from their minimum width', async ({ page }) => {
        await page.setViewportSize({ width: 500, height: 800 });
        await page.goto('/ux-css/basic');
        await expect(page.getByTestId('css-box')).toHaveCSS('margin-top', '0px');

        await page.setViewportSize({ width: 1024, height: 800 });
        await expect(page.getByTestId('css-box')).toHaveCSS('margin-top', '32px');
    });

    test('Dynamic css() calls get their styles from static_css', async ({ page }) => {
        await page.goto('/ux-css/basic?tone=primary');

        await expect(page.getByTestId('css-dynamic')).toHaveCSS('color', 'color(srgb 0.1451 0.3882 0.9216)');
    });
});
