import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    readonly inputTarget: HTMLInputElement;
    readonly placeholderTarget: HTMLElement;
    readonly previewTargets: HTMLElement[];
    readonly previewContainerTarget: HTMLElement;
    readonly previewTemplateTarget: HTMLTemplateElement;
    readonly optionsValue: any;
    static values: {
        options: {
            type: ObjectConstructor;
            default: {
                preview: {
                    style: string;
                    can_open_file_picker: boolean;
                    can_toggle_placeholder: boolean;
                };
            };
        };
    };
    static targets: string[];
    files: Map<string, File>;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    clear(): void;
    onInputChange(event: any): void;
    onDragLeave(event: any): void;
    onDragOver(event: any): void;
    onDrop(event: any): void;
    onPreviewContainerClick(event: any): void;
    onPreviewButtonClick(event: any): void;
    private dispatchEvent;
    private addFiles;
    private buildPreview;
    private refreshPreview;
    private isImage;
    private get isMultiple();
    private updateFileInput;
    private formatBytes;
    private get firstFile();
    private get isLegacy();
    private refreshLegacyPreview;
    private showLegacyPreview;
    private hideLegacyPreview;
    private showLegacyFileInput;
    private hideLegacyFileInput;
}
