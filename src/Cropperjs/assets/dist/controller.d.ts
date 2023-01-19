import { Controller } from '@hotwired/stimulus';
export default class CropperController extends Controller {
    readonly publicUrlValue: string;
    readonly optionsValue: object;
    static values: {
        publicUrl: StringConstructor;
        options: ObjectConstructor;
    };
    connect(): void;
    private dispatchEvent;
}
