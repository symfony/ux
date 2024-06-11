import { BackendAction, BackendInterface } from '../Backend/Backend';
import ValueStore from './ValueStore';
import { normalizeModelName } from '../string_utils';
import BackendRequest from '../Backend/BackendRequest';
import { elementBelongsToThisComponent, getValueFromElement, htmlToElement } from '../dom_utils';
import { executeMorphdom } from '../morphdom';
import UnsyncedInputsTracker from './UnsyncedInputsTracker';
import { ElementDriver } from './ElementDriver';
import HookManager from '../HookManager';
import { PluginInterface } from './plugins/PluginInterface';
import BackendResponse from '../Backend/BackendResponse';
import ExternalMutationTracker from '../Rendering/ExternalMutationTracker';
import { findComponents, registerComponent, unregisterComponent } from '../ComponentRegistry';

declare const Turbo: any;

type MaybePromise<T = void> = T | Promise<T>;

export type ComponentHooks = {
    'connect': (component: Component) => MaybePromise,
    'disconnect': (component: Component) => MaybePromise,
    'request:started': (requestConfig: any) => MaybePromise,
    'render:finished': (component: Component) => MaybePromise,
    'response:error': (backendResponse: BackendResponse, controls: { displayError: boolean }) => MaybePromise,
    'loading.state.started': (element: HTMLElement, request: BackendRequest) => MaybePromise,
    'loading.state.finished': (element: HTMLElement) => MaybePromise,
    'model:set': (model: string, value: any, component: Component) => MaybePromise,
};

export type ComponentHookName = keyof ComponentHooks;

export type ComponentHookCallback<T extends string = ComponentHookName> = T extends ComponentHookName
    ? ComponentHooks[T]
    : (...args: any[]) => MaybePromise;

export default class Component {
    readonly element: HTMLElement;
    readonly name: string;
    // key is the string event name and value is an array of action names
    readonly listeners: Map<string, string[]>;
    private backend: BackendInterface;
    readonly elementDriver: ElementDriver;
    id: string|null;

    /**
     * A fingerprint that identifies the props/input that was used on
     * the server to create this component, especially if it was a
     * child component. This is sent back to the server and can be used
     * to determine if any "input" to the child component changed and thus,
     * if the child component needs to be re-rendered.
     */
    fingerprint = '';

    readonly valueStore: ValueStore;
    private readonly unsyncedInputsTracker: UnsyncedInputsTracker;
    private hooks: HookManager;

    defaultDebounce = 150;

    private backendRequest: BackendRequest|null = null;
    /** Actions that are waiting to be executed */
    private pendingActions: BackendAction[] = [];
    /** Files that are waiting to be sent */
    private pendingFiles: {[key: string]: HTMLInputElement} = {};
    /** Is a request waiting to be made? */
    private isRequestPending = false;
    /** Current "timeout" before the pending request should be sent. */
    private requestDebounceTimeout: number | null = null;
    private nextRequestPromise: Promise<BackendResponse>;
    private nextRequestPromiseResolve: (response: BackendResponse) => any;

    private externalMutationTracker: ExternalMutationTracker;

    /**
     * @param element The root element
     * @param name    The name of the component
     * @param props   Readonly component props
     * @param listeners Array of event -> action listeners
     * @param id      Some unique id to identify this component. Needed to be a child component
     * @param backend Backend instance for updating
     * @param elementDriver Class to get "model" name from any element.
     */
    constructor(element: HTMLElement, name: string, props: any, listeners: Array<{ event: string; action: string }>, id: string|null, backend: BackendInterface, elementDriver: ElementDriver) {
        this.element = element;
        this.name = name;
        this.backend = backend;
        this.elementDriver = elementDriver;
        this.id = id;

        this.listeners = new Map();
        listeners.forEach((listener) => {
            if (!this.listeners.has(listener.event)) {
                this.listeners.set(listener.event, []);
            }
            this.listeners.get(listener.event)?.push(listener.action);
        });

        this.valueStore = new ValueStore(props);
        this.unsyncedInputsTracker = new UnsyncedInputsTracker(this, elementDriver);
        this.hooks = new HookManager();
        this.resetPromise();

        this.externalMutationTracker = new ExternalMutationTracker(
            this.element,
            (element: Element) => elementBelongsToThisComponent(element, this)
        );
        // start early to catch any mutations that happen before the component is connected
        // for example, the LoadingPlugin, which sets initial non-loading state
        this.externalMutationTracker.start();
    }

