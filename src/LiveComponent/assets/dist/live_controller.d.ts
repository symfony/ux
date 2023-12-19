import { Controller } from '@hotwired/stimulus';
import Component from './Component';
import ComponentRegistry from './ComponentRegistry';
export { Component };
export declare const getComponent: (element: HTMLElement) => Promise<Component>;
export interface LiveEvent extends CustomEvent {
    detail: {
        controller: LiveController;
        component: Component;
    };
}
export interface LiveController {
    element: HTMLElement;
    component: Component;
}
export default class LiveControllerDefault extends Controller<HTMLElement> implements LiveController {
    static values: {
        name: StringConstructor;
        url: StringConstructor;
        props: ObjectConstructor;
        csrf: StringConstructor;
        listeners: {
            type: ArrayConstructor;
            default: never[];
        };
        debounce: {
            type: NumberConstructor;
            default: number;
        };
        id: StringConstructor;
        fingerprint: {
            type: StringConstructor;
            default: string;
        };
        queryMapping: {
            type: ObjectConstructor;
            default: {};
        };
    };
    readonly nameValue: string;
    readonly urlValue: string;
    readonly propsValue: any;
    readonly csrfValue: string;
    readonly listenersValue: Array<{
        event: string;
        action: string;
    }>;
    readonly hasDebounceValue: boolean;
    readonly debounceValue: number;
    readonly fingerprintValue: string;
    readonly queryMappingValue: {
        [p: string]: {
            name: string;
        };
    };
    private proxiedComponent;
    component: Component;
    pendingActionTriggerModelElement: HTMLElement | null;
    private elementEventListeners;
    private pendingFiles;
    static componentRegistry: ComponentRegistry;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    update(event: any): void;
    action(event: any): void;
    $render(): Promise<import("./Backend/BackendResponse").default>;
    emit(event: Event): void;
    emitUp(event: Event): void;
    emitSelf(event: Event): void;
    private getEmitDirectives;
    $updateModel(model: string, value: any, shouldRender?: boolean, debounce?: number | boolean): Promise<import("./Backend/BackendResponse").default>;
    private handleInputEvent;
    private handleChangeEvent;
    private updateModelFromElementEvent;
    handleConnectedControllerEvent(event: LiveEvent): void;
    handleDisconnectedChildControllerEvent(event: LiveEvent): void;
    private dispatchEvent;
}
