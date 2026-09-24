import { Controller } from "@hotwired/stimulus";
declare class export_default extends Controller<HTMLElement> {
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
  /**
   * Handle file selection from input
   */
  selectFiles(event: Event): void;
  /**
   * Trigger file input click
   */
  browse(event?: Event): void;
  /**
   * Handle paste (e.g. screenshot from clipboard)
   */
  paste(event: ClipboardEvent): void;
  /**
   * Handle keyboard activation on the dropzone
   */
  keydown(event: KeyboardEvent): void;
  /**
   * Handle file drop
   */
  drop(event: DragEvent): void;
  /**
   * Handle dragover
   */
  dragover(event: DragEvent): void;
  /**
   * Handle dragleave
   */
  dragleave(event: DragEvent): void;
  /**
   * Cancel a specific upload
   */
  cancel(event: Event): void;
  /**
   * Remove a completed/cancelled upload from the list
   */
  remove(event: Event): Promise<void>;
  /**
   * Retry a failed upload
   */
  retry(event: Event): void;
  /**
   * Pause an in-progress upload
   */
  pause(event: Event): void;
  /**
   * Resume a paused upload
   */
  resumeUpload(event: Event): void;
  /**
   * Start all pending uploads (used when autoUpload is false)
   */
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
export { export_default as default };