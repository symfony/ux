/// <reference types="node" />
import { createHash } from 'node:crypto';
import { writeFileSync } from 'node:fs';
import { deflateSync } from 'node:zlib';
import { expect, type Locator, type Page, type Route, test } from '@playwright/test';

/**
 * Files up to the chunk size take the single-request direct upload; the
 * /ux-upload/form page uses 64 KiB chunks, so larger files there are chunked.
 * The /ux-upload/gallery page uses the dropzone layout with image previews.
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

    await page
        .locator('.ux-upload__input')
        .setInputFiles([file('first.txt', 'first file'), file('second.txt', 'second file')]);

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

    expect(state.firstAccepted).toBe(true);
    expect(state.busy).toBe('true');
    expect(state.secondAccepted).toBe(false);
    expect(state.secondPrevented).toBe(true);
});

test('optional styles follow the application color scheme', async ({ page }) => {
    const background = async () =>
        page.locator('.ux-upload__dropzone').evaluate((element) => getComputedStyle(element).backgroundColor);

    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/ux-upload/');
    expect(await background()).toBe('rgb(255, 255, 255)');

    await page.emulateMedia({ colorScheme: 'dark' });
    await page.goto('/ux-upload/');
    expect(await background()).toBe('rgb(24, 24, 27)');
});

const CHUNK_SIZE = 64 * 1024;

const binary = (size: number): Buffer => Buffer.from(Array.from({ length: size }, (_, index) => (index * 31) % 251));

const binaryFile = (name: string, size: number) => ({
    name,
    mimeType: 'application/octet-stream',
    buffer: binary(size),
});

const sha256 = (buffer: Buffer): string => createHash('sha256').update(buffer).digest('hex');

const expectReceived = async (page: Page, files: { name: string; mimeType: string; buffer: Buffer }[]) => {
    await expect(page.locator('#upload-received li')).toHaveText(
        files.map(
            ({ name, mimeType, buffer }) =>
                `Received ${name}, ${buffer.length} bytes, ${mimeType}, sha256 ${sha256(buffer)}`
        )
    );
    // The app redirects after a success: the form comes back empty
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue('');
};

const expectShownAsComplete = async (item: Locator): Promise<void> => {
    await expect(item).toHaveAttribute('data-progress', '100');
    await expect(item.locator('[data-ux-upload-status]')).toBeHidden();
    await expect(item.locator('[data-ux-upload-percent]')).toBeHidden();
    await expect(item.locator('[data-ux-upload-progress]')).toBeHidden();
};

interface UploadRequest {
    method: string;
    path: string;
    chunkIndex: string | undefined;
}

const recordUploadRequests = (page: Page): UploadRequest[] => {
    const requests: UploadRequest[] = [];
    page.on('request', (request) => {
        const { pathname } = new URL(request.url());
        if (pathname.startsWith('/_ux/upload')) {
            requests.push({ method: request.method(), path: pathname, chunkIndex: request.headers()['x-chunk-index'] });
        }
    });

    return requests;
};

const chunkIndices = (requests: UploadRequest[]): (string | undefined)[] =>
    requests.filter((request) => request.method === 'PUT').map((request) => request.chunkIndex);

const isInit = (request: UploadRequest): boolean => request.method === 'POST' && request.path === '/_ux/upload/init';
const isDirect = (request: UploadRequest): boolean => request.method === 'POST' && request.path === '/_ux/upload';
const isResume = (request: UploadRequest): boolean =>
    request.method === 'POST' && request.path === '/_ux/upload/resume';
const isCompletion = (request: UploadRequest): boolean =>
    request.method === 'POST' && !isInit(request) && !isDirect(request) && !isResume(request);

const isChunk = (url: URL, index: string, request: { method(): string; headers(): Record<string, string> }) =>
    url.pathname.startsWith('/_ux/upload/') &&
    request.method() === 'PUT' &&
    request.headers()['x-chunk-index'] === index;

test('sends a file larger than the chunk size through the chunked protocol', async ({ page }) => {
    await page.goto('/ux-upload/form');
    const requests = recordUploadRequests(page);

    await page.locator('.ux-upload__input').setInputFiles(binaryFile('chunked.bin', 4 * CHUNK_SIZE + 1000));

    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    expect(requests.filter(isInit)).toHaveLength(1);
    expect(chunkIndices(requests)).toEqual(['0', '1', '2', '3', '4']);
    expect(requests.filter(isCompletion)).toHaveLength(1);
    expect(requests.filter(isDirect)).toHaveLength(0);
});

test('submits the completed upload and the server reads the stored file', async ({ page }) => {
    const content = binary(2 * CHUNK_SIZE + 123);
    await page.goto('/ux-upload/form');

    await page
        .locator('.ux-upload__input')
        .setInputFiles({ name: 'report.bin', mimeType: 'application/octet-stream', buffer: content });
    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    await page.getByLabel('Title').fill('Quarterly report');
    await page.locator('#upload-submit').click();

    await expectReceived(page, [{ name: 'report.bin', mimeType: 'application/octet-stream', buffer: content }]);
});

test('keeps the completed upload across a validation error, without sending it again', async ({ page }) => {
    const content = binary(CHUNK_SIZE + 10);
    await page.goto('/ux-upload/form');
    const requests = recordUploadRequests(page);

    await page
        .locator('.ux-upload__input')
        .setInputFiles({ name: 'kept.bin', mimeType: 'application/octet-stream', buffer: content });
    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    await expectShownAsComplete(page.locator('.ux-upload__item'));
    const transfers = requests.length;

    await page.locator('#upload-submit').click();

    await expect(page.getByText('This value should not be blank.')).toBeVisible();
    const item = page.locator('.ux-upload__item[data-status="completed"]');
    await expect(item).toHaveCount(1);
    await expect(item.locator('.ux-upload__name')).toHaveText('kept.bin');
    await expectShownAsComplete(item);
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue(/"token"/);

    await page.getByLabel('Title').fill('Fixed title');
    await page.locator('#upload-submit').click();

    await expectReceived(page, [{ name: 'kept.bin', mimeType: 'application/octet-stream', buffer: content }]);
    expect(requests).toHaveLength(transfers);
});

test('resumes an interrupted chunked upload after a reload, sending only the missing chunks', async ({
    page,
}, testInfo) => {
    const path = testInfo.outputPath('resume.bin');
    writeFileSync(path, binary(4 * CHUNK_SIZE));
    await page.goto('/ux-upload/form');
    const requests = recordUploadRequests(page);
    let stalledChunk: Route | null = null;
    await page.route(
        () => true,
        async (route) => {
            if (isChunk(new URL(route.request().url()), '2', route.request())) {
                stalledChunk = route;

                return;
            }
            await route.fallback();
        }
    );

    await page.locator('.ux-upload__input').setInputFiles(path);
    await expect.poll(() => stalledChunk).not.toBeNull();
    expect(chunkIndices(requests)).toEqual(['0', '1', '2']);

    await page.reload();
    await page.unrouteAll({ behavior: 'ignoreErrors' });
    requests.length = 0;

    await page.locator('.ux-upload__input').setInputFiles(path);

    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    expect(requests.filter(isResume)).toHaveLength(1);
    expect(requests.filter(isInit)).toHaveLength(0);
    expect(chunkIndices(requests)).toEqual(['2', '3']);
});

test('removes a completed upload from the page and from the server', async ({ page }) => {
    await page.goto('/ux-upload/');
    await page.locator('.ux-upload__input').setInputFiles(file('removed.txt', 'remove me'));
    const item = page.locator('.ux-upload__item[data-status="completed"]');
    await expect(item).toBeVisible();

    const removal = page.waitForResponse(
        (response) =>
            response.request().method() === 'DELETE' && new URL(response.url()).pathname === '/_ux/upload/remove'
    );
    await item.locator('[data-ux-upload-action="remove"]').click();

    expect((await removal).ok()).toBe(true);
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue('');
});

test('refuses to submit the form while an upload is in progress', async ({ page }) => {
    await page.goto('/ux-upload/form');
    let heldChunk: Route | null = null;
    await page.route(
        () => true,
        async (route) => {
            if (isChunk(new URL(route.request().url()), '1', route.request())) {
                heldChunk = route;

                return;
            }
            await route.fallback();
        }
    );
    const submissions: string[] = [];
    page.on('request', (request) => {
        if (request.method() === 'POST' && new URL(request.url()).pathname === '/ux-upload/form') {
            submissions.push(request.url());
        }
    });

    const input = page.locator('.ux-upload__input');
    await input.setInputFiles(binaryFile('slow.bin', 4 * CHUNK_SIZE));
    await expect.poll(() => heldChunk).not.toBeNull();
    await page.getByLabel('Title').fill('Slow report');

    await page.locator('#upload-submit').click();

    expect(await input.evaluate((element: HTMLInputElement) => element.validity.customError)).toBe(true);
    expect(submissions).toEqual([]);

    await (heldChunk as unknown as Route).continue();
    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    await page.locator('#upload-submit').click();

    await expect(page.locator('#upload-received li')).toHaveCount(1);
    expect(submissions).toHaveLength(1);
});

test('pauses a chunked upload between chunks and resumes it', async ({ page }) => {
    await page.goto('/ux-upload/form');
    const requests = recordUploadRequests(page);
    let heldChunk: Route | null = null;
    await page.route(
        () => true,
        async (route) => {
            if (isChunk(new URL(route.request().url()), '1', route.request())) {
                heldChunk = route;

                return;
            }
            await route.fallback();
        }
    );

    await page.locator('.ux-upload__input').setInputFiles(binaryFile('paused.bin', 4 * CHUNK_SIZE));
    await expect.poll(() => heldChunk).not.toBeNull();
    const item = page.locator('.ux-upload__item');
    await item.locator('[data-ux-upload-action="pause"]').click();
    await expect(item).toHaveAttribute('data-status', 'paused');

    const heldResponse = page.waitForResponse((response) => response.request().headers()['x-chunk-index'] === '1');
    await (heldChunk as unknown as Route).continue();
    await heldResponse;
    await expect(item).toHaveAttribute('data-progress', '50');
    // No request can prove a pause, so leave the next chunk a fair chance to start
    await page.waitForTimeout(300);
    expect(chunkIndices(requests)).toEqual(['0', '1']);
    await expect(item).toHaveAttribute('data-status', 'paused');

    await item.locator('[data-ux-upload-action="resume"]').click();

    await expect(page.locator('.ux-upload__item[data-status="completed"]')).toBeVisible();
    expect(chunkIndices(requests)).toEqual(['0', '1', '2', '3']);
});

test('rejects a file above the maximum size before sending anything', async ({ page }) => {
    await page.goto('/ux-upload/form');
    const requests = recordUploadRequests(page);

    await page.locator('.ux-upload__input').setInputFiles(binaryFile('huge.bin', 1024 * 1024 + 1));

    await expect(page.locator('.ux-upload__error')).toHaveText('File too large (max 1.0 MB)');
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    expect(requests).toHaveLength(0);
});

const crc32 = (bytes: Buffer): number => {
    let crc = 0xffffffff;
    for (const byte of bytes) {
        crc ^= byte;
        for (let bit = 0; bit < 8; bit++) {
            crc = crc & 1 ? (crc >>> 1) ^ 0xedb88320 : crc >>> 1;
        }
    }

    return (crc ^ 0xffffffff) >>> 0;
};

const pngChunk = (type: string, data: Buffer): Buffer => {
    const length = Buffer.alloc(4);
    length.writeUInt32BE(data.length);
    const body = Buffer.concat([Buffer.from(type, 'ascii'), data]);
    const crc = Buffer.alloc(4);
    crc.writeUInt32BE(crc32(body));

    return Buffer.concat([length, body, crc]);
};

/** A valid RGB PNG, so the browser really decodes the preview. */
const png = (width: number, height: number): Buffer => {
    const header = Buffer.alloc(13);
    header.writeUInt32BE(width, 0);
    header.writeUInt32BE(height, 4);
    header.set([8, 2, 0, 0, 0], 8);
    const row = Buffer.concat([Buffer.from([0]), Buffer.alloc(width * 3, 0x7f)]);
    const pixels = Buffer.concat(Array.from({ length: height }, () => row));

    return Buffer.concat([
        Buffer.from([0x89, 0x50, 0x4e, 0x47, 0x0d, 0x0a, 0x1a, 0x0a]),
        pngChunk('IHDR', header),
        pngChunk('IDAT', deflateSync(pixels)),
        pngChunk('IEND', Buffer.alloc(0)),
    ]);
};

