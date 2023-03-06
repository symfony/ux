import { BackendAction } from './Backend';
export default class {
    private url;
    private readonly csrfToken;
    constructor(url: string, csrfToken?: string | null);
    buildRequest(props: any, actions: BackendAction[], updated: {
        [key: string]: any;
    }, childrenFingerprints: any): {
        url: string;
        fetchOptions: RequestInit;
    };
    private willDataFitInUrl;
}
