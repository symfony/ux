import BackendRequest from './BackendRequest';
import RequestBuilder from './RequestBuilder';

export interface ChildrenFingerprints {
    // key is the id of the child component
    [key: string]: {fingerprint: string, tag: string}
}

export interface BackendInterface {
    makeRequest(
        props: any,
        actions: BackendAction[],
        updated: {[key: string]: any},
        children: ChildrenFingerprints,
        updatedPropsFromParent: {[key: string]: any},
        files: {[key: string]: FileList},
    ): BackendRequest;
}

export interface BackendAction {
    name: string;
    args: Record<string, string>;
}

export default class implements BackendInterface {
    private readonly requestBuilder: RequestBuilder;

    constructor(url: string, csrfToken: string | null = null) {
        this.requestBuilder = new RequestBuilder(url, csrfToken);
    }

    makeRequest(
        props: any,
        actions: BackendAction[],
        updated: {[key: string]: any},
        children: ChildrenFingerprints,
        updatedPropsFromParent: {[key: string]: any},
        files: {[key: string]: FileList},
    ): BackendRequest {
        const { url, fetchOptions } = this.requestBuilder.buildRequest(
            props,
            actions,
            updated,
            children,
            updatedPropsFromParent,
            files
        );

        return new BackendRequest(
            fetch(url, fetchOptions),
            actions.map((backendAction) => backendAction.name),
            Object.keys(updated)
        );
    }
}