const galleryItems = (page: Page) => page.locator('.ux-upload__item');
const completedItems = (page: Page) => page.locator('.ux-upload__item[data-status="completed"]');

test('gallery: renders the drop area of the dropzone layout, not the compact picker', async ({ page }) => {
    const pickerStyle = async () =>
        page.locator('.ux-upload__dropzone').evaluate((element) => {
            const style = getComputedStyle(element);

            return { border: style.borderTopStyle, minHeight: style.minHeight };
        });

    await page.goto('/ux-upload/gallery');
    expect(await pickerStyle()).toEqual({ border: 'dashed', minHeight: '192px' });

    await page.goto('/ux-upload/');
    expect((await pickerStyle()).border).not.toBe('dashed');
});

test('gallery: uploads a file dropped on the drop area', async ({ page }) => {
    await page.goto('/ux-upload/gallery');
    const dropzone = page.locator('.ux-upload__dropzone');
    const dataTransfer = await page.evaluateHandle(() => {
        const data = new DataTransfer();
        data.items.add(new File(['dropped content'], 'dropped.txt', { type: 'text/plain' }));

        return data;
    });

    await dropzone.dispatchEvent('dragover', { dataTransfer });
    await expect(dropzone).toHaveClass(/is-active/);

    await dropzone.dispatchEvent('drop', { dataTransfer });

    await expect(dropzone).not.toHaveClass(/is-active/);
    await expect(completedItems(page)).toHaveCount(1);
    await expect(completedItems(page).locator('.ux-upload__name')).toHaveText('dropped.txt');
    await expectShownAsComplete(completedItems(page));
});

