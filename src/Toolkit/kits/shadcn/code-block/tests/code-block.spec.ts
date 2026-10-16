import type { Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const grantClipboard = (page: Page) => page.context().grantPermissions(['clipboard-read', 'clipboard-write']);

const readClipboard = (page: Page) => page.evaluate(() => navigator.clipboard.readText());

describeRecipe('shadcn/code-block', () => {
    testState('shows a check once the command is copied', {
        example: 'header',
        state: 'copied',
        act: async (page) => {
            await grantClipboard(page);
            // The copy icon comes back after 2 seconds, the screenshot must not race it.
            await page.clock.install();
            const copy = page.getByRole('button', { name: 'Copy' });

            await copy.click();
            await page.mouse.move(0, 0);

            await expect(page.getByRole('button', { name: 'Copied' })).toHaveAttribute('data-copied', 'true');
            await expect(page.locator('[aria-live="polite"]')).toHaveText('Copied');
            await expect.poll(() => readClipboard(page)).toBe('pnpm add ai @ai-sdk/react zod');
        },
    });

    test('shows the copy icon again after two seconds', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('shadcn/code-block/header', { timers: 'fake' });
        const copy = page.locator('[data-slot="code-block-copy-button"]');

        await copy.click();
        await expect(copy).toHaveAttribute('aria-label', 'Copied');

        await page.clock.runFor(2000);

        await expect(copy).toHaveAttribute('data-copied', 'false');
        await expect(copy).toHaveAttribute('aria-label', 'Copy');
        await expect(page.locator('[aria-live="polite"]')).toHaveText('');
    });

    test('copies the code with its blank lines', async ({ page, gotoExample }) => {
        await grantClipboard(page);
        await gotoExample('shadcn/code-block/show-more');

        await page.getByRole('button', { name: 'Copy' }).click();

        await expect.poll(() => readClipboard(page)).toMatch(/^"use client"\n\nimport \* as React from "react"\n/);
    });

    test('leaves the line numbers out of a selection, and keeps its blank lines', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/code-block/default');

        const selection = await page.locator('[data-slot="code-block-content"]').evaluate((content) => {
            const range = document.createRange();
            range.selectNodeContents(content);
            window.getSelection()?.addRange(range);

            return window.getSelection()?.toString();
        });

        expect(selection).toContain(
            'import { streamText } from "ai"\n\nexport async function POST(request: Request) {\n'
        );
        expect(selection).not.toMatch(/^1/);
    });

    testState('unwraps long lines', {
        example: 'actions',
        state: 'unwrapped',
        act: async (page) => {
            const toggle = page.getByRole('button', { name: 'Wrap lines' });
            const content = page.locator('[data-slot="code-block-content"]');
            await expect(content).toHaveAttribute('data-wrap');

            await toggle.click();
            await page.mouse.move(0, 0);

            await expect(toggle).toHaveAttribute('aria-pressed', 'false');
            await expect(content).not.toHaveAttribute('data-wrap');
            await expect(content).toHaveCSS('white-space', 'pre');
        },
    });

    test('downloads the output', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/code-block/actions');

        const download = page.waitForEvent('download');
        await page.getByRole('button', { name: 'Download' }).click();

        expect((await download).suggestedFilename()).toBe('lint.log');
    });

    testState('shows the whole file', {
        example: 'show-more',
        state: 'expanded',
        act: async (page) => {
            // Its accessible name changes once expanded.
            const toggle = page.locator('[data-action="code-block#toggleExpanded"]');
            const content = page.locator('[data-slot="code-block-content"]');
            await expect(content).toHaveCSS('max-height', '256px');

            await toggle.click();
            await page.mouse.move(0, 0);

            await expect(toggle).toHaveAttribute('aria-expanded', 'true');
            await expect(toggle).toHaveAccessibleName('Show less');
            await expect(content).toHaveAttribute('data-expanded');
            await expect(content).toHaveCSS('max-height', 'none');
        },
    });

    test('caps the expanded code at a higher height', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/code-block/max-height');
        const content = page.locator('[data-slot="code-block-content"]');

        await page.getByRole('button', { name: 'Show more' }).click();

        await expect(content).toHaveCSS('max-height', '384px');
        await expect(content).toHaveCSS('overflow-y', 'auto');
    });

    test('shows the floating actions when the content is focused', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/code-block/floating-actions');
        const actions = page.locator('[data-slot="code-block-actions"]');
        await expect(actions).toHaveCSS('opacity', '0');

        await page.locator('[data-slot="code-block-content"]').focus();

        await expect(actions).toHaveCSS('opacity', '1');
    });
});
