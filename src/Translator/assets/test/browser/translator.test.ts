import { expect, type Page, test } from '@playwright/test';

function expectOutputToBeEmpty(page: Page) {
    return expect(page.getByTestId('output')).toBeEmpty();
}

function expectOutputToContainText(page: Page, text: string) {
    return expect(page.getByTestId('output')).toContainText(text);
}

test('Can translate basic message', async ({ page }) => {
    await page.goto('/ux-translator/basic');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Hello!');
    await expectOutputToContainText(page, '🇫🇷 Bonjour !');
});

test('Can translate message with parameter', async ({ page }) => {
    await page.goto('/ux-translator/with-parameter');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Hello Fabien!');
    await expectOutputToContainText(page, '🇫🇷 Bonjour Fabien !');

    await page.getByLabel('Name').clear();
    await page.getByLabel('Name').fill('Hugo');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Hello Hugo!');
    await expectOutputToContainText(page, '🇫🇷 Bonjour Hugo !');
});

test('Can translate ICU message with `select` argument', async ({ page }) => {
    await page.goto('/ux-translator/icu-select');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Alex has invited you to her party!');
    await expectOutputToContainText(page, `🇫🇷 Alex t'a invité à sa fête !`);

    await page.getByLabel('Gender').selectOption({ label: 'Male' });
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Alex has invited you to his party!');
    await expectOutputToContainText(page, `🇫🇷 Alex t'a invité à sa fête !`);
});

test('Can translate ICU message with `plural` argument', async ({ page }) => {
    await page.goto('/ux-translator/icu-plural');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 There is one apple...');
    await expectOutputToContainText(page, '🇫🇷 Il y a une pomme...');

    await page.getByLabel('Apples').clear();
    await page.getByLabel('Apples').fill('0');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 There are no apples');
    await expectOutputToContainText(page, `🇫🇷 Il n'y a pas de pommes`);

    await page.getByLabel('Apples').clear();
    await page.getByLabel('Apples').fill('3');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 There are 3 apples!');
    await expectOutputToContainText(page, '🇫🇷 Il y a 3 pommes !');
});

test('Can translate ICU message with `selectordinal` argument', async ({ page }) => {
    await page.goto('/ux-translator/icu-selectordinal');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 You finished 1st!');
    await expectOutputToContainText(page, '🇫🇷 Tu as terminé 1er !');

    await page.getByLabel('Place').clear();
    await page.getByLabel('Place').fill('2');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 You finished 2nd!');
    await expectOutputToContainText(page, '🇫🇷 Tu as terminé 2e !');

    await page.getByLabel('Place').clear();
    await page.getByLabel('Place').fill('3');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 You finished 3rd!');
    await expectOutputToContainText(page, '🇫🇷 Tu as terminé 3e !');
});

test('Can translate ICU message with `date` and `time` arguments', async ({ page }) => {
    await page.goto('/ux-translator/icu-date-time');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Published at 4/27/2023 - 8:12 AM');
    await expectOutputToContainText(page, '🇫🇷 Publié le 27/04/2023 - 08:12');

    await page.getByLabel('Date').clear();
    await page.getByLabel('Date').fill('2024-03-17T09:30');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 Published at 3/17/2024 - 9:30 AM');
    await expectOutputToContainText(page, '🇫🇷 Publié le 17/03/2024 - 09:30');
});

test('Can translate ICU message with `number` and `percent` arguments', async ({ page }) => {
    await page.goto('/ux-translator/icu-number-percent');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 50% of the work is done');
    await expectOutputToContainText(page, '🇫🇷 50 % du travail est fait');

    await page.getByLabel('Progress').fill('0.75');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 75% of the work is done');
    await expectOutputToContainText(page, '🇫🇷 75 % du travail est fait');
});

test('Can translate ICU message with `number` and `currency` arguments', async ({ page }) => {
    await page.goto('/ux-translator/icu-number-currency');
    await expectOutputToBeEmpty(page);

    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 This artifact is worth €30.00');
    await expectOutputToContainText(page, '🇫🇷 Cet artéfact vaut 30,00 €');

    await page.getByLabel('Price').clear();
    await page.getByLabel('Price').fill('12.34');
    await page.getByRole('button', { name: 'Render' }).click();
    await expectOutputToContainText(page, '🇬🇧 This artifact is worth €12.34');
    await expectOutputToContainText(page, '🇫🇷 Cet artéfact vaut 12,34 €');
});