test('gallery: shows a decoded preview for an image and the file icon for other files', async ({ page }) => {
    await page.goto('/ux-upload/gallery');

    await page
        .locator('.ux-upload__input')
        .setInputFiles([
            { name: 'photo.png', mimeType: 'image/png', buffer: png(4, 3) },
            file('notes.txt', 'plain text'),
        ]);
    await expect(completedItems(page)).toHaveCount(2);

    const photo = galleryItems(page).filter({ hasText: 'photo.png' });
    const preview = photo.locator('.ux-upload__preview');
    await expect(preview).toBeVisible();
    await expect(preview).toHaveAttribute('alt', 'photo.png');
    await expect.poll(() => preview.evaluate((image: HTMLImageElement) => image.naturalWidth)).toBe(4);
    await expect(photo.locator('.ux-upload__file-icon')).toBeHidden();

    const notes = galleryItems(page).filter({ hasText: 'notes.txt' });
    await expect(notes.locator('.ux-upload__preview')).toBeHidden();
    await expect(notes.locator('.ux-upload__file-icon')).toBeVisible();
});

test('gallery: moves the picker into the grid, hides it at max_files and brings it back on removal', async ({
    page,
}) => {
    await page.goto('/ux-upload/gallery');
    const list = page.locator('.ux-upload__list');
    const picker = page.locator('.ux-upload__dropzone');
    const input = page.locator('.ux-upload__input');
    const requests = recordUploadRequests(page);

    const listBox = await list.boundingBox();
    expect((await picker.boundingBox())?.width).toBe(listBox?.width);

    await input.setInputFiles(file('first.txt', 'first'));
    await expect(completedItems(page)).toHaveCount(1);
    const itemBox = await galleryItems(page).first().boundingBox();
    const tileBox = await picker.boundingBox();
    expect(tileBox?.width).toBeLessThan(listBox?.width ?? 0);
    expect(tileBox?.x).toBeGreaterThan(itemBox?.x ?? Infinity);

    await input.setInputFiles([file('second.txt', 'second'), file('third.txt', 'third')]);
    await expect(completedItems(page)).toHaveCount(3);
    await expect(picker).toBeHidden();

    const transfers = requests.length;
    await input.setInputFiles(file('fourth.txt', 'fourth'));
    await expect(page.locator('.ux-upload__error')).toHaveText('Maximum number of files reached');
    await expect(galleryItems(page)).toHaveCount(3);
    expect(requests).toHaveLength(transfers);

    await galleryItems(page).first().locator('[data-ux-upload-action="remove"]').click();
    await expect(galleryItems(page)).toHaveCount(2);
    await expect(picker).toBeVisible();
});

