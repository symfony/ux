import { Controller } from '@hotwired/stimulus';

declare class export_default extends Controller {
    readonly inputTarget: HTMLInputElement;
    readonly placeholderTarget: HTMLDivElement;
    readonly previewTargets: HTMLDivElement[];
    readonly previewContainerTarget: HTMLDivElement;
    static targets: string[];
    files: Map<string, File>;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    clear(event?: {
        target?: HTMLElement;
        params?: {
            filename?: string;
        };
    }): void;
    onInputChange(): void;
    private renderPreview;
    private clearPreviewContainer;
    private buildPreview;
    _populateImagePreview(element: HTMLElement, file: File): void;
    onDragEnter(): void;
    onDragLeave(event: any): void;
    private updateFileInput;
    private addFiles;
    private isImage;
    private get isMultiple();
    private dispatchEvent;
}

export { export_default as default };
