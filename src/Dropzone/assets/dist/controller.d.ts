import { Controller } from "@hotwired/stimulus";
declare class export_default extends Controller {
  readonly inputTarget: HTMLInputElement;
  readonly placeholderTarget: HTMLDivElement;
  readonly previewTarget: HTMLDivElement;
  readonly previewClearButtonTarget: HTMLButtonElement;
  readonly previewFilenameTarget: HTMLDivElement;
  readonly previewImageTarget: HTMLDivElement;
  static targets: string[];
  initialize(): void;
  connect(): void;
  disconnect(): void;
  clear(): void;
  onInputChange(event: any): void;
  _populateImagePreview(file: Blob): void;
  onDragEnter(): void;
  onDragLeave(event: any): void;
  private dispatchEvent;
}
export { export_default as default };