test('gallery: submits several files and the server reads each of them', async ({ page }) => {
    const files = [
        { name: 'photo.png', mimeType: 'image/png', buffer: png(8, 8) },
        { name: 'data.bin', mimeType: 'application/octet-stream', buffer: binary(3000) },
        { name: 'notes.txt', mimeType: 'text/plain', buffer: Buffer.from('gallery notes') },
    ];
    await page.goto('/ux-upload/gallery');

    await page.locator('.ux-upload__input').setInputFiles(files);
    await expect(completedItems(page)).toHaveCount(3);
    await page.locator('#upload-submit').click();

    await expectReceived(page, files);
});

test('gallery: summarizes the progress of several uploads in flight', async ({ page }) => {
    await page.goto('/ux-upload/gallery');
    const held: Route[] = [];
    await page.route(
        (url) => url.pathname === '/_ux/upload',
        (route) => {
            held.push(route);
        }
    );
    const summary = page.locator('.ux-upload__summary');

    await page
        .locator('.ux-upload__input')
        .setInputFiles([file('a.txt', 'first'), file('b.txt', 'second'), file('c.txt', 'third')]);
    await expect.poll(() => held.length).toBe(3);

    await expect(summary).toBeVisible();
    await expect(page.locator('.ux-upload__summary-text')).toHaveText('Uploading... 0 of 3 complete');
    await expect(page.locator('.ux-upload__summary-progress')).toBeVisible();

    await held[0].continue();
    await expect(page.locator('.ux-upload__summary-text')).toHaveText('Uploading... 1 of 3 complete');

    await Promise.all(held.slice(1).map((route) => route.continue()));
    await expect(page.locator('.ux-upload__summary-text')).toHaveText('3 files uploaded');
    await expect(page.locator('.ux-upload__summary-progress')).toBeHidden();
});

