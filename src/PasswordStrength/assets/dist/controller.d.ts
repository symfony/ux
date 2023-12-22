import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values: {
        veryWeakMessage: {
            type: StringConstructor;
            default: string;
        };
        weakMessage: {
            type: StringConstructor;
            default: string;
        };
        mediumMessage: {
            type: StringConstructor;
            default: string;
        };
        strongMessage: {
            type: StringConstructor;
            default: string;
        };
        veryStrongMessage: {
            type: StringConstructor;
            default: string;
        };
    };
    static targets: string[];
    readonly veryWeakMessageValue: string;
    readonly weakMessageValue: string;
    readonly mediumMessageValue: string;
    readonly strongMessageValue: string;
    readonly veryStrongMessageValue: string;
    readonly scoreTargets: HTMLElement[];
    readonly messageTargets: HTMLElement[];
    readonly meterTargets: HTMLElement[];
    connect(): void;
    private dispatchEvent;
    estimatePasswordStrength(event: InputEvent): void;
    private countChars;
}
