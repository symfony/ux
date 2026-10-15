import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const isActionLeftOfTitle = async (page: Page, title: string, action: string): Promise<boolean> => {
    const titleBox = await page.getByText(title, { exact: true }).boundingBox();
    const actionBox = await page.getByRole('button', { name: action, exact: true }).boundingBox();

    return actionBox!.x < titleBox!.x;
};

const expectDirection = async (page: Page, tab: string, dir: 'ltr' | 'rtl') => {
    await expect(page.getByRole('tab', { name: tab })).toHaveAttribute('aria-selected', 'true');

    const direction = page.getByRole('tabpanel', { name: tab }).locator('[data-slot="direction"]');

    await expect(direction).toBeVisible();
    await expect(direction).toHaveAttribute('dir', dir);
    await expect(direction).toHaveCSS('direction', dir);
};

describeRecipe('shadcn/direction', () => {
    test('renders the Arabic panel from right to left by default', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/direction/default');

        await expectDirection(page, 'Arabic (العربية)', 'rtl');
        expect(await isActionLeftOfTitle(page, 'تسجيل الدخول إلى حسابك', 'إنشاء حساب')).toBe(true);
    });

    testState('renders the English panel from left to right', {
        example: 'default',
        state: 'english',
        act: async (page) => {
            await page.getByRole('tab', { name: 'English' }).click();
            await page.mouse.move(0, 0);

            await expectDirection(page, 'English', 'ltr');
            expect(await isActionLeftOfTitle(page, 'Login to your account', 'Sign Up')).toBe(false);
        },
    });

    testState('renders the Hebrew panel from right to left', {
        example: 'default',
        state: 'hebrew',
        act: async (page) => {
            await page.getByRole('tab', { name: 'Hebrew (עברית)' }).click();
            await page.mouse.move(0, 0);

            await expectDirection(page, 'Hebrew (עברית)', 'rtl');
            expect(await isActionLeftOfTitle(page, 'התחברות לחשבון שלך', 'הרשמה')).toBe(true);
        },
    });
});