const directResponse = (page: Page) =>
    page.waitForResponse(
        (response) => response.request().method() === 'POST' && new URL(response.url()).pathname === '/_ux/upload'
    );

const imageFile = (name: string, buffer: Buffer) => ({ name, mimeType: 'image/png', buffer });

test('validation: refuses a type the field does not accept before sending it', async ({ page }) => {
    await page.goto('/ux-upload/validation');
    const requests = recordUploadRequests(page);

    await page.locator('.ux-upload__input').setInputFiles(file('notes.txt', 'plain text'));

    await expect(page.locator('.ux-upload__error')).toHaveText('File type not allowed');
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    expect(requests).toHaveLength(0);
});

test('validation: refuses a file above the maximum size before sending it', async ({ page }) => {
    await page.goto('/ux-upload/validation');
    const requests = recordUploadRequests(page);

    await page
        .locator('.ux-upload__input')
        .setInputFiles(imageFile('huge.png', Buffer.concat([png(32, 32), Buffer.alloc(100 * 1024)])));

    await expect(page.locator('.ux-upload__error')).toHaveText('File too large (max 100.0 KB)');
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    expect(requests).toHaveLength(0);
});

test('validation: shows the server refusal of a file whose content is not the declared image', async ({ page }) => {
    await page.goto('/ux-upload/validation');
    const response = directResponse(page);

    await page.locator('.ux-upload__input').setInputFiles(imageFile('photo.png', Buffer.from('this is plain text')));

    const answer = await response;
    expect(answer.status()).toBe(400);
    const serverMessage = 'MIME type "text/plain" is not allowed. Allowed types: "image/png, image/jpeg".';
    expect(await answer.json()).toEqual({ error: serverMessage });
    const item = page.locator('.ux-upload__item');
    await expect(item).toHaveAttribute('data-status', 'error');
    await expect(item.locator('[data-ux-upload-status]')).toHaveText(serverMessage);
    await expect(page.locator('.ux-upload__sr-only')).toHaveText(`photo.png: upload failed - ${serverMessage}`);
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue('');
    await expect(item.locator('[data-ux-upload-action="retry"]')).toBeVisible();
    await expect(item.locator('[data-ux-upload-action="cancel"]')).toBeHidden();
});

test('validation: shows the message of an application listener that refuses the image', async ({ page }) => {
    await page.goto('/ux-upload/validation');
    const response = directResponse(page);

    await page.locator('.ux-upload__input').setInputFiles(imageFile('narrow.png', png(8, 8)));

    expect((await response).status()).toBe(400);
    const item = page.locator('.ux-upload__item');
    await expect(item).toHaveAttribute('data-status', 'error');
    await expect(item.locator('[data-ux-upload-status]')).toHaveText('The image must be at least 16 pixels wide.');
    await expect(page.locator('.ux-upload input[type="hidden"]')).toHaveValue('');
});

