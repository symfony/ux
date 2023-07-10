import { Controller } from '@hotwired/stimulus';
export default class extends Controller<HTMLInputElement> {
    readonly visibleLabelValue: string;
    readonly visibleIconValue: string;
    readonly hiddenLabelValue: string;
    readonly hiddenIconValue: string;
    readonly buttonClassesValue: Array<string>;
    static values: {
        visibleLabel: StringConstructor;
        visibleIcon: StringConstructor;
        hiddenLabel: StringConstructor;
        hiddenIcon: StringConstructor;
        buttonClasses: ArrayConstructor;
    };
    isDisplayed: boolean;
    visibleIcon: string;
    hiddenIcon: string;
    connect(): void;
    private createButton;
    toggle(event: any): void;
    private dispatchEvent;
}
