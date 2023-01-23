import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';
export default class extends Controller {
    #private;
    static values: {
        url: StringConstructor;
        optionsAsHtml: BooleanConstructor;
        noResultsFoundText: StringConstructor;
        noMoreResultsText: StringConstructor;
        minCharacters: {
            type: NumberConstructor;
            default: number;
        };
        tomSelectOptions: ObjectConstructor;
        preload: StringConstructor;
    };
    readonly urlValue: string;
    readonly optionsAsHtmlValue: boolean;
    readonly noMoreResultsTextValue: string;
    readonly noResultsFoundTextValue: string;
    readonly minCharactersValue: number;
    readonly tomSelectOptionsValue: object;
    readonly hasPreloadValue: boolean;
    readonly preloadValue: string;
    tomSelect: TomSelect;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    get selectElement(): HTMLSelectElement | null;
    get formElement(): HTMLInputElement | HTMLSelectElement;
    private dispatchEvent;
    get preload(): string | boolean;
}