test('validation: accepts a valid image after a refusal and a reload, and submits it', async ({ page }) => {
    const valid = png(32, 32);
    await page.goto('/ux-upload/validation');
    await page.locator('.ux-upload__input').setInputFiles(imageFile('narrow.png', png(8, 8)));
    await expect(page.locator('.ux-upload__item')).toHaveAttribute('data-status', 'error');

    await page.reload();
    await page.locator('.ux-upload__input').setInputFiles(imageFile('wide.png', valid));

    await expect(completedItems(page)).toHaveCount(1);
    await expectShownAsComplete(completedItems(page));
    await page.locator('#upload-submit').click();
    await expectReceived(page, [{ name: 'wide.png', mimeType: 'image/png', buffer: valid }]);
});

test('validation: lets the user remove a refused file to pick another one', async ({ page }) => {
    await page.goto('/ux-upload/validation');
    await page.locator('.ux-upload__input').setInputFiles(imageFile('narrow.png', png(8, 8)));
    const item = page.locator('.ux-upload__item');
    await expect(item).toHaveAttribute('data-status', 'error');

    await expect(item.locator('[data-ux-upload-action="remove"]')).toBeVisible();
    await item.locator('[data-ux-upload-action="remove"]').click();
    await page.locator('.ux-upload__input').setInputFiles(imageFile('wide.png', png(32, 32)));
    await expect(completedItems(page)).toHaveCount(1);
});

const textFile = (name: string, contents: string) => ({ name, mimeType: 'text/plain', buffer: Buffer.from(contents) });

test('single: hides the picker once the file is sent and brings it back on removal', async ({ page }) => {
    await page.goto('/ux-upload/');
    const picker = page.locator('.ux-upload__dropzone');
    await expect(picker).toBeVisible();

    await page.locator('.ux-upload__input').setInputFiles(textFile('only.txt', 'one file'));
    await expect(completedItems(page)).toHaveCount(1);
    await expect(picker).toBeHidden();

    // Nothing is left to report on a sent file: its action sits on the row of its name
    const item = (await completedItems(page).boundingBox())!;
    const remove = (await page.locator('[data-ux-upload-action="remove"]').boundingBox())!;
    expect(Math.abs(remove.y + remove.height / 2 - (item.y + item.height / 2))).toBeLessThan(2);

    await page.locator('[data-ux-upload-action="remove"]').click();
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    await expect(picker).toBeVisible();
});

test('single: submits the uploaded file and the server reads it', async ({ page }) => {
    const upload = textFile('single.txt', 'one file');
    await page.goto('/ux-upload/');

    await page.locator('.ux-upload__input').setInputFiles(upload);
    await expect(completedItems(page)).toHaveCount(1);
    await page.locator('#upload-submit').click();

    await expectReceived(page, [upload]);
});

test('multiple: submits every uploaded file, in order', async ({ page }) => {
    const uploads = ['one', 'two', 'three', 'four', 'five'].map((word) => textFile(`${word}.txt`, `file ${word}`));
    await page.goto('/ux-upload/multiple');

    await page.locator('.ux-upload__input').setInputFiles(uploads);
    await expect(completedItems(page)).toHaveCount(5);
    await page.locator('#upload-submit').click();

    await expectReceived(page, uploads);
});

test('multiple: submits only the files left after a removal', async ({ page }) => {
    const uploads = ['kept-a', 'removed', 'kept-b'].map((word) => textFile(`${word}.txt`, `file ${word}`));
    await page.goto('/ux-upload/multiple');

    await page.locator('.ux-upload__input').setInputFiles(uploads);
    await expect(completedItems(page)).toHaveCount(3);
    await completedItems(page).filter({ hasText: 'removed.txt' }).locator('[data-ux-upload-action="remove"]').click();
    await expect(page.locator('.ux-upload__item')).toHaveCount(2);
    await page.locator('#upload-submit').click();

    await expectReceived(page, [uploads[0], uploads[2]]);
});

