import { expect, test } from '@playwright/test';

/**
 * Behaviour that only a real browser can show: the chunked round-trip, the
 * Stimulus controller reacting to a selection, and computed styles.
 *
 * Everything that can be asserted on the rendered markup alone lives in the PHP
 * rendering tests instead (tests/Rendering/FormThemeRenderingTest.php).
 */

const file = (name: string, contents: string) => ({
    name,
    mimeType: 'text/plain',
    buffer: Buffer.from(contents),
});

test('uploads a single file and writes the completion token back', async ({ page }) => {
    await page.goto('/ux-upload/');

    await page.locator('.ux-upload__input').setInputFiles(file('hello.txt', 'hello world'));

    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();

    // The round-trip succeeded only if the controller stored the signed token
    // in the form's hidden input.
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue(/"token"/);
});

test('uploads several files at once', async ({ page }) => {
    await page.goto('/ux-upload/multiple');

    await page.locator('.ux-upload__input').setInputFiles([
        file('first.txt', 'first file'),
        file('second.txt', 'second file'),
    ]);

    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toHaveCount(2);
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue(/"token"/);
});

test('uploads through a theme whose presentation blocks are empty', async ({ page }) => {
    await page.goto('/ux-upload/minimal-theme');

    await page.locator('.ux-upload__input').setInputFiles(file('minimal.txt', 'minimal template'));

    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    await expect(page.locator('.ux-upload__visual')).toHaveCount(0);
    await expect(page.locator('.ux-upload__progress')).toHaveCount(0);
    await expect(page.locator('.ux-upload__actions')).toHaveCount(0);
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue(/"token"/);
});

test('reveals the manual start button once a file is selected', async ({ page }) => {
    await page.goto('/ux-upload/manual');

    const button = page.locator('.ux-upload > .ux-upload__start');
    await expect(button).toBeHidden();

    await page.locator('.ux-upload__input').setInputFiles(file('manual.txt', 'manual upload'));

    await expect(button).toBeVisible();
    await expect(button).toHaveAttribute('data-action', 'symfony--ux-upload--upload#startAll');
});

test('shows only the actions that apply to a completed item', async ({ page }) => {
    await page.goto('/ux-upload/');

    await page.locator('.ux-upload__input').setInputFiles(file('actions.txt', 'action states'));

    const item = page.locator('.ux-upload__item[data-status="completed"]');
    await expect(item).toBeVisible();
    await expect(item.locator('[data-ux-upload-action="remove"]')).toBeVisible();
    await expect(item.locator('[data-ux-upload-action="pause"]')).toBeHidden();
    await expect(item.locator('[data-ux-upload-action="resume"]')).toBeHidden();
    await expect(item.locator('[data-ux-upload-action="cancel"]')).toBeHidden();
    await expect(item.locator('[data-ux-upload-action="retry"]')).toBeHidden();
});

test('prevents a second immediate form submission', async ({ page }) => {
    await page.goto('/ux-upload/');

    const state = await page.evaluate(() => {
        const form = document.querySelector('form') as HTMLFormElement;
        const submitter = form.querySelector('button[type="submit"]') as HTMLButtonElement;
        const first = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter });
        const second = new SubmitEvent('submit', { bubbles: true, cancelable: true, submitter });

        return {
            firstAccepted: form.dispatchEvent(first),
            secondAccepted: form.dispatchEvent(second),
            secondPrevented: second.defaultPrevented,
            busy: form.getAttribute('aria-busy'),
        };
    });

    // firstAccepted is deliberately not asserted: the host application also runs
    // Turbo, which cancels the first submit for its own handling. What matters is
    // that the guard marks the form busy and blocks anything that follows.
    expect(state.busy).toBe('true');
    expect(state.secondAccepted).toBe(false);
    expect(state.secondPrevented).toBe(true);
});

test('optional styles follow the application color scheme', async ({ page }) => {
    const background = async () =>
        page
            .locator('.ux-upload__dropzone')
            .evaluate((element) => getComputedStyle(element).backgroundColor);

    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/ux-upload/');
    expect(await background()).toBe('rgb(255, 255, 255)');

    await page.emulateMedia({ colorScheme: 'dark' });
    await page.goto('/ux-upload/');
    expect(await background()).toBe('rgb(24, 24, 27)');
});
