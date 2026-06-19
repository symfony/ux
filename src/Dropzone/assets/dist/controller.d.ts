import { Controller } from "@hotwired/stimulus";
declare class export_default extends Controller {
  readonly inputTarget: HTMLInputElement;
  readonly placeholderTarget: HTMLDivElement;
  readonly previewTarget: HTMLDivElement;
  readonly previewClearButtonTarget: HTMLButtonElement;
  readonly previewFilenameTarget: HTMLDivElement;
  readonly previewImageTarget: HTMLDivElement;
  readonly previewListTarget: HTMLUListElement;
  readonly hasPreviewListTarget: boolean;
  readonly multipleValue: boolean;
  static targets: string[];
  static values: {
    multiple: BooleanConstructor;
  };
  private dataTransfer;
  initialize(): void;
  connect(): void;
  disconnect(): void;
  clear(): void;
  onInputChange(event: any): void;
  _populateImagePreview(target: HTMLElement, file: Blob): void;
  onDragEnter(): void;
  onDragLeave(event: any): void;
  private connectMultiple;
  onMultipleChange(): void;
  private syncMultiple;
  private removeFileAt;
  private containsFile;
  private renderList;
  private buildListItem;
  private dispatchEvent;
}
export { export_default as default };