    addPlugin(plugin: PluginInterface) {
        plugin.attachToComponent(this);
    }

    connect(): void {
        registerComponent(this);
        this.hooks.triggerHook('connect', this);
        this.unsyncedInputsTracker.activate();
        this.externalMutationTracker.start();
    }

    disconnect(): void {
        unregisterComponent(this);
        this.hooks.triggerHook('disconnect', this);
        this.clearRequestDebounceTimeout();
        this.unsyncedInputsTracker.deactivate();
        this.externalMutationTracker.stop();
    }

    on<T extends string | ComponentHookName = ComponentHookName>(hookName: T, callback: ComponentHookCallback<T>): void {
        this.hooks.register(hookName, callback);
    }

    off<T extends string | ComponentHookName = ComponentHookName>(hookName: T, callback: ComponentHookCallback<T>): void {
        this.hooks.unregister(hookName, callback);
    }

    set(model: string, value: any, reRender = false, debounce: number|boolean = false): Promise<BackendResponse> {
        const promise = this.nextRequestPromise;
        const modelName = normalizeModelName(model);

        if (!this.valueStore.has(modelName)) {
            throw new Error(`Invalid model name "${model}".`);
        }
        const isChanged = this.valueStore.set(modelName, value);

        this.hooks.triggerHook('model:set', model, value, this);

        // the model's data is no longer unsynced
        this.unsyncedInputsTracker.markModelAsSynced(modelName);

        // don't bother re-rendering if the value didn't change
        if (reRender && isChanged) {
            this.debouncedStartRequest(debounce);
        }

        return promise;
    }

    getData(model: string): any {
        const modelName = normalizeModelName(model);
        if (!this.valueStore.has(modelName)) {
            throw new Error(`Invalid model "${model}".`);
        }

        return this.valueStore.get(modelName);
    }

    action(name: string, args: any = {}, debounce: number|boolean = false): Promise<BackendResponse> {
        const promise = this.nextRequestPromise;
        this.pendingActions.push({
            name,
            args
        });

        this.debouncedStartRequest(debounce);

        return promise;
    }

    files(key: string, input: HTMLInputElement): void {
        this.pendingFiles[key] = input;
    }

    render(): Promise<BackendResponse> {
        const promise = this.nextRequestPromise;
        this.tryStartingRequest();

        return promise;
    }

    /**
     * Returns an array of models the user has modified, but whose model has not
     * yet been updated.
     */
    getUnsyncedModels(): string[] {
        return this.unsyncedInputsTracker.getUnsyncedModels();
    }

    emit(name: string, data: any, onlyMatchingComponentsNamed: string|null = null): void {
        return this.performEmit(name, data, false, onlyMatchingComponentsNamed);
    }

    emitUp(name: string, data: any, onlyMatchingComponentsNamed: string|null = null): void {
        return this.performEmit(name, data, true, onlyMatchingComponentsNamed);
    }

    emitSelf(name: string, data: any): void {
        return this.doEmit(name, data);
    }

    private performEmit(name: string, data: any, emitUp: boolean, matchingName: string|null): void {
        const components: Component[] = findComponents(this, emitUp, matchingName);
        components.forEach((component) => {
            component.doEmit(name, data);
        });
    }

    private doEmit(name: string, data: any): void {
        if (!this.listeners.has(name)) {
            return ;
        }

        // set actions but tell TypeScript it is an array of strings
        const actions = this.listeners.get(name) || [];
        actions.forEach((action) => {
            // debounce slightly to allow for multiple actions to queue
            this.action(action, data, 1);
        });
    }

    private isTurboEnabled(): boolean {
        return typeof Turbo !== 'undefined' && !this.element.closest('[data-turbo="false"]');
    }

    private tryStartingRequest(): void {
        if (!this.backendRequest) {
            this.performRequest()

            return;
        }

        // mark that a request is wanted
        this.isRequestPending = true;
    }

