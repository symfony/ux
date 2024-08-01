import type { BackendAction, ChildrenFingerprints } from './Backend';
export default class {
    private url;
    private method;
    private readonly csrfToken;
    constructor(url: string, method?: 'get' | 'post', csrfToken?: string | null);
    buildRequest(props: any, actions: BackendAction[], updated: {
        [key: string]: any;
    }, children: ChildrenFingerprints, updatedPropsFromParent: {
        [key: string]: any;
    }, files: {
        [key: string]: FileList;
    }): {
        url: string;
        fetchOptions: RequestInit;
    };
    private willDataFitInUrl;
}
