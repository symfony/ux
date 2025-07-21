import { Controller } from "@hotwired/stimulus";

//#region src/Backend/BackendResponse.d.ts
declare class export_default$1 {
  response: Response;
  private body;
  constructor(response: Response);
  getBody(): Promise<string>;
}
//#endregion
//#region src/Backend/BackendRequest.d.ts
declare class export_default {
  promise: Promise<Response>;
  actions: string[];
  updatedModels: string[];
  isResolved: boolean;
  constructor(promise: Promise<Response>, actions: string[], updateModels: string[]);
  containsOneOfActions(targetedActions: string[]): boolean;
  areAnyModelsUpdated(targetedModels: string[]): boolean;
}
//#endregion
//#region src/Backend/Backend.d.ts
interface ChildrenFingerprints {
  [key: string]: {
    fingerprint: string;
    tag: string;
  };
}
interface BackendInterface {
  makeRequest(props: any, actions: BackendAction[], updated: {
    [key: string]: any;
  }, children: ChildrenFingerprints, updatedPropsFromParent: {
    [key: string]: any;
  }, files: {
    [key: string]: FileList;
  }): export_default;
}
interface BackendAction {
  name: string;
  args: Record<string, string>;
}
//#endregion
//#region src/Component/ElementDriver.d.ts
interface ElementDriver {
  getModelName(element: HTMLElement): string | null;
  getComponentProps(): any;
  getEventsToEmit(): Array<{
    event: string;
    data: any;
    target: string | null;
    componentName: string | null;
  }>;
  getBrowserEventsToDispatch(): Array<{
    event: string;
    payload: any;
  }>;
}
//#endregion
//#region src/Component/plugins/PluginInterface.d.ts
interface PluginInterface {
  attachToComponent(component: Component): void;
}
//#endregion
//#region src/Component/ValueStore.d.ts
declare class export_default$2 {
  private props;
  private dirtyProps;
  private pendingProps;
  private updatedPropsFromParent;
  constructor(props: any);
  get(name: string): any;
  has(name: string): boolean;
  set(name: string, value: any): boolean;
  getOriginalProps(): any;
  getDirtyProps(): any;
  getUpdatedPropsFromParent(): any;
  flushDirtyPropsToPending(): void;
  reinitializeAllProps(props: any): void;
  pushPendingPropsBackToDirty(): void;
  storeNewPropsFromParent(props: any): boolean;
}
//#endregion
//#region src/Component/index.d.ts
type MaybePromise<T = void> = T | Promise<T>;
type ComponentHooks = {
  connect: (component: Component) => MaybePromise;
  disconnect: (component: Component) => MaybePromise;
  'request:started': (requestConfig: any) => MaybePromise;
  'render:finished': (component: Component) => MaybePromise;
  'response:error': (backendResponse: export_default$1, controls: {
    displayError: boolean;
  }) => MaybePromise;
  'loading.state:started': (element: HTMLElement, request: export_default) => MaybePromise;
  'loading.state:finished': (element: HTMLElement) => MaybePromise;
  'model:set': (model: string, value: any, component: Component) => MaybePromise;
};
type ComponentHookName = keyof ComponentHooks;
type ComponentHookCallback<T extends string = ComponentHookName> = T extends ComponentHookName ? ComponentHooks[T] : (...args: any[]) => MaybePromise;
declare class Component {
  readonly element: HTMLElement;
  readonly name: string;
  readonly listeners: Map<string, string[]>;
  private backend;
  readonly elementDriver: ElementDriver;
  id: string | null;
  fingerprint: string;
  readonly valueStore: export_default$2;
  private readonly unsyncedInputsTracker;
  private hooks;
  defaultDebounce: number;
  private backendRequest;
  private pendingActions;
  private pendingFiles;
  private isRequestPending;
  private requestDebounceTimeout;
  private nextRequestPromise;
  private nextRequestPromiseResolve;
  private externalMutationTracker;
  constructor(element: HTMLElement, name: string, props: any, listeners: Array<{
    event: string;
    action: string;
  }>, id: string | null, backend: BackendInterface, elementDriver: ElementDriver);
  addPlugin(plugin: PluginInterface): void;
  connect(): void;
  disconnect(): void;
  on<T extends string | ComponentHookName = ComponentHookName>(hookName: T, callback: ComponentHookCallback<T>): void;
  off<T extends string | ComponentHookName = ComponentHookName>(hookName: T, callback: ComponentHookCallback<T>): void;
  set(model: string, value: any, reRender?: boolean, debounce?: number | boolean): Promise<export_default$1>;
  getData(model: string): any;
  action(name: string, args?: any, debounce?: number | boolean): Promise<export_default$1>;
  files(key: string, input: HTMLInputElement): void;
  render(): Promise<export_default$1>;
  getUnsyncedModels(): string[];
  emit(name: string, data: any, onlyMatchingComponentsNamed?: string | null): void;
  emitUp(name: string, data: any, onlyMatchingComponentsNamed?: string | null): void;
  emitSelf(name: string, data: any): void;
  private performEmit;
  private doEmit;
  private isTurboEnabled;
  private tryStartingRequest;
  private performRequest;
  private processRerender;
  private calculateDebounce;
  private clearRequestDebounceTimeout;
  private debouncedStartRequest;
  private renderError;
  private resetPromise;
  _updateFromParentProps(props: any): void;
}
//#endregion
//#region src/ComponentRegistry.d.ts
declare const getComponent: (element: HTMLElement) => Promise<Component>;
//#endregion
//#region src/live_controller.d.ts
interface LiveEvent extends CustomEvent {
  detail: {
    controller: LiveController;
    component: Component;
  };
}
interface LiveController {
  element: HTMLElement;
  component: Component;
}
declare class LiveControllerDefault extends Controller<HTMLElement> implements LiveController {
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
  $render(): Promise<export_default$1>;
  emit(event: any): void;
  emitUp(event: any): void;
  emitSelf(event: any): void;
  $updateModel(model: string, value: any, shouldRender?: boolean, debounce?: number | boolean): Promise<export_default$1>;
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
//#endregion
export { Component, LiveController, LiveEvent, LiveControllerDefault as default, getComponent };