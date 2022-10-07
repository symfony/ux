export default class {
    response: Response
    private body: string;

    constructor(response: Response) {
        this.response = response;
    }

    async getBody(): Promise<string> {
        if (!this.body) {
            this.body = await this.response.text();
        }

        return this.body;
    }
}
