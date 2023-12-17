import { BackendAction, BackendInterface, ChildrenFingerprints } from '../Backend/Backend';
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
import { ModelBinding } from '../Directive/get_model_binding';
import ExternalMutationTracker from '../Rendering/ExternalMutationTracker';

declare const Turbo: any;

export type ComponentFinder = (currentComponent: Component, onlyParents: boolean, onlyMatchName: string|null) => Component[];

class ChildComponentWrapper {
    component: Component;
    modelBindings: ModelBinding[];

    constructor(component: Component, modelBindings: ModelBinding[]) {
        this.component = component;
        this.modelBindings = modelBindings;
    }
}

export default class Component {
    readonly element: HTMLElement;
    readonly name: string;
    // key is the string event name and value is an array of action names
    readonly listeners: Map<string, string[]>;
    private readonly componentFinder: ComponentFinder;
    private backend: BackendInterface;
    private readonly elementDriver: ElementDriver;
    id: string|null;

    /**
     * A fingerprint that identifies the props/input that was used on
     * the server to create this component, especially if it was a
     * child component. This is sent back to the server and can be used
     * to determine if any "input" to the child component changed and thus,
     * if the child component needs to be re-rendered.
     */
    fingerprint: string|null;

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

    private children: Map<string, ChildComponentWrapper> = new Map();
    private parent: Component|null = null;

    private externalMutationTracker: ExternalMutationTracker;

    /**
     * @param element The root element
     * @param name    The name of the component
     * @param props   Readonly component props
     * @param listeners Array of event -> action listeners
     * @param componentFinder
     * @param fingerprint
     * @param id      Some unique id to identify this component. Needed to be a child component
     * @param backend Backend instance for updating
     * @param elementDriver Class to get "model" name from any element.
     */
    constructor(element: HTMLElement, name: string, props: any, listeners: Array<{ event: string; action: string }>, componentFinder: ComponentFinder, fingerprint: string|null, id: string|null, backend: BackendInterface, elementDriver: ElementDriver) {
        this.element = element;
        this.name = name;
        this.componentFinder = componentFinder;
        this.backend = backend;
        this.elementDriver = elementDriver;
        this.id = id;
        this.fingerprint = fingerprint;

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

        this.onChildComponentModelUpdate = this.onChildComponentModelUpdate.bind(this);
    }

    /**
     * @internal
     */
    _swapBackend(backend: BackendInterface) {
        this.backend = backend;
    }

    addPlugin(plugin: PluginInterface) {
        plugin.attachToComponent(this);
    }

    connect(): void {
        this.hooks.triggerHook('connect', this);
        this.unsyncedInputsTracker.activate();
        this.externalMutationTracker.start();
    }

    disconnect(): void {
        this.hooks.triggerHook('disconnect', this);
        this.clearRequestDebounceTimeout();
        this.unsyncedInputsTracker.deactivate();
        this.externalMutationTracker.stop();
    }

    /**
     * Add a named hook to the component. Available hooks are:
     *
     *     * connect (component: Component) => {}
     *     * disconnect (component: Component) => {}
     *     * render:started (html: string, response: BackendResponse, controls: { shouldRender: boolean }) => {}
     *     * render:finished (component: Component) => {}
     *     * response:error (backendResponse: BackendResponse, controls: { displayError: boolean }) => {}
     *     * loading.state:started (element: HTMLElement, request: BackendRequest) => {}
     *     * loading.state:finished (element: HTMLElement) => {}
     *     * model:set (model: string, value: any, component: Component) => {}
     */
    on(hookName: string, callback: (...args: any[]) => void): void {
        this.hooks.register(hookName, callback);
    }

