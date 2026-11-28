import { describeRecipe, expect, test } from '../../../../assets/test/browser/fixtures';

describeRecipe('flowbite-4/pagination', () => {
    test('keeps the color of the current page on hover', async ({ page, gotoExample }) => {
        await gotoExample('flowbite-4/pagination/default');
        const current = page.getByRole('link', { name: '2', exact: true });
        const color = await current.evaluate((link) => getComputedStyle(link).color);

        await current.hover();

        await expect(current).toHaveAttribute('aria-current', 'page');
        await expect(current).toHaveCSS('color', color);
    });
});
