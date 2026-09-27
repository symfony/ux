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

test.describe('Css conditions', () => {
    const danger = 'color(srgb 0.8627 0.149 0.149)';
    const primary = 'color(srgb 0.1451 0.3882 0.9216)';

    test('A condition hash applies while the element is focused or pressed', async ({ page }) => {
        await page.goto('/ux-css/conditions');
        const button = page.getByTestId('css-focus-active');
        await expect(button).toHaveCSS('color', danger);

        await button.focus();
        await expect(button).toHaveCSS('color', primary);

        await button.hover();
        await page.mouse.down();
        await expect(button).toHaveCSS('text-decoration-line', 'underline');

        await page.mouse.up();
        await expect(button).toHaveCSS('text-decoration-line', 'none');
    });

    test('A condition hash applies to disabled and checked form controls', async ({ page }) => {
        await page.goto('/ux-css/conditions');
        await expect(page.getByTestId('css-disabled')).toHaveCSS('opacity', '0.5');

        const checkbox = page.getByTestId('css-checked');
        await expect(checkbox).toHaveCSS('opacity', '1');

        await checkbox.check();
        await expect(checkbox).toHaveCSS('opacity', '0.5');
    });

    test('A condition nested in a breakpoint applies only from that breakpoint', async ({ page }) => {
        await page.setViewportSize({ width: 500, height: 800 });
        await page.goto('/ux-css/conditions');
        const box = page.getByTestId('css-breakpoint');
        await expect(box).toHaveCSS('padding', '16px');

        await box.hover();
        await expect(box).toHaveCSS('color', danger);

        await page.setViewportSize({ width: 1024, height: 800 });
        await expect(box).toHaveCSS('padding', '32px');

        await box.hover();
        await expect(box).toHaveCSS('color', primary);
    });

    test('A conditional value applies its condition on the property', async ({ page }) => {
        await page.goto('/ux-css/conditions');
        const box = page.getByTestId('css-conditional-value');
        await expect(box).toHaveCSS('color', danger);

        await box.hover();
        await expect(box).toHaveCSS('color', primary);
    });

    test('A group condition applies when the parent is hovered', async ({ page }) => {
        await page.goto('/ux-css/conditions');
        const group = page.getByTestId('css-group');
        const child = page.getByTestId('css-group-child');
        await expect(child).toHaveCSS('color', danger);

        const groupBox = await group.boundingBox();
        await group.hover({ position: { x: groupBox!.width - 2, y: groupBox!.height / 2 } });
        await expect(child).toHaveCSS('color', primary);
    });

    test('A media condition follows the color scheme of the system', async ({ page }) => {
        await page.emulateMedia({ colorScheme: 'light' });
        await page.goto('/ux-css/conditions');
        const box = page.getByTestId('css-os-dark');
        await expect(box).toHaveCSS('color', danger);

        await page.emulateMedia({ colorScheme: 'dark' });
        await expect(box).toHaveCSS('color', primary);
    });
});
