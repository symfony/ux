import { describeRecipe, expect, test } from '../../../../assets/test/browser/fixtures';

describeRecipe('shadcn/avatar', () => {
    test('gives the image its alternative text', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/avatar/basic');

        await expect(page.getByRole('img', { name: '@shadcn' })).toBeVisible();
    });
});