test('manual: sends nothing until the start button, then submits the uploaded file', async ({ page }) => {
    const upload = textFile('manual.txt', 'started by hand');
    await page.goto('/ux-upload/manual');
    const requests = recordUploadRequests(page);

    await page.locator('.ux-upload__input').setInputFiles(upload);
    await expect(page.locator('.ux-upload__item')).toHaveAttribute('data-status', 'pending');
    expect(requests).toHaveLength(0);

    await page.locator('.ux-upload__start').click();
    await expect(completedItems(page)).toHaveCount(1);
    await page.locator('#upload-submit').click();

    await expectReceived(page, [upload]);
});

test('manual: submitting an optional field before the start button sends the form without the pending file', async ({
    page,
}) => {
    await page.goto('/ux-upload/manual');
    const requests = recordUploadRequests(page);

    await page.locator('.ux-upload__input').setInputFiles(textFile('pending.txt', 'never started'));
    await expect(page.locator('.ux-upload__item')).toHaveAttribute('data-status', 'pending');
    await page.locator('#upload-submit').click();

    await expect(page.locator('#upload-received li')).toHaveText(['Received no file']);
    await expect(page.locator('.ux-upload__item')).toHaveCount(0);
    expect(requests).toHaveLength(0);
});

test('minimal theme: submits a file uploaded through empty presentation blocks', async ({ page }) => {
    const upload = textFile('minimal.txt', 'minimal theme');
    await page.goto('/ux-upload/minimal-theme');

    await page.locator('.ux-upload__input').setInputFiles(upload);
    await expect(completedItems(page)).toHaveCount(1);
    await page.locator('#upload-submit').click();

    await expectReceived(page, [upload]);
});

test('single: the browser refuses an empty submission, the field being required by default', async ({ page }) => {
    await page.goto('/ux-upload/');
    const input = page.locator('.ux-upload__input');
    await expect(input).toHaveAttribute('required', /.*/);
    const submissions: string[] = [];
    page.on('request', (request) => {
        if (request.method() === 'POST') {
            submissions.push(request.url());
        }
    });

    await page.locator('#upload-submit').click();

    expect(await input.evaluate((element: HTMLInputElement) => element.validity.valueMissing)).toBe(true);
    expect(submissions).toEqual([]);
});

test.describe('without JavaScript', () => {
    test.use({ javaScriptEnabled: false });

    test('submits the file with the form, as a native file field does', async ({ page }) => {
        const content = binary(2 * CHUNK_SIZE + 123);
        await page.goto('/ux-upload/form');

        await page
            .locator('.ux-upload__input')
            .setInputFiles({ name: 'report.bin', mimeType: 'application/octet-stream', buffer: content });
        await page.getByLabel('Title').fill('Quarterly report');
        await page.locator('#upload-submit').click();

        await expectReceived(page, [{ name: 'report.bin', mimeType: 'application/octet-stream', buffer: content }]);
    });

    test('submits several files with the form, in order', async ({ page }) => {
        const uploads = [textFile('first.txt', 'first file'), textFile('second.txt', 'second file')];
        await page.goto('/ux-upload/multiple');

        await page.locator('.ux-upload__input').setInputFiles(uploads);
        await page.locator('#upload-submit').click();

        await expectReceived(page, uploads);
    });

    test('shows the refusal of the server as a form error', async ({ page }) => {
        await page.goto('/ux-upload/validation');

        await page
            .locator('.ux-upload__input')
            .setInputFiles(imageFile('photo.png', Buffer.from('this is plain text')));
        await page.locator('#upload-submit').click();

        // The file is judged on its content, not on the image type the browser announced
        await expect(page.locator('form')).toContainText('MIME type "text/plain" is not allowed by the form policy.');
        await expect(page.locator('#upload-received')).toHaveCount(0);
    });

    test('renders no server path when another field is invalid', async ({ page }) => {
        await page.goto('/ux-upload/form');

        await page.locator('.ux-upload__input').setInputFiles(textFile('kept.txt', 'kept on the server'));
        await page.locator('#upload-submit').click();

        await expect(page.locator('form')).toContainText('This value should not be blank.');
        const token = JSON.parse(await page.locator('.ux-upload input[type="hidden"]').inputValue());
        expect(Object.keys(token)).toEqual(['token', 'meta']);
        expect(token.meta).toEqual({ filename: 'kept.txt', mimeType: 'text/plain', size: 18 });
    });
});
