import { Response } from 'node-fetch';
import { describe, expect, it } from 'vitest';
import BackendResponse from '../../../src/Backend/BackendResponse';

const encoder = new TextEncoder();

/** jsdom's Blob has neither text() nor arrayBuffer(), so read it the long way. */
const blobBytes = (blob: Blob): Promise<Uint8Array> =>
    new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(new Uint8Array(reader.result as ArrayBuffer));
        reader.onerror = () => reject(reader.error);
        reader.readAsArrayBuffer(blob);
    });

const blobText = async (blob: Blob): Promise<string> => new TextDecoder().decode(await blobBytes(blob));

const makeResponse = (headers: Record<string, string> = {}, body: string | Uint8Array = ''): BackendResponse =>
    // @ts-expect-error Response doesn't quite match the underlying interface
    new BackendResponse(new Response(body, { headers }));

/**
 * Builds a response body the way the server does: HTML bytes, then file bytes,
 * with the offset advertised in a header.
 */
const makeDownloadResponse = (html: string, file: Uint8Array, headers: Record<string, string> = {}) => {
    const htmlBytes = encoder.encode(html);
    const body = new Uint8Array(htmlBytes.length + file.length);
    body.set(htmlBytes, 0);
    body.set(file, htmlBytes.length);

    return makeResponse(
        {
            'X-Live-Html-Length': String(htmlBytes.length),
            'X-Live-Download-Filename': 'report.csv',
            'X-Live-Download-Type': 'text/csv',
            ...headers,
        },
        body
    );
};

describe('BackendResponse', () => {
    describe('without a download', () => {
        it('returns the whole body as HTML', async () => {
            const response = makeResponse({}, '<div>hello</div>');

            expect(await response.getBody()).toBe('<div>hello</div>');
        });

        it('has no download', async () => {
            const response = makeResponse({}, '<div>hello</div>');
            await response.getBody();

            expect(response.getDownload()).toBeNull();
        });
    });

    describe('with a download', () => {
        it('returns only the HTML part as the body', async () => {
            const response = makeDownloadResponse('<div>hello</div>', encoder.encode('a,b,c'));

            expect(await response.getBody()).toBe('<div>hello</div>');
        });

        it('returns the remaining bytes as the download blob', async () => {
            const response = makeDownloadResponse('<div>hello</div>', encoder.encode('a,b,c'));
            await response.getBody();

            const download = response.getDownload();
            expect(download).not.toBeNull();
            expect(await blobText(download!.blob)).toBe('a,b,c');
            expect(download?.blob.type).toBe('text/csv');
        });

        it('splits on bytes, so a multibyte HTML part does not shift the file', async () => {
            // "é" is two bytes but one character: a character-based offset would land mid-sequence
            const response = makeDownloadResponse('<div>résumé</div>', encoder.encode('a,b,c'));

            expect(await response.getBody()).toBe('<div>résumé</div>');
            expect(await blobText(response.getDownload()!.blob)).toBe('a,b,c');
        });

        it('preserves bytes that are not valid UTF-8', async () => {
            const file = new Uint8Array([0x00, 0x01, 0x02, 0xff, 0xfe]);
            const response = makeDownloadResponse('<div>hello</div>', file);
            await response.getBody();

            const bytes = await blobBytes(response.getDownload()!.blob);
            expect(Array.from(bytes)).toEqual([0x00, 0x01, 0x02, 0xff, 0xfe]);
        });

        it('handles an empty file', async () => {
            const response = makeDownloadResponse('<div>hello</div>', new Uint8Array(0));
            await response.getBody();

            expect(response.getDownload()?.blob.size).toBe(0);
        });

        it('handles empty HTML', async () => {
            const response = makeDownloadResponse('', encoder.encode('a,b,c'));

            expect(await response.getBody()).toBe('');
            expect(await blobText(response.getDownload()!.blob)).toBe('a,b,c');
        });

        it('parses only once across repeated calls', async () => {
            const response = makeDownloadResponse('<div>hello</div>', encoder.encode('a,b,c'));

            // a second read of an already-consumed stream would throw
            expect(await response.getBody()).toBe('<div>hello</div>');
            expect(await response.getBody()).toBe('<div>hello</div>');
        });
    });

    describe('download filename', () => {
        it('percent-decodes the filename', async () => {
            const response = makeDownloadResponse('<div></div>', encoder.encode('x'), {
                'X-Live-Download-Filename': 'r%C3%A9sum%C3%A9.pdf',
            });
            await response.getBody();

            expect(response.getDownload()?.filename).toBe('résumé.pdf');
        });

        it('falls back to "download" when the header is absent', async () => {
            const html = '<div></div>';
            const response = makeResponse(
                { 'X-Live-Html-Length': String(encoder.encode(html).length) },
                encoder.encode(`${html}x`)
            );
            await response.getBody();

            expect(response.getDownload()?.filename).toBe('download');
        });

        it('falls back to "download" on malformed percent-encoding', async () => {
            const response = makeDownloadResponse('<div></div>', encoder.encode('x'), {
                'X-Live-Download-Filename': '%',
            });
            await response.getBody();

            expect(response.getDownload()?.filename).toBe('download');
        });

        it('defaults the blob type when the header is absent', async () => {
            const html = '<div></div>';
            const response = makeResponse(
                {
                    'X-Live-Html-Length': String(encoder.encode(html).length),
                    'X-Live-Download-Filename': 'file.bin',
                },
                encoder.encode(`${html}x`)
            );
            await response.getBody();

            expect(response.getDownload()?.blob.type).toBe('application/octet-stream');
        });
    });

    describe('getDownloadUrl()', () => {
        it('reads the X-Live-Download-Url header', () => {
            expect(makeResponse({ 'X-Live-Download-Url': '/exports/report.csv' }).getDownloadUrl()).toBe(
                '/exports/report.csv'
            );
        });

        it('is null when absent', () => {
            expect(makeResponse().getDownloadUrl()).toBeNull();
        });
    });

    describe('getLiveUrl()', () => {
        it('reads the X-Live-Url header', () => {
            expect(makeResponse({ 'X-Live-Url': '/foo?bar=1' }).getLiveUrl()).toBe('/foo?bar=1');
        });

        it('is null when absent', () => {
            expect(makeResponse().getLiveUrl()).toBeNull();
        });
    });

    describe('isRemoved()', () => {
        it('is true when the X-Live-Remove header is present', () => {
            expect(makeResponse({ 'X-Live-Remove': '1' }).isRemoved()).toBe(true);
        });

        it('is false when absent', () => {
            expect(makeResponse().isRemoved()).toBe(false);
        });

        it('only looks at the presence of the header, not its value', () => {
            expect(makeResponse({ 'X-Live-Remove': '0' }).isRemoved()).toBe(true);
        });
    });
});
