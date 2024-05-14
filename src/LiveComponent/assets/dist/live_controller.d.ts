import { Controller } from '@hotwired/stimulus';
import Component from './Component';
import { type BackendInterface } from './Backend/Backend';
export { Component };
export { getComponent } from './ComponentRegistry';
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
        props: {
            type: ObjectConstructor;
            default: {};
        };
        propsUpdatedFromParent: {
            type: ObjectConstructor;
            default: {};
        };
        csrf: StringConstructor;
        listeners: {
            type: ArrayConstructor;
            default: never[];
        };
        eventsToEmit: {
            type: ArrayConstructor;
            default: never[];
        };
        eventsToDispatch: {
            type: ArrayConstructor;
            default: never[];
        };
        debounce: {
            type: NumberConstructor;
            default: number;
        };
        fingerprint: {
            type: StringConstructor;
            default: string;
        };
        requestMethod: {
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
    propsUpdatedFromParentValue: any;
    readonly csrfValue: string;
    readonly listenersValue: Array<{
        event: string;
        action: string;
    }>;
    readonly eventsToEmitValue: Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }>;
    readonly eventsToDispatchValue: Array<{
        event: string;
        payload: any;
    }>;
    readonly hasDebounceValue: boolean;
    readonly debounceValue: number;
    readonly fingerprintValue: string;
    readonly requestMethodValue: 'get' | 'post';
    readonly queryMappingValue: {
        [p: string]: {
            name: string;
        };
    };
    private proxiedComponent;
    private mutationObserver;
    component: Component;
    pendingActionTriggerModelElement: HTMLElement | null;
    private elementEventListeners;
    private pendingFiles;
    static backendFactory: (controller: LiveControllerDefault) => BackendInterface;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    update(event: any): void;
    action(event: any): void;
    $render(): Promise<import("./Backend/BackendResponse").default>;
    emit(event: any): void;
    emitUp(event: any): void;
    emitSelf(event: any): void;
    $updateModel(model: string, value: any, shouldRender?: boolean, debounce?: number | boolean): Promise<import("./Backend/BackendResponse").default>;
    propsUpdatedFromParentValueChanged(): void;
    fingerprintValueChanged(): void;
    private getEmitDirectives;
    private createComponent;
    private connectComponent;
    private disconnectComponent;
    private handleInputEvent;
    private handleChangeEvent;
    private updateModelFromElementEvent;
    private dispatchEvent;
    private onMutations;
}
