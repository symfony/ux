import { Controller } from '@hotwired/stimulus';

declare class export_default extends Controller {
    readonly srcValue: string;
    readonly srcsetValue: any;
    readonly hasSrcsetValue: boolean;
    static values: {
        src: StringConstructor;
        srcset: ObjectConstructor;
    };
    connect(): void;
    _calculateSrcsetString(): string;
    private dispatchEvent;
}

export { export_default as default };
