import { Controller } from '@hotwired/stimulus';
export type AlertStrategy = 'browser_native' | 'emit_event';
export declare const invalidFormAlertEventName = "interactive-form-validation-invalid-alert";
export default class extends Controller {
    readonly msgValue: string;
    readonly idValue?: string;
    readonly withAlertValue: boolean;
    readonly alertStrategyValue: AlertStrategy;
    static values: {
        msg: StringConstructor;
        id: StringConstructor;
        withAlert: BooleanConstructor;
        alertStrategy: StringConstructor;
    };
    executeBrowserStrategy: () => void;
    executeEmitStrategy: () => void;
    connect(): void;
    resolveStrategy(): (() => void);
}
