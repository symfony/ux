import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const MESSAGE = 'The password strength is too low. Please use a stronger password.';

// The scores `PasswordStrengthValidator::estimateStrength()` of symfony/validator gives these passwords.
const SCORES: [string, number][] = [
    ['password', 0],
    ['aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 0],
    ['Tr0ub4dor&3', 1],
    ['abcdefghijklmnopqrstu', 2],
    ['Sym-f0ny_UX!rocks', 3],
    ['correct horse battery staple', 4],
    // Each case sits just above or just below a threshold, so a wrong size for a character set changes a score.
    ['0123450000000000000000000000000000000', 3],
    ['012345678900000000', 0],
    ['ABCAAAAAAAAAAAAAAAAAAAAAAAAAAAAA', 1],
    ['ABCDEFGHIJKLA', 0],
    ['abcaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 1],
    ['abcdefghijkla', 0],
    ['!#$%&!!!!!!!!!!!!!!!', 1],
    ['!#$%&*+-./:;<=>?@^!!!!!!!', 3],
    ['abcdefghijkl\t\t', 2],
    ['abcdefghijklmnaa\t\t', 2],
    ['abcdefghié', 1],
    // PHP rates the UTF-8 bytes of a password, not its characters.
    // The first one has 16 bytes, 8 of them distinct: exactly the 80 bits of a medium password.
    // Counting characters instead would rate it very weak.
    ['éèêàçùâé', 2],
    ['éèêàçùâ', 1],
    // 48 bytes, 2 of them distinct: exactly the 60 bits of a weak password.
    ['éééééééééééééééééééééééé', 1],
    ['🔑🔒🗝️', 2],
];

describeRecipe('common/password-strength', () => {
    testState('rates the password as you type', {
        example: 'default',
        state: 'strong',
        act: async (page) => {
            const input = page.getByLabel('Password');

            await input.fill('Sym-f0ny_UX!rocks');

            await expect(page.getByText('Strong', { exact: true })).toBeVisible();
            await expect(input).toHaveAccessibleDescription('Strong');
        },
    });

    test('scores passwords like the PasswordStrength constraint', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/default');
        const input = page.getByLabel('Password');
        const controller = page.locator('[data-controller="password-strength"]');

        for (const [password, score] of SCORES) {
            await input.fill(password);

            await expect(controller, password).toHaveAttribute('data-password-strength-score-value', String(score));
        }
    });

    test('rates the password already in the field when it connects', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/default');

        await page.evaluate(() =>
            document.body.insertAdjacentHTML(
                'beforeend',
                '<div id="prefilled" data-controller="password-strength"><input type="password" value="correct horse battery staple" data-password-strength-target="input"></div>'
            )
        );

        await expect(page.locator('#prefilled')).toHaveAttribute('data-password-strength-score-value', '4');
    });

    test('dispatches password-strength:change when the score changes', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/default');
        const scores = await page.evaluateHandle(() => {
            const scores: (number | null)[] = [];
            document.addEventListener('password-strength:change', (event) =>
                scores.push((event as CustomEvent<{ score: number | null }>).detail.score)
            );

            return scores;
        });
        const input = page.getByLabel('Password');

        await input.fill('pass');
        await input.fill('password');
        await input.fill('correct horse battery staple');
        await input.fill('');

        expect(await scores.jsonValue()).toEqual([0, 4, null]);
    });

    test('leaves the label untouched while the score stays the same', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/default');
        const input = page.getByLabel('Password');
        const label = page.locator('#password-strength');
        await input.fill('a');
        const text = await label.evaluateHandle((element) => element.firstChild);

        await input.fill('ab');

        await expect(label).toHaveText('Very weak');
        expect(await label.evaluate((element, text) => element.firstChild === text, text)).toBe(true);
    });

    testState('fills the meter with the score', {
        example: 'native-meter',
        state: 'medium',
        act: async (page) => {
            await page.getByLabel('Password', { exact: true }).fill('abcdefghijklmnopqrstu');

            await expect(page.getByRole('meter')).toHaveJSProperty('value', 2);
            await expect(page.getByRole('meter')).toHaveAttribute('aria-valuetext', 'Medium');
            await expect(page.getByText('Medium', { exact: true })).toBeVisible();
        },
    });

    test('clears the score once the field is emptied', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/native-meter');
        const input = page.getByLabel('Password', { exact: true });
        const meter = page.getByRole('meter');
        await input.fill('correct horse battery staple');
        await expect(meter).toHaveJSProperty('value', 4);

        await input.fill('');

        await expect(page.locator('[data-controller="password-strength"]')).not.toHaveAttribute(
            'data-password-strength-score-value'
        );
        await expect(meter).toHaveJSProperty('value', 0);
        await expect(meter).not.toHaveAttribute('aria-valuetext');
        await expect(page.locator('[data-password-strength-target="label"]')).toBeEmpty();
    });

    test('moves the meter back to its minimum once the field is emptied', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/default');
        await page.evaluate(() =>
            document.body.insertAdjacentHTML(
                'beforeend',
                '<div data-controller="password-strength"><input type="password" id="below-zero" data-password-strength-target="input"><meter min="-1" max="4" data-password-strength-target="meter"></meter></div>'
            )
        );
        const input = page.locator('#below-zero');
        const meter = page.locator('meter');
        await input.fill('a');
        await expect(meter).toHaveJSProperty('value', 0);

        await input.fill('');

        await expect(meter).toHaveJSProperty('value', -1);
    });

    testState('shows the custom labels', {
        example: 'custom-labels',
        state: 'strong',
        act: async (page) => {
            await page.getByLabel('Passphrase').fill('Sym-f0ny_UX!rocks');

            await expect(page.getByText('Good', { exact: true })).toBeVisible();
        },
    });

    testState('marks a password below the min score as invalid', {
        example: 'block-weak-passwords',
        state: 'invalid',
        act: async (page) => {
            const input = page.getByLabel('New password');

            await input.fill('Tr0ub4dor&3');
            await input.blur();

            await expect(input).toHaveJSProperty('validationMessage', MESSAGE);
            await expect(page.getByText('Weak', { exact: true })).toBeVisible();
        },
    });

    test('blocks the submission of a password below the min score', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/block-weak-passwords');
        const input = page.getByLabel('New password');
        await input.fill('Tr0ub4dor&3');

        await page.getByRole('button', { name: 'Change password' }).click();

        await expect(input).toBeFocused();
        await expect(page).toHaveURL(/\/block-weak-passwords\?/);
    });

    test('accepts a password reaching the min score', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/block-weak-passwords');
        const input = page.getByLabel('New password');

        await input.fill('Sym-f0ny_UX!rocks');

        await expect(input).toHaveJSProperty('validationMessage', '');
        expect(await input.evaluate((element: HTMLInputElement) => element.form?.checkValidity())).toBe(true);
    });

    test('leaves an empty field to the required attribute', async ({ page, gotoExample }) => {
        await gotoExample('common/password-strength/block-weak-passwords');
        const input = page.getByLabel('New password');
        await input.fill('Tr0ub4dor&3');

        await input.fill('');

        expect(await input.evaluate((element: HTMLInputElement) => element.validity.customError)).toBe(false);
        expect(await input.evaluate((element: HTMLInputElement) => element.validity.valueMissing)).toBe(true);
    });
});
