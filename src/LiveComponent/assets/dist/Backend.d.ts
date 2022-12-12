import BackendRequest from './BackendRequest';
export interface BackendInterface {
    makeRequest(data: any, actions: BackendAction[], updatedModels: string[], childrenFingerprints: any): BackendRequest;
}
export interface BackendAction {
    name: string;
    args: Record<string, string>;
}
export default class implements BackendInterface {
    private url;
    private readonly csrfToken;
    constructor(url: string, csrfToken?: string | null);
    makeRequest(data: any, actions: BackendAction[], updatedModels: string[], childrenFingerprints: any): BackendRequest;
    private willDataFitInUrl;
}
