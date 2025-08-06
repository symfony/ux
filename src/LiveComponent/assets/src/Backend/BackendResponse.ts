export default class {
    response: Response;
    private body: string;
    private liveUrl: string | null;

    constructor(response: Response) {
        this.response = response;
    }

    async getBody(): Promise<string> {
        if (!this.body) {
            this.body = await this.response.text();
        }

        return this.body;
    }

    getLiveUrl(): string | null {
        if (undefined === this.liveUrl) {
            this.liveUrl = this.response.headers.get('X-Live-Url');
        }

        return this.liveUrl;
    }

    async checkResponseType(): Promise<{ type: 'json' | 'html' | 'invalid'; body: string }> {
        const contentType = this.response.headers.get('Content-Type') || '';
        const headers = this.response.headers;

        const text = await this.getBody();
        const trimmed = text.trim();

        if (contentType.includes('application/json')) {
            try {
                JSON.parse(trimmed);
                return { type: 'json', body: trimmed };
            } catch {
                // not valid JSON
            }
        }

        const isValidHtml =
            trimmed.length > 0 &&
            (contentType.includes('application/vnd.live-component+html') || headers.get('X-Live-Redirect') !== null);

        if (isValidHtml) {
            return { type: 'html', body: trimmed };
        }

        return { type: 'invalid', body: trimmed };
    }
}
