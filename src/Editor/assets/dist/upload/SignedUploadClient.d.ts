interface UploadResult {
  url: string;
  size?: number;
  type?: string;
  width?: number;
  height?: number;
}
interface UploadOptions {
  field: string;
}
declare class SignedUploadClient {
  private readonly url;
  private readonly options;
  constructor(url: string, options: UploadOptions);
  upload(file: Blob, filename: string): Promise<UploadResult>;
}
export { SignedUploadClient, UploadOptions, UploadResult };