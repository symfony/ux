import BackendRequest from './BackendRequest';

export interface BackendInterface {
    makeRequest(data: any, actions: BackendAction[], updatedModels: string[], childrenFingerprints: any): BackendRequest;
}

export interface BackendAction {
    name: string,
    args: Record<string, string>
}

export default class implements BackendInterface {
    private url: string;
    private readonly csrfToken: string|null;

    constructor(url: string, csrfToken: string|null = null) {
        this.url = url;
        this.csrfToken = csrfToken;
    }

    makeRequest(data: any, actions: BackendAction[], updatedModels: string[], childrenFingerprints: any): BackendRequest {
        const splitUrl = this.url.split('?');
        let [url] = splitUrl
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');

        const fetchOptions: RequestInit = {};
        fetchOptions.headers = {
            'Accept': 'application/vnd.live-component+html',
        };

        const hasFingerprints = Object.keys(childrenFingerprints).length > 0;
        const hasUpdatedModels = Object.keys(updatedModels).length > 0;
        if (actions.length === 0 && this.willDataFitInUrl(JSON.stringify(data), params, JSON.stringify(childrenFingerprints))) {
            params.set('data', JSON.stringify(data));
            if (hasFingerprints) {
                params.set('childrenFingerprints', JSON.stringify(childrenFingerprints));
            }
            updatedModels.forEach((model) => {
                params.append('updatedModels[]', model);
            });
            fetchOptions.method = 'GET';
        } else {
            fetchOptions.method = 'POST';
            fetchOptions.headers['Content-Type'] = 'application/json';
            const requestData: any = { data };
            if (hasUpdatedModels) {
                requestData.updatedModels = updatedModels;
            }
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

        return new BackendRequest(
            fetch(`${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`, fetchOptions),
            actions.map((backendAction) => backendAction.name),
            updatedModels
        );
    }

    private willDataFitInUrl(dataJson: string, params: URLSearchParams, childrenFingerprintsJson: string) {
        const urlEncodedJsonData = new URLSearchParams(dataJson + childrenFingerprintsJson).toString();

        // if the URL gets remotely close to 2000 chars, it may not fit
        return (urlEncodedJsonData + params.toString()).length < 1500;
    }
}
