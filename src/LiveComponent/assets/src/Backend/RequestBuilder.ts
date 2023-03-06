import { BackendAction } from './Backend';

export default class {
    private url: string;
    private readonly csrfToken: string | null;

    constructor(url: string, csrfToken: string | null = null) {
        this.url = url;
        this.csrfToken = csrfToken;
    }

    buildRequest(
        props: any,
        actions: BackendAction[],
        updated: {[key: string]: any},
        childrenFingerprints: any
    ): { url: string; fetchOptions: RequestInit } {
        const splitUrl = this.url.split('?');
        let [url] = splitUrl;
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');

        const fetchOptions: RequestInit = {};
        fetchOptions.headers = {
            Accept: 'application/vnd.live-component+html',
        };

        const hasFingerprints = Object.keys(childrenFingerprints).length > 0;
        if (
            actions.length === 0 &&
            this.willDataFitInUrl(JSON.stringify(props), JSON.stringify(updated), params, JSON.stringify(childrenFingerprints))
        ) {
            params.set('props', JSON.stringify(props));
            params.set('updated', JSON.stringify(updated));
            if (hasFingerprints) {
                params.set('childrenFingerprints', JSON.stringify(childrenFingerprints));
            }
            fetchOptions.method = 'GET';
        } else {
            fetchOptions.method = 'POST';
            fetchOptions.headers['Content-Type'] = 'application/json';
            const requestData: any = { props, updated };
            if (hasFingerprints) {
                requestData.childrenFingerprints = childrenFingerprints;
            }

            if (actions.length > 0) {
                // one or more ACTIONs
                if (this.csrfToken) {
                    fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfToken;
                }

                if (actions.length === 1) {
                    // simple, single action
                    requestData.args = actions[0].args;

                    url += `/${encodeURIComponent(actions[0].name)}`;
                } else {
                    url += '/_batch';
                    requestData.actions = actions;
                }
            }

            fetchOptions.body = JSON.stringify(requestData);
        }

        const paramsString = params.toString();

        return {
            url: `${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`,
            fetchOptions,
        }
    }

    private willDataFitInUrl(propsJson: string, updatedJson: string, params: URLSearchParams, childrenFingerprintsJson: string) {
        const urlEncodedJsonData = new URLSearchParams(propsJson + updatedJson + childrenFingerprintsJson).toString();

        // if the URL gets remotely close to 2000 chars, it may not fit
        return (urlEncodedJsonData + params.toString()).length < 1500;
    }
}
