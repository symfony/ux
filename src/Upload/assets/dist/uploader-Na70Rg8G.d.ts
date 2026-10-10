/**
 * Types for Symfony UX Upload
 */
interface InitResponse {
  uploadId: string;
  uploadUrl: string;
  config: UploadConfig;
  resumeToken?: string;
}
interface UploadConfig {
  chunkSize: number;
  totalChunks: number;
  compression: boolean;
  parallel: number;
}
interface ProgressResponse {
  progress: {
    storedChunks: number;
    chunkIndices: number[];
    percentComplete: number;
  } | null;
}
interface PublicMetadata {
  filename: string;
  mimeType: string;
  size: number;
}
interface CompleteResponse {
  success: boolean;
  uploadId?: string;
  token: string;
  meta: PublicMetadata;
}
interface UploadResult {
  uploadId?: string;
  token: string;
  metadata: PublicMetadata;
}
interface UploadError {
  success: false;
  error: string;
}
interface UploadSpeed {
  bytesPerSecond: number;
  remainingMs: number;
}
interface UploaderEvents {
  onInit?: (uploadId: string, file: File, resumable: boolean) => void;
  onDirectProgress?: (file: File, percent: number, speed?: UploadSpeed) => void;
  onProgress?: (uploadId: string, percent: number, chunkIndex: number, speed?: UploadSpeed) => void;
  onComplete?: (uploadId: string, result: UploadResult) => void;
  onError?: (uploadId: string, error: Error) => void;
  onChunkComplete?: (uploadId: string, chunkIndex: number, totalChunks: number) => void;
}
type IntegrityAlgorithm = 'sha256' | 'sha384' | 'sha512';
type UploaderFetch = (input: RequestInfo | URL, init?: RequestInit) => Promise<Response>;
type UploaderXhrFactory = () => XMLHttpRequest;
interface UploaderOptions {
  initUrl: string;
  directUrl?: string;
  directUploadThreshold?: number;
  removeUrl?: string;
  events?: UploaderEvents;
  uploader?: string;
  csrfToken?: string | null;
  integrityAlgorithm?: IntegrityAlgorithm;
  policyToken?: string | null;
  compression?: boolean;
  credentials?: RequestCredentials;
  headers?: Record<string, string>;
  fetch?: UploaderFetch;
  xhr?: UploaderXhrFactory;
}
declare class UploadCancelledError extends Error {
  constructor();
}
declare class UploadSuspendedError extends Error {
  constructor();
}
declare class Uploader {
  private resumeStore;
  private abortControllers;
  private uploadUrls;
  private uploadFingerprints;
  private abortReasons;
  private pauseFlags;
  private pauseResolvers;
  private speedSamples;
  private uploadFileSizes;
  private initUrl;
  private directUrl?;
  private directUploadThreshold;
  private removeUrl;
  private events;
  private uploaderName;
  private csrfToken;
  private integrityAlgorithm;
  private policyToken;
  private compressionEnabled;
  private credentials?;
  private headers;
  private fetcher;
  private fileAbortControllers;
  private xhrFactory;
  private useXhrForDirect;
  constructor(options: UploaderOptions);
  constructor(initUrl: string, events?: UploaderEvents, uploaderName?: string, csrfToken?: string | null, integrityAlgorithm?: IntegrityAlgorithm, policyToken?: string | null, compressionEnabled?: boolean);
  /**
   * Upload a file with chunking, compression, and retry support
   */
  upload(file: File): Promise<UploadResult>;
  usesDirectUpload(file: File): boolean;
  /**
   * Cancel an upload that has no server upload ID yet (direct upload, or a
   * chunked upload still initializing).
   */
  cancelFile(file: File): void;
  /**
   * Cancel an in-progress upload
   */
  cancel(uploadId: string): void;
  /**
   * Stop local work while preserving the server session for a later resume.
   */
  suspend(uploadId: string): void;
  /**
   * Remove a completed upload.
   */
  remove(token: string): Promise<void>;
  /**
   * Pause an in-progress upload (stops sending new chunks)
   */
  pause(uploadId: string): void;
  /**
   * Resume a paused upload
   */
  resume(uploadId: string): void;
  /**
   * Check if an upload is paused
   */
  isPaused(uploadId: string): boolean;
  /**
   * Initialize upload and get signed URLs
   */
  private initializeUpload;
  private uploadDirect;
  private uploadDirectWithXhr;
  private uploadDirectWithFetch;
  private parseDirectResponse;
  private readDirectError;
  private resumeUpload;
  private fingerprint;
  /**
   * Check which chunks already exist (for resume)
   */
  private checkResume;
  /**
   * Upload chunks with parallel execution
   */
  private uploadChunks;
  /**
   * Upload a single chunk with retry logic
   */
  private uploadChunk;
  /**
   * Complete the upload
   */
  private completeUpload;
  private request;
  /**
   * Check if Compression Streams API is supported
   */
  private isCompressionSupported;
  /**
   * Check if file type is already compressed
   */
  private isAlreadyCompressed;
  /**
   * Compress data using Compression Streams API
   */
  private compress;
  /**
   * Record a speed sample and calculate current speed + ETA
   */
  private recordSpeedSample;
  /**
   * Compute a file hash using Web Crypto API.
   * Returns undefined if the API is not available (graceful degradation).
   */
  private computeFileHash;
  private digestBuffer;
  /**
   * Wait for the retry backoff, giving up as soon as the upload is aborted
   */
  private sleep;
  /**
   * Internal: stop the current step as soon as the upload has been aborted
   */
  private throwIfAborted;
  /**
   * Wait if the upload is paused, resolving when resumed
   */
  private waitIfPaused;
  /**
   * Internal: clear pause state and resolve any waiting promise
   */
  private resumeIfPaused;
}
export { Uploader as a, UploaderOptions as c, InitResponse as d, ProgressResponse as f, UploadResult as h, UploadSuspendedError as i, UploaderXhrFactory as l, UploadError as m, UploadCancelledError as n, UploaderEvents as o, UploadConfig as p, UploadSpeed as r, UploaderFetch as s, IntegrityAlgorithm as t, CompleteResponse as u };