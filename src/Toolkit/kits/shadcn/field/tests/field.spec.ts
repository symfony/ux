import type { Locator } from '@playwright/test';
import { describeRecipe, expect, test } from '../../../../assets/test/browser/fixtures';

async function focusRingsOf(choiceCard: Locator): Promise<{ onCard: boolean; total: number }> {
    return choiceCard.evaluate(async (label) => {
        await Promise.all(document.getAnimations().map((animation) => animation.finished));

        const hasRing = (element: Element) => / 3px(,|$)/.test(getComputedStyle(element).boxShadow);

        return { onCard: hasRing(label), total: [label, ...label.querySelectorAll('*')].filter(hasRing).length };
    });
}

describeRecipe('shadcn/field', () => {
    for (const example of ['shadcn/field/choice-card', 'shadcn/switch/choice-card', 'shadcn/checkbox/default']) {
        test(`draws the focus ring on the choice card only in ${example}`, async ({ page, gotoExample }) => {
            await gotoExample(example);

            // The first controls of an example may sit outside a choice card.
            const choiceCard = page.locator('[data-slot="field-label"]:has(> [data-slot="field"]):focus-within');
            for (let presses = 0; presses < 10 && 0 === (await choiceCard.count()); presses++) {
                await page.keyboard.press('Tab');
            }
            await expect(choiceCard).toHaveCount(1);

            const rings = await focusRingsOf(choiceCard);

            expect(rings).toEqual({ onCard: true, total: 1 });
        });
    }
});
