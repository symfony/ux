import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const wrapInForm = (page: Page) =>
    page.locator('[data-controller="password-visibility"]').evaluate((element) => {
        const form = document.createElement('form');
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            form.dataset.submitted = 'true';
        });
        element.replaceWith(form);
        form.append(element);
    });

describeRecipe('common/password-visibility', () => {
    testState('shows the password', {
        example: 'default',
        state: 'visible',
        act: async (page) => {
            await page.getByRole('button', { name: 'Show password', exact: true }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'text');
            await expect(page.getByRole('button', { name: 'Hide password', exact: true })).toBeVisible();
        },
    });

    test('hides the password again', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');

        await page.getByRole('button', { name: 'Show password', exact: true }).click();
        await page.getByRole('button', { name: 'Hide password', exact: true }).click();

        await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password', exact: true })).toBeVisible();
    });

    testState('swaps the text labels', {
        example: 'text-labels',
        state: 'visible',
        act: async (page) => {
            await page.getByRole('button', { name: 'Show', exact: true }).click();
            await page.mouse.move(0, 0);

            await expect(page.getByLabel('New password')).toHaveAttribute('type', 'text');
            await expect(page.getByRole('button', { name: 'Hide', exact: true })).toBeVisible();
        },
    });

    test('gives the focus back to the field after a click, with the same selection', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        const field = page.getByLabel('Password', { exact: true });
        await field.focus();
        await field.evaluate((input: HTMLInputElement) => input.setSelectionRange(3, 8));

        await page.getByRole('button', { name: 'Show password', exact: true }).click();

        await expect(field).toBeFocused();
        expect(await field.evaluate((input: HTMLInputElement) => [input.selectionStart, input.selectionEnd])).toEqual([
            3, 8,
        ]);
    });

    test('keeps the focus on the button when toggled with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await page.getByRole('button', { name: 'Show password', exact: true }).focus();

        await page.keyboard.press('Enter');

        await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'text');
        await expect(page.getByRole('button', { name: 'Hide password', exact: true })).toBeFocused();
    });

    test('hides the password again when its form is submitted', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await wrapInForm(page);
        const field = page.getByLabel('Password', { exact: true });
        await page.getByRole('button', { name: 'Show password', exact: true }).click();

        await field.press('Enter');

        await expect(page.locator('form')).toHaveAttribute('data-submitted', 'true');
        await expect(field).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password', exact: true })).toBeVisible();
    });

    test('keeps a hidden password hidden when its form is submitted', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await wrapInForm(page);
        const field = page.getByLabel('Password', { exact: true });

        await field.press('Enter');

        await expect(page.locator('form')).toHaveAttribute('data-submitted', 'true');
        await expect(field).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password', exact: true })).toBeVisible();
    });

    test('hides the password again when its form is reset', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await wrapInForm(page);
        await page.getByRole('button', { name: 'Show password', exact: true }).click();

        await page.locator('form').evaluate((form: HTMLFormElement) => form.reset());

        await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password', exact: true })).toBeVisible();
    });

    test('hides the password again when it connects again', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await page.getByRole('button', { name: 'Show password', exact: true }).click();

        // Turbo restores a page from its cache as a copy of its elements, with the password still visible.
        await page
            .locator('[data-controller="password-visibility"]')
            .evaluate((element) => element.replaceWith(element.cloneNode(true)));

        await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password', exact: true })).toBeVisible();
    });

    test('hides the password again when the page is left', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await page.getByRole('button', { name: 'Show password', exact: true }).click();

        await page.evaluate(() => window.dispatchEvent(new PageTransitionEvent('pagehide', { persisted: true })));

        await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'password');
        await expect(page.getByRole('button', { name: 'Show password', exact: true })).toBeVisible();
    });

    test('keeps the button from submitting its form', async ({ page, gotoExample }) => {
        await gotoExample('common/password-visibility/default');
        await wrapInForm(page);
        // A button without a type submits the form it belongs to.
        await page.getByRole('button').evaluate((button) => button.removeAttribute('type'));

        await page.getByRole('button', { name: 'Show password', exact: true }).click();

        await expect(page.getByLabel('Password', { exact: true })).toHaveAttribute('type', 'text');
        await expect(page.locator('form')).not.toHaveAttribute('data-submitted');
    });
});
