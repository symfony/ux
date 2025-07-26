import { Controller } from '@hotwired/stimulus';

declare class CropperController extends Controller {
    readonly publicUrlValue: string;
    readonly optionsValue: object;
    static values: {
        publicUrl: StringConstructor;
        options: ObjectConstructor;
    };
    connect(): void;
    private dispatchEvent;
}

export { CropperController as default };
