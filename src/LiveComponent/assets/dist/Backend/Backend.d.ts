import BackendRequest from './BackendRequest';
export interface ChildrenFingerprints {
    [key: string]: {
        fingerprint: string;
        tag: string;
    };
}
export interface BackendInterface {
    makeRequest(props: any, actions: BackendAction[], updated: {
        [key: string]: any;
    }, children: ChildrenFingerprints, updatedPropsFromParent: {
        [key: string]: any;
    }): BackendRequest;
}
export interface BackendAction {
    name: string;
    args: Record<string, string>;
}
export default class implements BackendInterface {
    private readonly requestBuilder;
    constructor(url: string, csrfToken?: string | null);
    makeRequest(props: any, actions: BackendAction[], updated: {
        [key: string]: any;
    }, children: ChildrenFingerprints, updatedPropsFromParent: {
        [key: string]: any;
    }): BackendRequest;
}
