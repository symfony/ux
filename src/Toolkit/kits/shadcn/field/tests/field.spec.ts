import type { Page } from '@playwright/test';
import { describeRecipe, expect, test } from '../../../../assets/test/browser/fixtures';

async function focusRingsInFocusedChoiceCard(page: Page): Promise<number> {
    return page.evaluate(async () => {
        await Promise.all(document.getAnimations().map((animation) => animation.finished));

        const label = document.activeElement?.closest('[data-slot="field-label"]');
        if (!label) {
            return 0;
        }

        return [label, ...label.querySelectorAll('*')].filter((element) =>
            / 3px(,|$)/.test(getComputedStyle(element).boxShadow)
        ).length;
    });
}

describeRecipe('shadcn/field', () => {
    for (const example of ['shadcn/field/choice-card', 'shadcn/switch/choice-card']) {
        test(`draws a single focus ring on a focused choice card of ${example}`, async ({ page, gotoExample }) => {
            await gotoExample(example);

            await page.keyboard.press('Tab');

            expect(await focusRingsInFocusedChoiceCard(page)).toBe(1);
        });
    }
});
