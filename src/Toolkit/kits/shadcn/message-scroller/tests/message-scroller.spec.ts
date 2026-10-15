import type { Locator, Page } from '@playwright/test';
import { describeRecipe, expect, test, testState } from '../../../../assets/test/browser/fixtures';

const viewportIn = (scope: Page | Locator) => scope.locator('[data-slot="message-scroller-viewport"]').first();

const scrollTop = (viewport: Locator) => viewport.evaluate((element) => element.scrollTop);

const maxScrollTop = (viewport: Locator) => viewport.evaluate((element) => element.scrollHeight - element.clientHeight);

// Rounded, as text heights put rows on fractional pixels.
const topInViewport = (viewport: Locator, messageId: string) =>
    viewport.evaluate(
        (element, id) =>
            Math.round(
                element.querySelector(`[data-message-id="${id}"]`)!.getBoundingClientRect().top -
                    element.getBoundingClientRect().top
            ),
        messageId
    );

// Keyboard and smooth scrolls animate: wait until the position stops changing.
async function settledScrollTop(viewport: Locator) {
    let previous = -1;
    await expect
        .poll(async () => {
            const current = await scrollTop(viewport);
            const settled = current === previous;
            previous = current;

            return settled;
        })
        .toBe(true);

    return previous;
}

// Rows are added the way a Turbo Stream adds them: cloned from a rendered row, with another text and id.
async function addRow(
    viewport: Locator,
    {
        from,
        id,
        text,
        anchor = false,
        position = 'append',
    }: { from: string; id: string; text: string; anchor?: boolean; position?: 'append' | 'prepend' }
) {
    await viewport.evaluate(
        (element, { from, id, text, anchor, position }) => {
            const row = element.querySelector(`[data-message-id="${from}"]`)!.cloneNode(true) as HTMLElement;
            row.dataset.messageId = id;
            row.dataset.scrollAnchor = anchor ? 'true' : 'false';
            row.querySelector('[data-slot="bubble-content"]')!.textContent = text;
            element.querySelector('[data-slot="message-scroller-content"]')![position](row);
        },
        { from, id, text, anchor, position }
    );
}