    off(hookName: string, callback: (...args: any[]) => void): void {
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

    addChild(child: Component, modelBindings: ModelBinding[] = []): void {
        if (!child.id) {
            throw new Error('Children components must have an id.');
        }

        this.children.set(child.id, new ChildComponentWrapper(child, modelBindings));
        child.parent = this;
        child.on('model:set', this.onChildComponentModelUpdate);
    }

    removeChild(child: Component): void {
        if (!child.id) {
            throw new Error('Children components must have an id.');
        }

        this.children.delete(child.id);
        child.parent = null;
        child.off('model:set', this.onChildComponentModelUpdate);
    }

    getParent(): Component|null {
        return this.parent;
    }

    getChildren(): Map<string, Component> {
        const children: Map<string, Component> = new Map();
        this.children.forEach((childComponent, id) => {
            children.set(id, childComponent.component);
        });

        return children;
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
        const components = this.componentFinder(this, emitUp, matchingName);
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

    /**
     * Called during morphdom: read props from toEl and re-render if necessary.
     *
     * @param toEl
     */
    updateFromNewElementFromParentRender(toEl: HTMLElement): boolean {
        const props = this.elementDriver.getComponentProps(toEl);

        // if no props are on the element, use the existing element completely
        // this means the parent is signaling that the child does not need to be re-rendered
        if (props === null) {
            return false;
        }

        // push props directly down onto the value store
        const isChanged = this.valueStore.storeNewPropsFromParent(props);

        const fingerprint = toEl.dataset.liveFingerprintValue;
        if (fingerprint !== undefined) {
            this.fingerprint = fingerprint;
        }

        if (isChanged) {
            this.render();
        }

        return isChanged;
    }

    /**
     * Handles data-model binding from a parent component onto a child.
     */
    onChildComponentModelUpdate(modelName: string, value: any, childComponent: Component): void {
        if (!childComponent.id) {
            throw new Error('Missing id');
        }

        const childWrapper = this.children.get(childComponent.id);
        if (!childWrapper) {
            throw new Error('Missing child');
        }

        childWrapper.modelBindings.forEach((modelBinding) => {
            const childModelName = modelBinding.innerModelName || 'value';

            // skip, unless childModelName matches the model that just changed
            if (childModelName !== modelName) {
                return;
            }

            this.set(
                modelBinding.modelName,
                value,
                modelBinding.shouldRender,
                modelBinding.debounce
            );
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

        this.backendRequest = this.backend.makeRequest(
            this.valueStore.getOriginalProps(),
            this.pendingActions,
            this.valueStore.getDirtyProps(),
            this.getChildrenFingerprints(),
            this.valueStore.getUpdatedPropsFromParent(),
            filesToSend,
        );
        this.hooks.triggerHook('loading.state:started', this.element, this.backendRequest);

        this.pendingActions = [];
        this.valueStore.flushDirtyPropsToPending();
        this.isRequestPending = false;

        this.backendRequest.promise.then(async (response) => {
            this.backendRequest = null;
            const backendResponse = new BackendResponse(response);
            const html = await backendResponse.getBody();

            // clear sent files inputs
            for(const input of Object.values(this.pendingFiles)) {
                input.value = '';
            }

            // if the response does not contain a component, render as an error
            const headers = backendResponse.response.headers;
            if (headers.get('Content-Type') !== 'application/vnd.live-component+html' && !headers.get('X-Live-Redirect')) {
                const controls = { displayError: true };
                this.valueStore.pushPendingPropsBackToDirty();
                this.hooks.triggerHook('response:error', backendResponse, controls);

                if (controls.displayError) {
                    this.renderError(html);
                }

                thisPromiseResolve(backendResponse);

                return response;
            }

            this.processRerender(html, backendResponse);

            // finally resolve this promise
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
            console.error('There was a problem with the component HTML returned:');

            throw error;
        }

        const newProps = this.elementDriver.getComponentProps(newElement);
        this.valueStore.reinitializeAllProps(newProps);

        const eventsToEmit = this.elementDriver.getEventsToEmit(newElement);
        const browserEventsToDispatch = this.elementDriver.getBrowserEventsToDispatch(newElement);

        // make sure we've processed all external changes before morphing
        this.externalMutationTracker.handlePendingChanges();
        this.externalMutationTracker.stop();
        executeMorphdom(
            this.element,
            newElement,
            this.unsyncedInputsTracker.getUnsyncedInputs(),
            (element: HTMLElement) => getValueFromElement(element, this.valueStore),
            Array.from(this.getChildren().values()),
            this.elementDriver.findChildComponentElement,
            this.elementDriver.getKeyFromElement,
            this.externalMutationTracker
        );
        this.externalMutationTracker.start();

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

    private getChildrenFingerprints(): ChildrenFingerprints {
        const fingerprints: ChildrenFingerprints = {};

        this.children.forEach((childComponent) => {
            const child = childComponent.component;
            if (!child.id) {
                throw new Error('missing id');
            }

            fingerprints[child.id] = {
                fingerprint: child.fingerprint as string,
                tag: child.element.tagName.toLowerCase(),
            };
        });

        return fingerprints;
    }

    private resetPromise(): void {
        this.nextRequestPromise = new Promise((resolve) => {
            this.nextRequestPromiseResolve = resolve;
        });
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
