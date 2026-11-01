import { Controller } from "@hotwired/stimulus";
export default class export_default extends Controller {
  readonly inputTarget: HTMLInputElement;
  readonly placeholderTarget: HTMLDivElement;
  readonly previewTarget: HTMLDivElement;
  readonly previewClearButtonTarget: HTMLButtonElement;
  readonly previewFilenameTarget: HTMLDivElement;
  readonly previewImageTarget: HTMLDivElement;
  readonly previewListTarget: HTMLUListElement;
  readonly hasPreviewListTarget: boolean;
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
  private shownFile;
  private dropping;
  initialize(): void;
  connect(): void;
  disconnect(): void;
  clear(): void;
  onInputChange(event: any): void;
  _populateImagePreview(target: HTMLElement, file: Blob): void;
  onDragEnter(): void;
  onDragLeave(event: any): void;
  onDrop(): void;
  private connectMultiple;
  onMultipleChange(): void;
  private syncMultiple;
  private removeFileAt;
  private onFilesDropped;
  private checkAccept;
  private setInputFiles;
  private reject;
  private containsFile;
  private renderList;
  private buildListItem;
  private dispatchEvent;
}
