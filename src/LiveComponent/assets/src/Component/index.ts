import {BackendAction, BackendInterface} from '../Backend';
import ValueStore from './ValueStore';
import { normalizeModelName } from '../string_utils';
import BackendRequest from '../BackendRequest';
import {
    getValueFromElement, htmlToElement,
} from '../dom_utils';
import {executeMorphdom} from '../morphdom';
import UnsyncedInputsTracker from './UnsyncedInputsTracker';
import { ElementDriver } from './ElementDriver';
import HookManager from '../HookManager';
import { PluginInterface } from './plugins/PluginInterface';
import BackendResponse from '../BackendResponse';

declare const Turbo: any;

export default class Component {
    readonly element: HTMLElement;
    private readonly backend: BackendInterface;
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

    private backendRequest: BackendRequest|null;
    /** Actions that are waiting to be executed */
    private pendingActions: BackendAction[] = [];
    /** Is a request waiting to be made? */
    private isRequestPending = false;
    /** Current "timeout" before the pending request should be sent. */
    private requestDebounceTimeout: number | null = null;
    private nextRequestPromise: Promise<BackendResponse>;
    private nextRequestPromiseResolve: (response: BackendResponse) => any;

    private children: Map<string, Component> = new Map();
    private parent: Component|null = null;

    /**
     * @param element The root element
     * @param props   Readonly component props
     * @param data    Modifiable component data/state
     * @param fingerprint
     * @param id      Some unique id to identify this component. Needed to be a child component
     * @param backend Backend instance for updating
     * @param elementDriver Class to get "model" name from any element.
     */
    constructor(element: HTMLElement, props: any, data: any, fingerprint: string|null, id: string|null, backend: BackendInterface, elementDriver: ElementDriver) {
        this.element = element;
        this.backend = backend;
        this.elementDriver = elementDriver;
        this.id = id;
        this.fingerprint = fingerprint;

        this.valueStore = new ValueStore(props, data);
        this.unsyncedInputsTracker = new UnsyncedInputsTracker(this, elementDriver);
        this.hooks = new HookManager();
        this.resetPromise();
    }

    addPlugin(plugin: PluginInterface) {
        plugin.attachToComponent(this);
    }

    connect(): void {
        this.hooks.triggerHook('connect', this);
        this.unsyncedInputsTracker.activate();
    }

    disconnect(): void {
        this.hooks.triggerHook('disconnect', this);
        this.clearRequestDebounceTimeout();
        this.unsyncedInputsTracker.deactivate();
    }

    /**
     * Add a named hook to the component. Available hooks are:
     *
     *     * connect (component: Component) => {}
     *     * disconnect (component: Component) => {}
     *     * render:started (html: string, response: BackendResponse, controls: { shouldRender: boolean }) => {}
     *     * render:finished (component: Component) => {}
     *     * loading.state:started (element: HTMLElement, request: BackendRequest) => {}
     *     * loading.state:finished (element: HTMLElement) => {}
     *     * model:set (model: string, value: any) => {}
     */
    on(hookName: string, callback: (...args: any[]) => void): void {
        this.hooks.register(hookName, callback);
    }

    set(model: string, value: any, reRender = false, debounce: number|boolean = false): Promise<BackendResponse> {
        const promise = this.nextRequestPromise;
        const modelName = normalizeModelName(model);
        this.valueStore.set(modelName, value);

        this.hooks.triggerHook('model:set', model, value);

        // the model's data is no longer unsynced
        this.unsyncedInputsTracker.markModelAsSynced(modelName);

        if (reRender) {
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

    action(name: string, args: any, debounce: number|boolean = false): Promise<BackendResponse> {
        const promise = this.nextRequestPromise;
        this.pendingActions.push({
            name,
            args
        });

        this.debouncedStartRequest(debounce);

        return promise;
    }

    render(): Promise<BackendResponse> {
        const promise = this.nextRequestPromise;
        this.tryStartingRequest();

        return promise;
    }

    getUnsyncedModels(): string[] {
        return this.unsyncedInputsTracker.getModifiedModels();
    }

    addChild(component: Component): void {
        if (!component.id) {
            throw new Error('Children components must have an id.');
        }

        this.children.set(component.id, component);
        component.parent = this;
    }

    removeChild(child: Component): void {
        if (!child.id) {
            throw new Error('Children components must have an id.');
        }

        this.children.delete(child.id);
        child.parent = null;
    }

    getParent(): Component|null {
        return this.parent;
    }

    getChildren(): Map<string, Component> {
        return new Map(this.children);
    }

    updateFromNewElement(toEl: HTMLElement): boolean {
        const props = this.elementDriver.getComponentProps(toEl);

        // if no props are on the element, use the existing element completely
        // this means the parent is signaling that the child does not need to be re-rendered
        if (props === null) {
            return false;
        }

        // push props directly down onto the value store
        const isChanged = this.valueStore.reinitializeProps(props);

        const fingerprint = toEl.dataset.liveFingerprintValue;
        if (fingerprint !== undefined) {
            this.fingerprint = fingerprint;
        }

        if (isChanged) {
            this.render();
        }

        return false;
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

        this.backendRequest = this.backend.makeRequest(
            this.valueStore.all(),
            this.pendingActions,
            this.valueStore.updatedModels,
            this.getChildrenFingerprints()
        );
        this.hooks.triggerHook('loading.state:started', this.element, this.backendRequest);

        this.pendingActions = [];
        this.valueStore.updatedModels = [];
        this.isRequestPending = false;

        this.backendRequest.promise.then(async (response) => {
            const backendResponse = new BackendResponse(response);
            thisPromiseResolve(backendResponse);
            const html = await backendResponse.getBody();
            // if the response does not contain a component, render as an error
            if (backendResponse.response.headers.get('Content-Type') !== 'application/vnd.live-component+html') {
                this.renderError(html);

                return response;
            }

            this.processRerender(html, backendResponse);

            this.backendRequest = null;

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
            if (typeof Turbo !== 'undefined') {
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
        this.valueStore.updatedModels.forEach((modelName) => {
            modifiedModelValues[modelName] = this.valueStore.get(modelName);
        });

        const newElement = htmlToElement(html);
        // normalize new element into non-loading state before diff
        this.hooks.triggerHook('loading.state:finished', newElement);

        this.valueStore.reinitializeData(this.elementDriver.getComponentData(newElement));
        executeMorphdom(
            this.element,
            newElement,
            this.unsyncedInputsTracker.getUnsyncedInputs(),
            (element: HTMLElement) => getValueFromElement(element, this.valueStore),
            Array.from(this.getChildren().values()),
            this.elementDriver.findChildComponentElement,
            this.elementDriver.getKeyFromElement
        );

        // reset the modified values back to their client-side version
        Object.keys(modifiedModelValues).forEach((modelName) => {
            this.valueStore.set(modelName, modifiedModelValues[modelName]);
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
            modal.style.width = '100vw';
            modal.style.height = '100vh';
        }

        const iframe = document.createElement('iframe');
        iframe.style.borderRadius = '5px';
        iframe.style.width = '100%';
        iframe.style.height = '100%';
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

    private getChildrenFingerprints(): any {
        const fingerprints: any = {};

        this.children.forEach((child) => {
            if (!child.id) {
                throw new Error('missing id');
            }

            fingerprints[child.id] = child.fingerprint;
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
