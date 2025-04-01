export default class {
    response: Response;
    private body;
    private liveUrl;
    constructor(response: Response);
    getBody(): Promise<string>;
    getLiveUrl(): string | null;
}
