import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    readonly srcValue: string;
    readonly srcsetValue: any;
    readonly hasSrcsetValue: boolean;
    static values: {
        src: StringConstructor;
        srcset: ObjectConstructor;
    };
    connect(): void;
    _calculateSrcsetString(): string;
    _dispatchEvent(name: string, payload: any): void;
}
