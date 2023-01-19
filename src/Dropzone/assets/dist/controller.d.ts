import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    readonly inputTarget: HTMLInputElement;
    readonly placeholderTarget: HTMLDivElement;
    readonly previewTarget: HTMLDivElement;
    readonly previewClearButtonTarget: HTMLButtonElement;
    readonly previewFilenameTarget: HTMLDivElement;
    readonly previewImageTarget: HTMLDivElement;
    static targets: string[];
    connect(): void;
    clear(): void;
    onInputChange(event: any): void;
    _populateImagePreview(file: Blob): void;
    private dispatchEvent;
}