    private performRequest(): void {
        // grab the resolve() function for the current promise
        const thisPromiseResolve = this.nextRequestPromiseResolve;
        // then create a fresh Promise, so any future .then() apply to it
        this.resetPromise();

        // any fields that were modified will now be sent on this request:
        // they are now "in sync" (with some exceptions noted inside)
        this.unsyncedInputsTracker.resetUnsyncedFields();

        const filesToSend: {[key: string]: FileList} = {};
        for(const [key, value] of Object.entries(this.pendingFiles)) {
            if (value.files) {
                filesToSend[key] = value.files;
            }
        }

        const requestConfig = {
            props: this.valueStore.getOriginalProps(),
            actions: this.pendingActions,
            updated: this.valueStore.getDirtyProps(),
            children: {},
            updatedPropsFromParent: this.valueStore.getUpdatedPropsFromParent(),
            files: filesToSend,
        };
        this.hooks.triggerHook('request:started', requestConfig);
        this.backendRequest = this.backend.makeRequest(
            requestConfig.props,
            requestConfig.actions,
            requestConfig.updated,
            requestConfig.children,
            requestConfig.updatedPropsFromParent,
            requestConfig.files
        );
        this.hooks.triggerHook('loading.state:started', this.element, this.backendRequest);

        this.pendingActions = [];
        this.valueStore.flushDirtyPropsToPending();
        this.isRequestPending = false;

        this.backendRequest.promise.then(async (response) => {
            const backendResponse = new BackendResponse(response);
            const html = await backendResponse.getBody();

            // clear sent files inputs
            for(const input of Object.values(this.pendingFiles)) {
                input.value = '';
            }

            // if the response does not contain a component, render as an error
            const headers = backendResponse.response.headers;
            if (!headers.get('Content-Type')?.includes('application/vnd.live-component+html') && !headers.get('X-Live-Redirect')) {
                const controls = { displayError: true };
                this.valueStore.pushPendingPropsBackToDirty();
                this.hooks.triggerHook('response:error', backendResponse, controls);

                if (controls.displayError) {
                    this.renderError(html);
                }

                this.backendRequest = null;
                thisPromiseResolve(backendResponse);

                return response;
            }

            this.processRerender(html, backendResponse);

            // finally resolve this promise
            this.backendRequest = null;
            thisPromiseResolve(backendResponse);

            // do we already have another request pending?
            if (this.isRequestPending) {
                this.isRequestPending = false;
                this.performRequest();
            }

            return response;
        });
    }

    private processRerender(html: string, backendResponse: BackendResponse) {
        const controls = { shouldRender: true };
        this.hooks.triggerHook('render:started', html, backendResponse, controls);
        // used to notify that the component doesn't live on the page anymore
        if (!controls.shouldRender) {
            return;
        }

        if (backendResponse.response.headers.get('Location')) {
            // action returned a redirect
            if (this.isTurboEnabled()) {
                Turbo.visit(backendResponse.response.headers.get('Location'));
            } else {
                window.location.href = backendResponse.response.headers.get('Location') || '';
            }

            return;
        }

        // remove the loading behavior now so that when we morphdom
        // "diffs" the elements, any loading differences will not cause
        // elements to appear different unnecessarily
        this.hooks.triggerHook('loading.state:finished', this.element);

        /**
         * For any models modified since the last request started, grab
         * their value now: we will re-set them after the new data from
         * the server has been processed.
         */
        const modifiedModelValues: any = {};
        Object.keys(this.valueStore.getDirtyProps()).forEach((modelName) => {
            modifiedModelValues[modelName] = this.valueStore.get(modelName);
        });

        let newElement: HTMLElement;
        try {
            newElement = htmlToElement(html);

            if (!newElement.matches('[data-controller~=live]')) {
                throw new Error('A live component template must contain a single root controller element.');
            }
        } catch (error) {
            console.error(`There was a problem with the '${this.name}' component HTML returned:`, {
                id: this.id
            });
            throw error;
        }

        // make sure we've processed all external changes before morphing
        this.externalMutationTracker.handlePendingChanges();
        this.externalMutationTracker.stop();
        executeMorphdom(
            this.element,
            newElement,
            this.unsyncedInputsTracker.getUnsyncedInputs(),
            (element: HTMLElement) => getValueFromElement(element, this.valueStore),
            this.externalMutationTracker
        );
        this.externalMutationTracker.start();

        const newProps = this.elementDriver.getComponentProps();
        this.valueStore.reinitializeAllProps(newProps);

        const eventsToEmit = this.elementDriver.getEventsToEmit();
        const browserEventsToDispatch = this.elementDriver.getBrowserEventsToDispatch();

        // reset the modified values back to their client-side version
        Object.keys(modifiedModelValues).forEach((modelName) => {
            this.valueStore.set(modelName, modifiedModelValues[modelName]);
        });

        eventsToEmit.forEach(({ event, data, target, componentName }) => {
            if (target === 'up') {
                this.emitUp(event, data, componentName);

                return;
            }

            if (target === 'self') {
                this.emitSelf(event, data);

                return
            }

            this.emit(event, data, componentName);
        });

        browserEventsToDispatch.forEach(({ event, payload }) => {
            this.element.dispatchEvent(new CustomEvent(event, {
                detail: payload,
                bubbles: true,
            }));
        });

        this.hooks.triggerHook('render:finished', this);
    }

