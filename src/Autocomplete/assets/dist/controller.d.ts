import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';
export interface AutocompletePreConnectOptions {
    options: any;
}
export interface AutocompleteConnectOptions {
    tomSelect: TomSelect;
    options: any;
}
export default class extends Controller {
    #private;
    static values: {
        url: StringConstructor;
        optionsAsHtml: BooleanConstructor;
        noResultsFoundText: StringConstructor;
        noMoreResultsText: StringConstructor;
        minCharacters: NumberConstructor;
        tomSelectOptions: ObjectConstructor;
        preload: StringConstructor;
    };
    readonly urlValue: string;
    readonly optionsAsHtmlValue: boolean;
    readonly noMoreResultsTextValue: string;
    readonly noResultsFoundTextValue: string;
    readonly minCharactersValue: number;
    readonly hasMinCharactersValue: boolean;
    readonly tomSelectOptionsValue: object;
    readonly hasPreloadValue: boolean;
    readonly preloadValue: string;
    tomSelect: TomSelect;
    private mutationObserver;
    private isObserving;
    private hasLoadedChoicesPreviously;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    private getMaxOptions;
    get selectElement(): HTMLSelectElement | null;
    get formElement(): HTMLInputElement | HTMLSelectElement;
    private dispatchEvent;
    get preload(): string | boolean;
    private resetTomSelect;
    private changeTomSelectDisabledState;
    private updateTomSelectPlaceholder;
    private startMutationObserver;
    private stopMutationObserver;
    private onMutations;
    private requiresLiveIgnore;
}
