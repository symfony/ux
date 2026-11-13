import { Controller } from "@hotwired/stimulus";
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
export declare class UploadCancelledError extends Error {
  constructor();
}
export declare class UploadSuspendedError extends Error {
  constructor();
}
export declare class Uploader {
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
  upload(file: File): Promise<UploadResult>;
  usesDirectUpload(file: File): boolean;
  cancelFile(file: File): void;
  cancel(uploadId: string): void;
  suspend(uploadId: string): void;
  remove(token: string): Promise<void>;
  pause(uploadId: string): void;
  resume(uploadId: string): void;
  isPaused(uploadId: string): boolean;
  private initializeUpload;
  private uploadDirect;
  private uploadDirectWithXhr;
  private uploadDirectWithFetch;
  private parseDirectResponse;
  private readDirectError;
  private resumeUpload;
  private fingerprint;
  private checkResume;
  private uploadChunks;
  private uploadChunk;
  private completeUpload;
  private request;
  private isCompressionSupported;
  private isAlreadyCompressed;
  private compress;
  private recordSpeedSample;
  private computeFileHash;
  private digestBuffer;
  private sleep;
  private throwIfAborted;
  private waitIfPaused;
  private resumeIfPaused;
}
export declare function extractFilesFromClipboard(event: ClipboardEvent): File[];
export declare function renameAnonymousFile(file: File): File;
export default class export_default extends Controller<HTMLElement> {
  static targets: string[];
  static values: {
    directUrl: StringConstructor;
    chunkSize: {
      type: NumberConstructor;
      default: number;
    };
    initUrl: StringConstructor;
    removeUrl: StringConstructor;
    csrfToken: StringConstructor;
    maxSize: {
      type: NumberConstructor;
      default: number;
    };
    maxFiles: {
      type: NumberConstructor;
      default: number;
    };
    allowedTypes: {
      type: ArrayConstructor;
      default: never[];
    };
    compression: {
      type: BooleanConstructor;
      default: boolean;
    };
    multiple: {
      type: BooleanConstructor;
      default: boolean;
    };
    required: {
      type: BooleanConstructor;
      default: boolean;
    };
    autoUpload: {
      type: BooleanConstructor;
      default: boolean;
    };
    showPreview: {
      type: BooleanConstructor;
      default: boolean;
    };
    uploader: {
      type: StringConstructor;
      default: string;
    };
    integrityAlgorithm: {
      type: StringConstructor;
      default: string;
    };
    policyToken: StringConstructor;
    labelPending: {
      type: StringConstructor;
      default: string;
    };
    labelComplete: {
      type: StringConstructor;
      default: string;
    };
    labelCancelled: {
      type: StringConstructor;
      default: string;
    };
    labelUploadFailed: {
      type: StringConstructor;
      default: string;
    };
    labelMaxFiles: {
      type: StringConstructor;
      default: string;
    };
    labelFileTooLarge: {
      type: StringConstructor;
      default: string;
    };
    labelFileTypeNotAllowed: {
      type: StringConstructor;
      default: string;
    };
    labelSummaryAllComplete: {
      type: StringConstructor;
      default: string;
    };
    labelSummaryUploading: {
      type: StringConstructor;
      default: string;
    };
    labelSummaryPartial: {
      type: StringConstructor;
      default: string;
    };
    labelSummaryUploadingWithErrors: {
      type: StringConstructor;
      default: string;
    };
    labelSummaryDefault: {
      type: StringConstructor;
      default: string;
    };
    labelAnnounceStarted: {
      type: StringConstructor;
      default: string;
    };
    labelAnnounceComplete: {
      type: StringConstructor;
      default: string;
    };
    labelAnnounceFailed: {
      type: StringConstructor;
      default: string;
    };
    labelAnnounceCancelled: {
      type: StringConstructor;
      default: string;
    };
    labelPaused: {
      type: StringConstructor;
      default: string;
    };
    labelAnnouncePaused: {
      type: StringConstructor;
      default: string;
    };
    labelAnnounceResumed: {
      type: StringConstructor;
      default: string;
    };
  };
  readonly inputTarget: HTMLInputElement;
  readonly dropzoneTarget: HTMLElement;
  readonly errorTarget: HTMLElement;
  readonly errorTemplateTarget: HTMLTemplateElement;
  readonly listTarget: HTMLElement;
  readonly resultTarget: HTMLInputElement;
  readonly templateTarget: HTMLTemplateElement;
  readonly hasInputTarget: boolean;
  readonly hasDropzoneTarget: boolean;
  readonly hasErrorTarget: boolean;
  readonly hasErrorTemplateTarget: boolean;
  readonly hasListTarget: boolean;
  readonly hasResultTarget: boolean;
  readonly hasTemplateTarget: boolean;
  readonly summaryTarget: HTMLElement;
  readonly hasSummaryTarget: boolean;
  readonly summaryTextTarget: HTMLElement;
  readonly hasSummaryTextTarget: boolean;
  readonly summaryProgressTarget: HTMLElement;
  readonly hasSummaryProgressTarget: boolean;
  readonly summaryProgressBarTarget: HTMLElement;
  readonly hasSummaryProgressBarTarget: boolean;
  readonly announceTarget: HTMLElement;
  readonly hasAnnounceTarget: boolean;
  readonly startTarget: HTMLButtonElement;
  readonly hasStartTarget: boolean;
  directUrlValue: string;
  chunkSizeValue: number;
  initUrlValue: string;
  removeUrlValue: string;
  csrfTokenValue: string;
  maxSizeValue: number;
  maxFilesValue: number;
  allowedTypesValue: string[];
  compressionValue: boolean;
  multipleValue: boolean;
  requiredValue: boolean;
  autoUploadValue: boolean;
  showPreviewValue: boolean;
  uploaderValue: string;
  integrityAlgorithmValue: string;
  policyTokenValue: string;
  labelPendingValue: string;
  labelCompleteValue: string;
  labelCancelledValue: string;
  labelUploadFailedValue: string;
  labelMaxFilesValue: string;
  labelFileTooLargeValue: string;
  labelFileTypeNotAllowedValue: string;
  labelSummaryAllCompleteValue: string;
  labelSummaryUploadingValue: string;
  labelSummaryPartialValue: string;
  labelSummaryUploadingWithErrorsValue: string;
  labelSummaryDefaultValue: string;
  labelAnnounceStartedValue: string;
  labelAnnounceCompleteValue: string;
  labelAnnounceFailedValue: string;
  labelAnnounceCancelledValue: string;
  labelPausedValue: string;
  labelAnnouncePausedValue: string;
  labelAnnounceResumedValue: string;
  private uploader;
  private previewCache;
  private cachedBlobUrls;
  private uploads;
  private fileCounter;
  private fileByFile;
  private dragCounter;
  private fileProgress;
  private lastSummaryText;
  private form;
  connect(): void;
  disconnect(): void;
  private hydrate;
  selectFiles(event: Event): void;
  browse(event?: Event): void;
  paste(event: ClipboardEvent): void;
  keydown(event: KeyboardEvent): void;
  drop(event: DragEvent): void;
  dragover(event: DragEvent): void;
  dragleave(event: DragEvent): void;
  cancel(event: Event): void;
  remove(event: Event): Promise<void>;
  retry(event: Event): void;
  pause(event: Event): void;
  resumeUpload(event: Event): void;
  startAll(): void;
  private resolveFileId;
  private createEvents;
  private addDropzoneActive;
  private removeDropzoneActive;
  private isDisabled;
  private addFiles;
  private createUploadItem;
  private updateItemDisplay;
  private syncItemActions;
  private setStatus;
  private findItem;
  private restoreImagePreview;
  private validateFile;
  private startUpload;
  private updateProgress;
  private completeUpload;
  private failUpload;
  private trackBlobUrl;
  private revokeBlobUrls;
  private removeUpload;
  private renderProgress;
  private updateResultInput;
  private findFileIdByUploadId;
  private addError;
  private clearErrors;
  private syncRequired;
  private getProgressAnimationDuration;
  private updateSummary;
  private syncStartAction;
  private syncDropzoneAvailability;
  private announceStatus;
  private showImagePreview;
  private showFileTypeIcon;
}
export type { CompleteResponse, InitResponse, IntegrityAlgorithm, ProgressResponse, UploadConfig, UploadError, UploadResult, UploadSpeed, UploaderEvents, UploaderFetch, UploaderOptions, UploaderXhrFactory };
