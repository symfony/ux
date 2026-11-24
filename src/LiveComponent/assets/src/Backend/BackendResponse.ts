export interface Download {
    filename: string;
    blob: Blob;
}

/**
 * A response may carry a file, or data for the caller, after the HTML, in the same body.
 *
 * `X-Live-Html-Length` gives the byte offset where the HTML ends and the rest
 * begins, so the two are split without any delimiter to scan for.
 */
export default class {
    response: Response;
    private body: string;
    private liveUrl: string | null;
    private download: Download | null = null;
    private data: { bytes: ArrayBuffer; type: string } | null = null;
    private parsePromise: Promise<void> | null = null;

    constructor(response: Response) {
        this.response = response;
    }

    async getBody(): Promise<string> {
        if (!this.parsePromise) {
            this.parsePromise = this.parse();
        }
        await this.parsePromise;

        return this.body;
    }

    /**
     * Synchronous on purpose, so the download can be triggered from the render path.
     * Only meaningful once getBody() has resolved.
     */
    getDownload(): Download | null {
        return this.download;
    }

    /**
     * The data the action sent with LiveResponse::data(), or null when it sent none.
     *
     * A new Response is returned on each call, so the data can be read more than once, with json(), text(), blob() or arrayBuffer().
     * Only meaningful once getBody() has resolved, which is the case once the promise returned by Component.action() has resolved.
     */
    getData(): Response | null {
        if (null === this.data) {
            return null;
        }

        return new Response(this.data.bytes, { headers: { 'Content-Type': this.data.type } });
    }

    getLiveUrl(): string | null {
        if (undefined === this.liveUrl) {
            this.liveUrl = this.response.headers.get('X-Live-Url');
        }

        return this.liveUrl;
    }

    /**
     * A URL the browser downloads itself, rather than a file carried in this response.
     */
    getDownloadUrl(): string | null {
        return this.response.headers.get('X-Live-Download-Url');
    }

    /**
     * Whether the component is meant to leave the page after its final re-render.
     */
    isRemoved(): boolean {
        return this.response.headers.has('X-Live-Remove');
    }

    /**
     * Whether the action changed the state without rendering the template.
     *
     * The body then holds only an empty element, which carries the new props and events in the live attributes of the root element.
     */
    isRenderSkipped(): boolean {
        return this.response.headers.has('X-Live-No-Render');
    }

    private async parse(): Promise<void> {
        const htmlLength = this.response.headers.get('X-Live-Html-Length');

        if (null === htmlLength) {
            this.body = await this.response.text();

            return;
        }

        // the file may hold bytes that are not valid UTF-8, so the body is split
        // as bytes and only the HTML side is decoded
        const buffer = await this.response.arrayBuffer();
        const splitAt = clampToBuffer(Number.parseInt(htmlLength, 10), buffer.byteLength);

        // the HTML is decoded from a view rather than a slice: it is read, never kept
        this.body = new TextDecoder().decode(new Uint8Array(buffer, 0, splitAt));

        const dataType = this.response.headers.get('X-Live-Data-Type');
        if (null !== dataType) {
            // the data is kept for getData(), so it is copied out, and the HTML part is not held with it
            this.data = { bytes: buffer.slice(splitAt), type: dataType };

            return;
        }

        // the file is read once into the Blob, so a view is enough
        this.download = {
            // the filename is percent-encoded: headers carry ASCII only
            filename: decodeFilename(this.response.headers.get('X-Live-Download-Filename')),
            blob: new Blob([new Uint8Array(buffer, splitAt)], {
                type: this.response.headers.get('X-Live-Download-Type') ?? 'application/octet-stream',
            }),
        };
    }
}

/** A view cannot be built out of bounds, where ArrayBuffer.slice() clamps silently. */
function clampToBuffer(offset: number, byteLength: number): number {
    if (Number.isNaN(offset)) {
        return 0;
    }

    return offset < 0 ? Math.max(byteLength + offset, 0) : Math.min(offset, byteLength);
}

function decodeFilename(value: string | null): string {
    if (!value) {
        return 'download';
    }

    try {
        return decodeURIComponent(value) || 'download';
    } catch {
        // malformed percent-encoding: better a generic name than a failed download
        return 'download';
    }
}
