import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';
import type { Page } from '@playwright/test';

const slot = (page: Page, input: number, inputs = 6) => page.getByLabel(`Input ${input} of ${inputs}`, { exact: true });

const pasteText = async (page: Page, text: string) => {
    await page.context().grantPermissions(['clipboard-read', 'clipboard-write']);
    await page.evaluate((value) => navigator.clipboard.writeText(value), text);
    await page.keyboard.press('ControlOrMeta+V');
};

describeRecipe('shadcn/input-otp', () => {
    testState('fills the slots as the code is typed', {
        example: 'controlled',
        state: 'filled',
        act: async (page) => {
            await slot(page, 1).click();

            await page.keyboard.type('123456');

            for (const [index, digit] of [...'123456'].entries()) {
                await expect(slot(page, index + 1)).toHaveValue(digit);
            }
            await expect(page.getByText('You entered: 123456')).toBeVisible();
        },
    });

    test('moves the focus to the next slot on each character, across groups', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/separator');
        await slot(page, 1).click();

        await page.keyboard.type('1');

        await expect(slot(page, 1)).toHaveValue('1');
        await expect(slot(page, 2)).toBeFocused();

        await page.keyboard.type('2');

        await expect(slot(page, 2)).toHaveValue('2');
        await expect(slot(page, 3)).toBeFocused();
    });

    test('moves between slots with the arrow keys', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/separator');
        await slot(page, 1).click();

        await page.keyboard.press('ArrowRight');
        await page.keyboard.press('ArrowRight');

        await expect(slot(page, 3)).toBeFocused();

        await page.keyboard.press('ArrowLeft');

        await expect(slot(page, 2)).toBeFocused();
    });

    test('clears the slot then steps back on Backspace', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/separator');
        await slot(page, 1).click();
        await page.keyboard.type('12');
        await expect(slot(page, 3)).toBeFocused();

        await page.keyboard.press('Backspace');

        await expect(slot(page, 2)).toBeFocused();
        await expect(slot(page, 2)).toHaveValue('2');

        await page.keyboard.press('Backspace');

        await expect(slot(page, 2)).toBeFocused();
        await expect(slot(page, 2)).toHaveValue('');

        await page.keyboard.press('Backspace');

        await expect(slot(page, 1)).toBeFocused();
        await expect(slot(page, 1)).toHaveValue('1');
    });

    test('keeps one character per slot and stops at the last slot', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/four-digits');
        await slot(page, 1, 4).click();

        await page.keyboard.type('123456');

        for (const [index, digit] of [...'1234'].entries()) {
            await expect(slot(page, index + 1, 4)).toHaveValue(digit);
        }
        await expect(slot(page, 4, 4)).toBeFocused();
    });

    test('spreads a pasted code over the slots', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/controlled');
        await slot(page, 1).click();

        await pasteText(page, '123456');

        for (const [index, digit] of [...'123456'].entries()) {
            await expect(slot(page, index + 1)).toHaveValue(digit);
        }
        await expect(slot(page, 6)).toBeFocused();
        await expect(page.getByText('You entered: 123456')).toBeVisible();
    });

    test('drops the pasted characters that do not fit in the slots', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/four-digits');
        await slot(page, 1, 4).click();

        await pasteText(page, '123456');

        for (const [index, digit] of [...'1234'].entries()) {
            await expect(slot(page, index + 1, 4)).toHaveValue(digit);
        }
        await expect(slot(page, 4, 4)).toBeFocused();
    });

    test('ignores the typed characters that do not match the pattern', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/pattern');
        await slot(page, 1).click();

        await page.keyboard.type('a');

        await expect(slot(page, 1)).toHaveValue('');
        await expect(slot(page, 1)).toBeFocused();

        await page.keyboard.type('1b2');

        await expect(slot(page, 1)).toHaveValue('1');
        await expect(slot(page, 2)).toHaveValue('2');
        await expect(slot(page, 3)).toBeFocused();
    });

    test('keeps only the pasted characters that match the pattern', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/pattern');
        await slot(page, 1).click();

        await pasteText(page, '1a-2b 3456');

        for (const [index, digit] of [...'123456'].entries()) {
            await expect(slot(page, index + 1)).toHaveValue(digit);
        }
    });

    test('accepts letters and digits in the alphanumeric example', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/input-otp/alphanumeric');
        await slot(page, 1).click();

        await page.keyboard.type('a-B3');

        await expect(slot(page, 1)).toHaveValue('a');
        await expect(slot(page, 2)).toHaveValue('B');
        await expect(slot(page, 3)).toHaveValue('3');
        await expect(slot(page, 4)).toBeFocused();
    });
});
