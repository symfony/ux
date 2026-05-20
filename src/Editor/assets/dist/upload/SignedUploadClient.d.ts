export interface UploadResult {
    url: string;
    size?: number;
    type?: string;
    width?: number;
    height?: number;
}
export interface UploadOptions {
    field: string;
}
export declare class SignedUploadClient {
    private readonly url;
    private readonly options;
    constructor(url: string, options: UploadOptions);
    upload(file: Blob, filename: string): Promise<UploadResult>;
}
