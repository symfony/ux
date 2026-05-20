import { Controller } from '@hotwired/stimulus';
export declare abstract class AbstractEditorController<T = any> extends Controller<HTMLElement> {
    static values: {
        config: ObjectConstructor;
        format: StringConstructor;
        bridgeId: StringConstructor;
        uploadUrl: StringConstructor;
    };
    static targets: string[];
    readonly configValue: Record<string, unknown>;
    readonly formatValue: string;
    readonly bridgeIdValue: string;
    readonly uploadUrlValue: string;
    readonly inputTarget: HTMLInputElement | HTMLTextAreaElement;
    readonly mountTarget: HTMLElement;
    protected instance?: T;
    abstract createEditor(mount: HTMLElement, config: Record<string, unknown>): Promise<T>;
    abstract serialize(instance: T): string | object | Promise<string | object>;
    abstract destroyEditor(instance: T): Promise<void> | void;
    abstract hotReloadable(): Set<string>;
    abstract applyConfig(diff: Record<string, unknown>, instance: T): Promise<void> | void;
    connect(): Promise<void>;
    syncInput(): void;
    configValueChanged(newCfg: Record<string, unknown>, oldCfg?: Record<string, unknown>): Promise<void>;
    disconnect(): Promise<void>;
    protected diff(a: Record<string, unknown>, b: Record<string, unknown>): Record<string, unknown>;
}
