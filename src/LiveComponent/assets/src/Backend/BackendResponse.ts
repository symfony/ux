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
}
