import { expect, test } from '@playwright/test';

test('Can display and interact with AJAX-based autocomplete field', async ({ page }) => {
    await page.goto('/ux-autocomplete/with-ajax');

    await expect(page.locator('css=.ts-wrapper'), 'TomSelect has not been initialized.').toBeVisible();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('');

    const responsePromise = page.waitForResponse('**/autocomplete/fruit_autocomplete_field?query=');
    await page.getByRole('combobox', { name: 'Favorite fruit' }).click();
    await responsePromise;
    await page.getByRole('option', { name: 'Apple' }).click();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('apple');

    await page.getByText('⨯').click();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('');

    await page.getByRole('combobox', { name: 'Favorite fruit' }).click();
    await page.getByRole('option', { name: 'Grape' }).click();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('grape');
});

test('Can reach all the options of a multiple AJAX-based autocomplete field, when most of the first ones are selected', async ({
    page,
}) => {
    await page.goto('/ux-autocomplete/with-ajax-multiple');

    await expect(page.locator('css=.ts-wrapper'), 'TomSelect has not been initialized.').toBeVisible();

    // 8 of the 10 fruits of the first results page are already selected, they must be excluded server-side
    const responsePromise = page.waitForResponse('**/autocomplete/fruit_multiple_autocomplete_field?query=&excluded*');
    await page.getByRole('combobox', { name: 'Favorite fruits' }).click();
    await responsePromise;

    // the 11th fruit is reachable without scrolling
    await page.getByRole('option', { name: 'Peach' }).click();
    await expect(page.locator('css=select[id="form_favorite_fruits"] option:checked')).toHaveCount(9);
});

test('Can display and interact with non-AJAX-based autocomplete field', async ({ page }) => {
    await page.goto('/ux-autocomplete/without-ajax');

    await expect(page.locator('css=.ts-wrapper'), 'TomSelect has not been initialized.').toBeVisible();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('apple');

    await page.getByText('⨯').click();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('');

    await page.getByRole('combobox', { name: 'Favorite fruit' }).click();
    await page.getByRole('option', { name: 'Grape' }).filter({ visible: true }).click();
    await expect(page.locator('css=select[id="form_favorite_fruit"]')).toHaveValue('grape');
});