describeRecipe('shadcn/message-scroller', () => {
    test('opens at the end with the button inactive', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/default');
        const viewport = viewportIn(page);
        const button = page.locator('[data-slot="message-scroller-button"]');

        await expect(viewport).not.toHaveAttribute('data-pending-scroll');
        expect(await scrollTop(viewport)).toBeCloseTo(await maxScrollTop(viewport), 0);
        await expect(button).toHaveAttribute('data-active', 'false');
        await expect(button).toHaveAttribute('inert');
        await expect(button).toHaveAttribute('tabindex', '-1');
    });

    testState('shows the button once the reader scrolls up', {
        example: 'default',
        state: 'scrolled-up',
        act: async (page) => {
            const viewport = viewportIn(page);
            const button = page.locator('[data-slot="message-scroller-button"]');

            await viewport.hover();
            await page.mouse.wheel(0, -400);
            await page.mouse.move(0, 0);

            await expect(button).toHaveAttribute('data-active', 'true');
            await expect(button).not.toHaveAttribute('inert');
            await expect(viewport).toHaveAttribute('data-scrollable', 'start end');
        },
    });

    test('scrolls back to the end with the button, which then gives the focus up', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/default');
        const viewport = viewportIn(page);
        const button = page.locator('[data-slot="message-scroller-button"]');
        await viewport.hover();
        await page.mouse.wheel(0, -400);
        await expect(button).toHaveAttribute('data-active', 'true');

        await button.click();

        // Checked at once: the smooth scroll has not reached the end, and the button is not inert yet.
        expect(await button.evaluate((element) => element === document.activeElement)).toBe(false);
        await expect.poll(async () => (await maxScrollTop(viewport)) - (await scrollTop(viewport))).toBeLessThan(1);
        await expect(button).toHaveAttribute('data-active', 'false');
    });

    testState('settles an appended turn near the top, below a peek of the previous row', {
        example: 'anchoring-turns',
        state: 'new-turn',
        act: async (page) => {
            const viewport = viewportIn(page);

            await addRow(viewport, {
                from: 'anchor-5',
                id: 'anchor-7',
                text: 'And what happens to the reply?',
                anchor: true,
            });
            await addRow(viewport, { from: 'anchor-6', id: 'anchor-8', text: 'It streams in below the turn.' });

            // The content padding (16px) plus the default peek of the previous row (64px).
            await expect.poll(() => topInViewport(viewport, 'anchor-7')).toBe(80);
            await expect(page.locator('[data-message-scroller-target="spacer"]')).toBeAttached();
            expect(
                await page.locator('[data-message-scroller-target="spacer"]').evaluate((spacer) => spacer.hidden)
            ).toBe(false);
        },
    });

    test('keeps the spacer after the rows appended to the end of the content', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/anchoring-turns');
        const viewport = viewportIn(page);

        await addRow(viewport, { from: 'anchor-5', id: 'anchor-7', text: 'One more turn.', anchor: true });

        await expect
            .poll(() =>
                viewport.evaluate(
                    (element) =>
                        element.querySelector('[data-slot="message-scroller-content"]')!.lastElementChild!.dataset
                            .messageScrollerTarget
                )
            )
            .toBe('spacer');
    });

    test('follows a growing reply only while the reader stays at the end', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/following-the-live-edge');
        const viewport = viewportIn(page);
        const grow = () =>
            viewport.evaluate((element) => {
                const bubble = element.querySelector('[data-message-id="stream-4"] [data-slot="bubble-content"]')!;
                bubble.textContent += ' It also leaves a small peek of the previous exchange visible above the anchor.';
            });

        await grow();
        await grow();
        await expect.poll(async () => (await maxScrollTop(viewport)) - (await scrollTop(viewport))).toBeLessThan(1);

        await viewport.hover();
        await page.mouse.wheel(0, -200);
        const releasedAt = await settledScrollTop(viewport);
        expect(releasedAt).toBeLessThan((await maxScrollTop(viewport)) - 100);
        await grow();
        await grow();

        await expect.poll(() => maxScrollTop(viewport)).toBeGreaterThan(releasedAt + 200);
        expect(await scrollTop(viewport)).toBeCloseTo(releasedAt, 0);

        await page.locator('[data-slot="message-scroller-button"]').click();
        await expect.poll(async () => (await maxScrollTop(viewport)) - (await scrollTop(viewport))).toBeLessThan(1);
        await grow();

        await expect.poll(async () => (await maxScrollTop(viewport)) - (await scrollTop(viewport))).toBeLessThan(1);
    });

    test('stops following when the reader scrolls with the keyboard', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/following-the-live-edge');
        const viewport = viewportIn(page);

        await viewport.focus();
        await page.keyboard.press('PageUp');
        const releasedAt = await settledScrollTop(viewport);
        expect(releasedAt).toBeLessThan((await maxScrollTop(viewport)) - 100);
        await viewport.evaluate((element) => {
            element.querySelector('[data-message-id="stream-4"] [data-slot="bubble-content"]')!.textContent +=
                ' More text.'.repeat(40);
        });

        await expect.poll(() => maxScrollTop(viewport)).toBeGreaterThan(releasedAt + 200);
        expect(await scrollTop(viewport)).toBeCloseTo(releasedAt, 0);
    });

    test('keeps the reader on the same row when older rows are prepended', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/loading-earlier-messages');
        const viewport = viewportIn(page);
        // Chromium keeps the row in place by itself: turn its scroll anchoring off, as in Safari.
        await viewport.evaluate((element) => {
            element.style.overflowAnchor = 'none';
        });
        const before = await topInViewport(viewport, 'history-5');

        for (const id of ['history-old-1', 'history-old-2', 'history-old-3']) {
            await addRow(viewport, { from: 'history-2', id, text: 'An older message.', position: 'prepend' });
        }

        await expect
            .poll(async () => Math.abs((await topInViewport(viewport, 'history-5')) - before))
            .toBeLessThanOrEqual(1);
    });

    test('applies the opening position of a transcript once its tab is shown', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/opening-saved-threads');
        const panel = (name: string) => page.getByRole('tabpanel', { name });

        // The content padding (16px) plus the default peek of the previous row (64px).
        expect(await topInViewport(viewportIn(panel('last-anchor')), 'open-3')).toBe(80);

        await page.getByRole('tab', { name: 'start' }).click();
        await expect(viewportIn(panel('start'))).not.toHaveAttribute('data-pending-scroll');
        expect(await scrollTop(viewportIn(panel('start')))).toBe(0);

        await page.getByRole('tab', { name: 'end' }).click();
        const end = viewportIn(panel('end'));
        await expect(end).not.toHaveAttribute('data-pending-scroll');
        expect(await scrollTop(end)).toBeCloseTo(await maxScrollTop(end), 0);
    });

    testState('jumps to a message from the menu', {
        example: 'jumping-to-messages',
        state: 'jumped',
        act: async (page) => {
            const viewport = viewportIn(page);

            await page.getByRole('button', { name: 'Jump to...' }).click();
            await page.getByRole('menuitem', { name: 'What should I compare before we' }).click();
            await page.mouse.move(0, 0);

            await expect.poll(() => topInViewport(viewport, 'command-compare')).toBe(16);
        },
    });

    test('marks the outline link of the current turn', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/tracking-the-reader-s-position');
        const link = (name: string) => page.locator('[data-message-scroller-target="anchorLink"]', { hasText: name });

        // At the end, the last turn has not reached the line where turns settle yet: the one before is current.
        await expect(link('Can you turn that into an experiment?')).toHaveAttribute('aria-current', 'location');
        await expect(link("What's the risk if we delay the")).toHaveAttribute('aria-current', 'false');

        await page.getByRole('button', { name: 'Open transcript outline' }).hover();
        await link("We're seeing activation dip").click();

        await expect(link("We're seeing activation dip")).toHaveAttribute('aria-current', 'location');
        await expect(link('Can you turn that into an experiment?')).toHaveAttribute('aria-current', 'false');
    });

    testState('reports that the reader can scroll both ways', {
        example: 'reading-scroll-state',
        state: 'middle',
        act: async (page) => {
            const viewport = viewportIn(page);

            await viewport.hover();
            await page.mouse.wheel(0, 800);
            await page.mouse.move(0, 0);

            await expect(page.getByText('You can scroll both ways.')).toBeVisible();
            await expect(page.getByText('You are at the top. You can only scroll down.')).toBeHidden();
        },
    });

    test('reports that the reader is at the end', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/reading-scroll-state');
        const viewport = viewportIn(page);

        await expect(page.getByText('You are at the top. You can only scroll down.')).toBeVisible();
        await viewport.evaluate((element) => element.scrollTo({ top: element.scrollHeight }));

        await expect(page.getByText('You are at the bottom. You can only scroll up.')).toBeVisible();
    });

    test('still anchors new turns after being moved in the DOM', async ({ page, gotoExample }) => {
        await gotoExample('shadcn/message-scroller/anchoring-turns');
        await page.locator('[data-controller="message-scroller"]').evaluate(async (element) => {
            const parent = element.parentNode!;
            const next = element.nextSibling;
            element.remove();
            await new Promise((resolve) => setTimeout(resolve, 50));
            parent.insertBefore(element, next);
        });
        const viewport = viewportIn(page);

        await addRow(viewport, { from: 'anchor-5', id: 'anchor-7', text: 'Still there?', anchor: true });

        await expect.poll(() => topInViewport(viewport, 'anchor-7')).toBe(80);
    });
});
