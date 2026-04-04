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
