export interface UploadResult {
    url: string;
    size?: number;
    type?: string;
    width?: number;
    height?: number;
}

export interface UploadOptions {
    field: string;
}

export class SignedUploadClient {
    constructor(
        private readonly url: string,
        private readonly options: UploadOptions
    ) {}

    async upload(file: Blob, filename: string): Promise<UploadResult> {
        const fd = new FormData();
        fd.append('file', file, filename);
        fd.append('field', this.options.field);

        const res = await fetch(this.url, { method: 'POST', body: fd });
        const text = await res.text();
        let payload: any = {};
        try {
            payload = text ? JSON.parse(text) : {};
        } catch {
            /* server returned non-JSON */
        }

        if (!res.ok) {
            const err: any = new Error(payload.message ?? `Upload failed: ${res.status}`);
            err.status = res.status;
            err.code = payload.error ?? 'unknown_error';
            throw err;
        }
        return payload as UploadResult;
    }
}