    private calculateDebounce(debounce: number|boolean): number {
        if (debounce === true) {
            return this.defaultDebounce;
        }

        if (debounce === false) {
            return 0;
        }

        return debounce;
    }

    private clearRequestDebounceTimeout() {
        if (this.requestDebounceTimeout) {
            clearTimeout(this.requestDebounceTimeout);
            this.requestDebounceTimeout = null;
        }
    }

    private debouncedStartRequest(debounce: number|boolean) {
        this.clearRequestDebounceTimeout();
        this.requestDebounceTimeout = window.setTimeout(() => {
            this.render();
        }, this.calculateDebounce(debounce));
    }

    // inspired by Livewire!
    private renderError(html: string): void {
        let modal = document.getElementById('live-component-error');
        if (modal) {
            modal.innerHTML = '';
        } else {
            modal = document.createElement('div');
            modal.id = 'live-component-error';
            modal.style.padding = '50px';
            modal.style.backgroundColor = 'rgba(0, 0, 0, .5)';
            modal.style.zIndex = '100000';
            modal.style.position = 'fixed';
            modal.style.top = '0px';
            modal.style.bottom = '0px';
            modal.style.left = '0px';
            modal.style.right = '0px';
            modal.style.display = 'flex';
            modal.style.flexDirection = 'column';
        }

        const iframe = document.createElement('iframe');
        iframe.style.borderRadius = '5px';
        iframe.style.flexGrow = '1';
        modal.appendChild(iframe);

        document.body.prepend(modal);
        document.body.style.overflow = 'hidden';
        if (iframe.contentWindow) {
            iframe.contentWindow.document.open();
            iframe.contentWindow.document.write(html);
            iframe.contentWindow.document.close();
        }

        const closeModal = (modal: HTMLElement|null) => {
            if (modal) {
                modal.outerHTML = ''
            }
            document.body.style.overflow = 'visible'
        }

        // close on click
        modal.addEventListener('click', () => closeModal(modal));

        // close on escape
        modal.setAttribute('tabindex', '0');
        modal.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal(modal);
            }
        });
        modal.focus();
    }

    private resetPromise(): void {
        this.nextRequestPromise = new Promise((resolve) => {
            this.nextRequestPromiseResolve = resolve;
        });
    }

    /**
     * Called on a child component after the parent component render has requested
     * that the child component update its props & re-render if necessary.
     */
    _updateFromParentProps(props: any) {
        // push props directly down onto the value store
        const isChanged = this.valueStore.storeNewPropsFromParent(props);

        if (isChanged) {
            this.render();
        }
    }
}

/**
 * Makes the Component feel more like a JS-version of the PHP component:
 *
 *      // set model like properties
 *      component.firstName = 'Ryan';
 *
 *      // call a live action called "saveStatus" with a "status" arg
 *      component.saveStatus({ status: 'published' });
 */
export function proxifyComponent(component: Component): Component {
    return new Proxy(component, {
        get(component: Component, prop: string|symbol): any {
            // string check is to handle symbols
            if (prop in component || typeof prop !== 'string') {
                if (typeof component[prop as keyof typeof component] === 'function') {
                    const callable = component[prop as keyof typeof component] as (...args: any) => any;
                    return (...args: any) => {
                        return callable.apply(component, args);
                    }
                }

                // forward to public properties
                return Reflect.get(component, prop);
            }

            // return model
            if (component.valueStore.has(prop)) {
                return component.getData(prop)
            }

            // try to call an action
            return (args: string[]) => {
                return component.action.apply(component, [prop, args]);
            }
        },

        set(target: Component, property: string, value: any): boolean {
            if (property in target) {

                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore Ignoring potentially setting private properties
                target[property as keyof typeof target] = value;

                return true;
            }

            target.set(property, value);

            return true;
        },
    });
}
