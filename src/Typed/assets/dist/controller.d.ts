import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values: {
        strings: ArrayConstructor;
        typeSpeed: {
            type: NumberConstructor;
            default: number;
        };
        smartBackspace: {
            type: BooleanConstructor;
            default: boolean;
        };
        startDelay: NumberConstructor;
        backSpeed: NumberConstructor;
        shuffle: BooleanConstructor;
        backDelay: {
            type: NumberConstructor;
            default: number;
        };
        fadeOut: BooleanConstructor;
        fadeOutClass: {
            type: StringConstructor;
            default: string;
        };
        fadeOutDelay: {
            type: NumberConstructor;
            default: number;
        };
        loop: BooleanConstructor;
        loopCount: {
            type: NumberConstructor;
            default: number;
        };
        showCursor: {
            type: BooleanConstructor;
            default: boolean;
        };
        cursorChar: {
            type: StringConstructor;
            default: string;
        };
        autoInsertCss: {
            type: BooleanConstructor;
            default: boolean;
        };
        attr: StringConstructor;
        bindInputFocusEvents: BooleanConstructor;
        contentType: {
            type: StringConstructor;
            default: string;
        };
    };
    readonly stringsValue: string[];
    readonly typeSpeedValue: number;
    readonly smartBackspaceValue: boolean;
    readonly startDelayValue?: number;
    readonly backSpeedValue?: number;
    readonly shuffleValue?: boolean;
    readonly backDelayValue: number;
    readonly fadeOutValue?: boolean;
    readonly fadeOutClassValue: string;
    readonly fadeOutDelayValue: number;
    readonly loopValue?: boolean;
    readonly loopCountValue: number;
    readonly showCursorValue: boolean;
    readonly cursorCharValue: string;
    readonly autoInsertCssValue: boolean;
    readonly attrValue?: string;
    readonly bindInputFocusEventsValue?: boolean;
    readonly contentTypeValue: string;
    connect(): void;
    private dispatchEvent;
}
