import { Controller } from "@hotwired/stimulus";
export interface DropzonePreAddEventDetail {
  readonly files: readonly File[];
  readonly waitUntil: (files: readonly File[] | PromiseLike<readonly File[]>) => void;
}
export interface DropzoneRejectEventDetail {
  readonly files: readonly File[];
  readonly reason: 'accept' | 'max_files';
}
export default class DropzoneController extends Controller {
  private static readonly connectedDropzones;
  private static readonly heldSubmitters;
  readonly inputTarget: HTMLInputElement;
  readonly placeholderTarget: HTMLDivElement;
  readonly previewTarget: HTMLDivElement;
  readonly hasPreviewTarget: boolean;
  readonly previewClearButtonTarget: HTMLButtonElement;
  readonly previewFilenameTarget: HTMLDivElement;
  readonly hasPreviewFilenameTarget: boolean;
  readonly previewImageTarget: HTMLDivElement;
  readonly hasPreviewImageTarget: boolean;
  readonly previewListTarget: HTMLUListElement;
  readonly hasPreviewListTarget: boolean;
  readonly previewItemTemplateTarget: HTMLTemplateElement;
  readonly hasPreviewItemTemplateTarget: boolean;
  readonly multipleValue: boolean;
  readonly maxFilesValue: number;
  readonly removeLabelValue: string;
  static targets: string[];
  static values: {
    multiple: BooleanConstructor;
    maxFiles: NumberConstructor;
    removeLabel: {
      type: StringConstructor;
      default: string;
    };
  };
  private dataTransfer;
  private previewItem;
  private processingCount;
  private readonly dragTargets;
  private queue;
  private readonly pendingSelections;
  private generation;
  private dropping;
  private form;
  private resetTimeout;
  private readonly objectUrls;
  private renderedBlobs;
  private idleCallbacks;
  private readonly dataUrls;
  private readonly previewedFiles;
  initialize(): void;
  connect(): void;
  disconnect(): void;
  previewClearButtonTargetConnected(button: HTMLButtonElement): void;
  previewClearButtonTargetDisconnected(button: HTMLButtonElement): void;
  clear(): void;
  onInputChange(): void;
  _populateImagePreview(target: HTMLElement, file: Blob): void;
  onDragEnter(event: Event): void;
  onDragLeave(event: Event): void;
  onDrop(): void;
  private onFormReset;
  private preAdd;
  private whenIdle;
  private notifyIdle;
  private add;
  private removeFileAt;
  private checkAccept;
  private checkMaxFiles;
  private syncInput;
  private reject;
  private render;
  private buildListItem;
  private buildListItemFromTemplate;
  private isImage;
  private previewUrl;
  private readAsDataUrl;
  private revokeObjectUrls;
  private bindRemove;
  private isFileDrag;
  private updateState;
  private dispatchEvent;
  private static readonly onSubmit;
}
