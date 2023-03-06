export default class {
    response: Response;
    private body;
    constructor(response: Response);
    getBody(): Promise<string>;
}
