export class XhrTestDouble extends EventTarget {
    readonly upload = new EventTarget();
    readonly headers = new Map<string, string>();
    method = '';
    url = '';
    body: XMLHttpRequestBodyInit | null = null;
    status = 0;
    responseText = '';
    withCredentials = false;
    aborted = false;

    open(method: string, url: string): void {
        this.method = method;
        this.url = url;
    }

    setRequestHeader(name: string, value: string): void {
        this.headers.set(name, value);
    }

    send(body: XMLHttpRequestBodyInit | null = null): void {
        this.body = body;
    }

    abort(): void {
        this.aborted = true;
        this.dispatchEvent(new Event('abort'));
    }

    progress(loaded: number, total: number): void {
        this.upload.dispatchEvent(
            new ProgressEvent('progress', {
                lengthComputable: true,
                loaded,
                total,
            })
        );
    }

    respond(status: number, body: unknown): void {
        this.status = status;
        this.responseText = JSON.stringify(body);
        this.dispatchEvent(new Event('load'));
    }

    fail(): void {
        this.dispatchEvent(new Event('error'));
    }

    factory(): () => XMLHttpRequest {
        return () => this as unknown as XMLHttpRequest;
    }
}
