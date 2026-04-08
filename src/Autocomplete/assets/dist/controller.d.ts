import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";
interface AutocompletePreConnectOptions {
  options: any;
}
interface AutocompleteConnectOptions {
  tomSelect: TomSelect;
  options: any;
}
declare class export_default extends Controller {
  #private;
  static values: {
    url: StringConstructor;
    optionsAsHtml: BooleanConstructor;
    loadingMoreText: StringConstructor;
    noResultsFoundText: StringConstructor;
    noMoreResultsText: StringConstructor;
    createOptionText: StringConstructor;
    minCharacters: NumberConstructor;
    tomSelectOptions: ObjectConstructor;
    preload: StringConstructor;
    resetOnFocus: BooleanConstructor;
  };
  readonly urlValue: string;
  readonly optionsAsHtmlValue: boolean;
  readonly loadingMoreTextValue: string;
  readonly noMoreResultsTextValue: string;
  readonly noResultsFoundTextValue: string;
  readonly createOptionTextValue: string;
  readonly minCharactersValue: number;
  readonly hasMinCharactersValue: boolean;
  readonly tomSelectOptionsValue: object;
  readonly hasPreloadValue: boolean;
  readonly preloadValue: string;
  readonly resetOnFocusValue: boolean;
  tomSelect: TomSelect | undefined;
  private mutationObserver;
  private isObserving;
  private hasLoadedChoicesPreviously;
  private originalOptions;
  initialize(): void;
  connect(): void;
  initializeTomSelect(): void;
  disconnect(): void;
  urlValueChanged(): void;
  private getMaxOptions;
  get selectElement(): HTMLSelectElement | null;
  get formElement(): HTMLInputElement | HTMLSelectElement;
  private dispatchEvent;
  get preload(): string | boolean;
  private resetTomSelect;
  private changeTomSelectDisabledState;
  private startMutationObserver;
  private stopMutationObserver;
  private onMutations;
  private createOptionsDataStructure;
  private areOptionsEquivalent;
}
export { AutocompleteConnectOptions, AutocompletePreConnectOptions, export_default as default };