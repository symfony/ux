import { Controller } from '@hotwired/stimulus';
import { parseDirectives, type DirectiveModifier } from './Directive/directives_parser';
import { getModelDirectiveFromElement, getValueFromElement, elementBelongsToThisComponent } from './dom_utils';
import Component, { proxifyComponent } from './Component';
import Backend, { type BackendInterface } from './Backend/Backend';
import { StimulusElementDriver } from './Component/ElementDriver';
import LoadingPlugin from './Component/plugins/LoadingPlugin';
import ValidatedFieldsPlugin from './Component/plugins/ValidatedFieldsPlugin';
import PageUnloadingPlugin from './Component/plugins/PageUnloadingPlugin';
import PollingPlugin from './Component/plugins/PollingPlugin';
import SetValueOntoModelFieldsPlugin from './Component/plugins/SetValueOntoModelFieldsPlugin';
import type { PluginInterface } from './Component/plugins/PluginInterface';
import getModelBinding from './Directive/get_model_binding';
import QueryStringPlugin from './Component/plugins/QueryStringPlugin';
import ChildComponentPlugin from './Component/plugins/ChildComponentPlugin';
import getElementAsTagText from './Util/getElementAsTagText';
import LazyPlugin from './Component/plugins/LazyPlugin';

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
    static values = {
        name: String,
        url: String,
        props: { type: Object, default: {} },
        propsUpdatedFromParent: { type: Object, default: {} },
        listeners: { type: Array, default: [] },
        eventsToEmit: { type: Array, default: [] },
        eventsToDispatch: { type: Array, default: [] },
        debounce: { type: Number, default: 150 },
        fingerprint: { type: String, default: '' },
        requestMethod: { type: String, default: 'post' },
        queryMapping: { type: Object, default: {} },
    };

    declare readonly nameValue: string;
    declare readonly urlValue: string;
    declare readonly propsValue: any;
    declare propsUpdatedFromParentValue: any;
    declare readonly listenersValue: Array<{ event: string; action: string }>;
    declare readonly eventsToEmitValue: Array<{
        event: string;
        data: any;
        target: string | null;
        componentName: string | null;
    }>;
    declare readonly eventsToDispatchValue: Array<{ event: string; payload: any }>;
    declare readonly hasDebounceValue: boolean;
    declare readonly debounceValue: number;
    declare readonly fingerprintValue: string;
    declare readonly requestMethodValue: 'get' | 'post';
    declare readonly queryMappingValue: { [p: string]: { name: string } };

    /** The component, wrapped in the convenience Proxy */
    private proxiedComponent: Component;
    private mutationObserver: MutationObserver;
    /** The raw Component object */
    component: Component;
    pendingActionTriggerModelElement: HTMLElement | null = null;

    private elementEventListeners: Array<{ event: string; callback: (event: any) => void }> = [
        { event: 'input', callback: (event) => this.handleInputEvent(event) },
        { event: 'change', callback: (event) => this.handleChangeEvent(event) },
    ];
    private pendingFiles: { [key: string]: HTMLInputElement } = {};

    static backendFactory: (controller: LiveControllerDefault) => BackendInterface = (controller) =>
        new Backend(controller.urlValue, controller.requestMethodValue);

    initialize() {
        this.mutationObserver = new MutationObserver(this.onMutations.bind(this));

        this.createComponent();
    }

    connect() {
        this.connectComponent();

        this.mutationObserver.observe(this.element, {
            attributes: true,
        });
    }

    disconnect(): void {
        this.disconnectComponent();

        this.mutationObserver.disconnect();
    }

    /**
     * Called to update one piece of the model.
     *
     *      <button data-action="live#update" data-model="foo" data-value="5">
     */
    update(event: any) {
        if (event.type === 'input' || event.type === 'change') {
            throw new Error(
                `Since LiveComponents 2.3, you no longer need data-action="live#update" on form elements. Found on element: ${getElementAsTagText(
                    event.currentTarget
                )}`
            );
        }

        this.updateModelFromElementEvent(event.currentTarget, null);
    }

    action(event: any) {
        const params = event.params;
        if (!params.action) {
            throw new Error(
                `No action name provided on element: ${getElementAsTagText(
                    event.currentTarget
                )}. Did you forget to add the "data-live-action-param" attribute?`
            );
        }
        const rawAction = params.action;
        // all other params are considered action arguments
        const actionArgs = { ...params };
        delete actionArgs.action;

        // data-live-action-param="debounce(1000)|save"
        const directives = parseDirectives(rawAction);
        let debounce: number | boolean = false;

        directives.forEach((directive) => {
            let pendingFiles: { [key: string]: HTMLInputElement } = {};
            const validModifiers: Map<string, (modifier: DirectiveModifier) => void> = new Map();
            validModifiers.set('stop', () => {
                event.stopPropagation();
            });
            validModifiers.set('self', () => {
                if (event.target !== event.currentTarget) {
                    return;
                }
            });
            validModifiers.set('debounce', (modifier: DirectiveModifier) => {
                debounce = modifier.value ? Number.parseInt(modifier.value) : true;
            });
            validModifiers.set('files', (modifier: DirectiveModifier) => {
                if (!modifier.value) {
                    pendingFiles = this.pendingFiles;
                } else if (this.pendingFiles[modifier.value]) {
                    pendingFiles[modifier.value] = this.pendingFiles[modifier.value];
                }
            });

            directive.modifiers.forEach((modifier) => {
                if (validModifiers.has(modifier.name)) {
                    // variable is entirely to make ts happy
                    const callable = validModifiers.get(modifier.name) ?? (() => {});
                    callable(modifier);

                    return;
                }

                console.warn(
                    `Unknown modifier ${modifier.name} in action "${rawAction}". Available modifiers are: ${Array.from(
                        validModifiers.keys()
                    ).join(', ')}.`
                );
            });

            for (const [key, input] of Object.entries(pendingFiles)) {
                if (input.files) {
                    this.component.files(key, input);
                }
                delete this.pendingFiles[key];
            }
            this.component.action(directive.action, actionArgs, debounce);

            // possible case where this element is also a "model" element
            // if so, to be safe, slightly delay the action so that the
            // change/input listener on LiveController can process the
            // model change *before* sending the action
            if (getModelDirectiveFromElement(event.currentTarget, false)) {
                this.pendingActionTriggerModelElement = event.currentTarget;
            }
        });
    }

    $render() {
        return this.component.render();
    }

    emit(event: any) {
        this.getEmitDirectives(event).forEach(({ name, data, nameMatch }) => {
            this.component.emit(name, data, nameMatch);
        });
    }

    emitUp(event: any) {
        this.getEmitDirectives(event).forEach(({ name, data, nameMatch }) => {
            this.component.emitUp(name, data, nameMatch);
        });
    }

    emitSelf(event: any) {
        this.getEmitDirectives(event).forEach(({ name, data }) => {
            this.component.emitSelf(name, data);
        });
    }

    /**
     * Update a model value.
     *
     * @param {string} model The model to update
     * @param {any} value The new value
     * @param {boolean} shouldRender Whether a re-render should be triggered
     * @param {number|boolean} debounce
     */
    $updateModel(model: string, value: any, shouldRender = true, debounce: number | boolean = true) {
        return this.component.set(model, value, shouldRender, debounce);
    }

    propsUpdatedFromParentValueChanged() {
        this.component._updateFromParentProps(this.propsUpdatedFromParentValue);
    }

    fingerprintValueChanged() {
        this.component.fingerprint = this.fingerprintValue;
    }

    private getEmitDirectives(event: any): Array<{ name: string; data: any; nameMatch: string | null }> {
        const params = event.params;
        if (!params.event) {
            throw new Error(
                `No event name provided on element: ${getElementAsTagText(
                    event.currentTarget
                )}. Did you forget to add the "data-live-event-param" attribute?`
            );
        }
        const eventInfo = params.event;
        // all other params are considered event arguments
        const eventArgs = { ...params };
        delete eventArgs.event;

        // data-event="name(product_list)|some_event"
        const directives = parseDirectives(eventInfo);
        const emits: Array<{ name: string; data: any; nameMatch: string | null }> = [];
        directives.forEach((directive) => {
            let nameMatch = null;
            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'name':
                        nameMatch = modifier.value;
                        break;
                    default:
                        throw new Error(`Unknown modifier ${modifier.name} in event "${eventInfo}".`);
                }
            });

            emits.push({
                name: directive.action,
                data: eventArgs,
                nameMatch,
            });
        });

        return emits;
    }

    private createComponent(): void {
        const id = this.element.id || null;

        this.component = new Component(
            this.element,
            this.nameValue,
            this.propsValue,
            this.listenersValue,
            id,
            LiveControllerDefault.backendFactory(this),
            new StimulusElementDriver(this)
        );
        this.proxiedComponent = proxifyComponent(this.component);

        // @ts-ignore Adding the dynamic property
        this.element.__component = this.proxiedComponent;

        if (this.hasDebounceValue) {
            this.component.defaultDebounce = this.debounceValue;
        }

        const plugins: PluginInterface[] = [
            new LoadingPlugin(),
            new LazyPlugin(),
            new ValidatedFieldsPlugin(),
            new PageUnloadingPlugin(),
            new PollingPlugin(),
            new SetValueOntoModelFieldsPlugin(),
            new QueryStringPlugin(this.queryMappingValue),
            new ChildComponentPlugin(this.component),
        ];
        plugins.forEach((plugin) => {
            this.component.addPlugin(plugin);
        });
    }

    private connectComponent() {
        this.component.connect();
        this.mutationObserver.observe(this.element, {
            attributes: true,
        });

        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.addEventListener(event, callback);
        });

        this.dispatchEvent('connect');
    }

    private disconnectComponent() {
        this.component.disconnect();
        this.elementEventListeners.forEach(({ event, callback }) => {
            this.component.element.removeEventListener(event, callback);
        });
        this.dispatchEvent('disconnect');
    }

    private handleInputEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this.updateModelFromElementEvent(target, 'input');
    }

    private handleChangeEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this.updateModelFromElementEvent(target, 'change');
    }

    /**
     * Sets a model given an element and some event.
     *
     * This parses the "data-model" from the element and takes
     * into account modifiers like "debounce", "norender" and "on()".
     *
     * This is used, for example, the grab the new value from an input
     * on "change" and set that new value onto the model.
     *
     * It's also used to, on click, set the value from a button
     * with data-model="" and data-value"".
     *
     * @param element
     * @param eventName If specified (e.g. "input" or "change"), the model may
     *                  skip updating if the on() modifier is passed (e.g. on(change)).
     *                  If not passed, the model will always be updated.
     */
    private updateModelFromElementEvent(element: Element, eventName: string | null): void {
        if (!elementBelongsToThisComponent(element, this.component)) {
            return;
        }

        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }

        // file uploads aren't mapped to model,
        // but needs to be scheduled for sending
        if (element instanceof HTMLInputElement && element.type === 'file') {
            const key = element.name;
            if (element.files?.length) {
                this.pendingFiles[key] = element;
            } else if (this.pendingFiles[key]) {
                delete this.pendingFiles[key];
            }
        }

        const modelDirective = getModelDirectiveFromElement(element, false);

        // if not tied to a model, no more work to be done
        if (!modelDirective) {
            return;
        }

        const modelBinding = getModelBinding(modelDirective);
        if (!modelBinding.targetEventName) {
            modelBinding.targetEventName = 'input';
        }

        // rare case where the same event+element triggers a model
        // update *and* an action. The action is already scheduled
        // to occur, so we do not need to *also* trigger a re-render.
        if (this.pendingActionTriggerModelElement === element) {
            modelBinding.shouldRender = false;
        }

        // just in case, if a "change" event is happening, and this field
        // targets "input", set the model to be safe. This helps when people
        // manually trigger field updates by dispatching a "change" event
        if (eventName === 'change' && modelBinding.targetEventName === 'input') {
            modelBinding.targetEventName = 'change';
        }

        // e.g. we are targeting "change" and this is the "input" event
        // so do *not* update the model yet
        if (eventName && modelBinding.targetEventName !== eventName) {
            return;
        }

        if (false === modelBinding.debounce) {
            if (modelBinding.targetEventName === 'input') {
                // true debounce will cause default to be used
                modelBinding.debounce = true;
            } else {
                // for change, add no debounce
                modelBinding.debounce = 0;
            }
        }

        const finalValue = getValueFromElement(element, this.component.valueStore);

        this.component.set(modelBinding.modelName, finalValue, modelBinding.shouldRender, modelBinding.debounce);
    }

    private dispatchEvent(name: string, detail: any = {}, canBubble = true, cancelable = false) {
        detail.controller = this;
        detail.component = this.proxiedComponent;

        this.dispatch(name, { detail, prefix: 'live', cancelable, bubbles: canBubble });
    }

    private onMutations(mutations: MutationRecord[]): void {
        mutations.forEach((mutation) => {
            if (
                mutation.type === 'attributes' &&
                mutation.attributeName === 'id' &&
                this.element.id !== this.component.id
            ) {
                this.disconnectComponent();
                this.createComponent();
                this.connectComponent();
            }
        });
    }
}
