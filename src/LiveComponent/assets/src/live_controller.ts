import { Controller } from '@hotwired/stimulus';
import { parseDirectives, DirectiveModifier } from './directives_parser';
import { normalizeModelName } from './string_utils';
import {
    getModelDirectiveFromElement,
    getElementAsTagText,
    getValueFromElement,
    elementBelongsToThisComponent,
} from './dom_utils';
import Component, {proxifyComponent} from './Component';
import Backend from './Backend';
import { StandardElementDriver } from './Component/ElementDriver';
import LoadingPlugin from './Component/plugins/LoadingPlugin';
import ValidatedFieldsPlugin from './Component/plugins/ValidatedFieldsPlugin';
import PageUnloadingPlugin from './Component/plugins/PageUnloadingPlugin';
import PollingPlugin from './Component/plugins/PollingPlugin';
import SetValueOntoModelFieldsPlugin from './Component/plugins/SetValueOntoModelFieldsPlugin';
import {PluginInterface} from './Component/plugins/PluginInterface';

interface UpdateModelOptions {
    dispatch?: boolean;
    debounce?: number|boolean;
}

export interface LiveEvent extends CustomEvent {
    detail: {
        controller: LiveController,
        component: Component
    },
}

export interface LiveController {
    element: HTMLElement,
    component: Component
}
export default class extends Controller<HTMLElement> implements LiveController {
    static values = {
        url: String,
        data: Object,
        props: Object,
        csrf: String,
        debounce: { type: Number, default: 150 },
        id: String,
        fingerprint: String,
    }

    readonly urlValue!: string;
    readonly dataValue!: any;
    readonly propsValue!: any;
    readonly csrfValue!: string;
    readonly hasDebounceValue: boolean;
    readonly debounceValue: number;
    readonly fingerprintValue: string

    /** The component, wrapped in the convenience Proxy */
    private proxiedComponent: Component;
    /** The raw Component object */
    component: Component;
    pendingActionTriggerModelElement: HTMLElement|null = null;

    private elementEventListeners: Array<{ event: string, callback: (event: any) => void }> = [
        { event: 'input', callback: (event) => this.handleInputEvent(event) },
        { event: 'change', callback: (event) => this.handleChangeEvent(event) },
        { event: 'live:connect', callback: (event) => this.handleConnectedControllerEvent(event) },
    ];

    initialize() {
        this.handleDisconnectedChildControllerEvent = this.handleDisconnectedChildControllerEvent.bind(this);

        const id = this.element.dataset.liveId || null;

        this.component = new Component(
            this.element,
            this.propsValue,
            this.dataValue,
            this.fingerprintValue,
            id,
            new Backend(this.urlValue, this.csrfValue),
            new StandardElementDriver(),
        );
        this.proxiedComponent = proxifyComponent(this.component);

        // @ts-ignore Adding the dynamic property
        this.element.__component = this.proxiedComponent;

        if (this.hasDebounceValue) {
            this.component.defaultDebounce = this.debounceValue;
        }

        const plugins: PluginInterface[] = [
            new LoadingPlugin(),
            new ValidatedFieldsPlugin(),
            new PageUnloadingPlugin(),
            new PollingPlugin(),
            new SetValueOntoModelFieldsPlugin(),
        ];
        plugins.forEach((plugin) => {
            this.component.addPlugin(plugin);
        });
    }

    connect() {
        this.component.connect();

        this.elementEventListeners.forEach(({event, callback}) => {
            this.component.element.addEventListener(event, callback);
        });

        this._dispatchEvent('live:connect');
    }

    disconnect() {
        this.component.disconnect();

        this.elementEventListeners.forEach(({event, callback}) => {
            this.component.element.removeEventListener(event, callback);
        });

        this._dispatchEvent('live:disconnect');
    }

    /**
     * Called to update one piece of the model.
     *
     *      <button data-action="live#update" data-model="foo" data-value="5">
     */
    update(event: any) {
        if (event.type === 'input' || event.type === 'change') {
            throw new Error(`Since LiveComponents 2.3, you no longer need data-action="live#update" on form elements. Found on element: ${getElementAsTagText(event.target)}`);
        }

        this.updateModelFromElementEvent(event.target, null);
    }

    action(event: any) {
        // using currentTarget means that the data-action and data-action-name
        // must live on the same element: you can't add
        // data-action="click->live#action" on a parent element and
        // expect it to use the data-action-name from the child element
        // that actually received the click
        const rawAction = event.currentTarget.dataset.actionName;

        // data-action-name="prevent|debounce(1000)|save"
        const directives = parseDirectives (rawAction);
        let debounce: number|boolean = false;

        directives.forEach((directive) => {
            const validModifiers: Map<string, (modifier: DirectiveModifier) => void> = new Map();
            validModifiers.set('prevent', () => {
                event.preventDefault();
            });
            validModifiers.set('stop', () => {
                event.stopPropagation();
            });
            validModifiers.set('self', () => {
                if (event.target !== event.currentTarget) {
                    return;
                }
            });
            validModifiers.set('debounce', (modifier: DirectiveModifier) => {
                debounce = modifier.value ? parseInt(modifier.value) : true;
            });

            directive.modifiers.forEach((modifier) => {
                if (validModifiers.has(modifier.name)) {
                    // variable is entirely to make ts happy
                    const callable = validModifiers.get(modifier.name) ?? (() => {});
                    callable(modifier);

                    return;
                }

                console.warn(`Unknown modifier ${modifier.name} in action "${rawAction}". Available modifiers are: ${Array.from(validModifiers.keys()).join(', ')}.`);
            });

            this.component.action(directive.action, directive.named, debounce);

            // TODO: fix this
            // possible case where this element is also a "model" element
            // if so, to be safe, slightly delay the action so that the
            // change/input listener on LiveController can process the
            // model change *before* sending the action
            if (getModelDirectiveFromElement(event.currentTarget, false)) {
                this.pendingActionTriggerModelElement = event.currentTarget;
                //this.#clearRequestDebounceTimeout();
            }
        })
    }

    $render() {
        this.component.render();
    }

    /**
     * Update a model value.
     *
     * The extraModelName should be set to the "name" attribute of an element
     * if it has one. This is only important in a parent/child component,
     * where, in the child, you might be updating a "foo" model, but you
     * also want this update to "sync" to the parent component's "bar" model.
     * Typically, setup on a field like this:
     *
     *      <input data-model="foo" name="bar">
     *
     * @param {string} model The model to update
     * @param {any} value The new value
     * @param {boolean} shouldRender Whether a re-render should be triggered
     * @param {string|null} extraModelName Another model name that this might go by in a parent component.
     * @param {UpdateModelOptions} options
     */
    $updateModel(model: string, value: any, shouldRender = true, extraModelName: string|null = null, options: UpdateModelOptions = {}) {
        const modelName = normalizeModelName(model);
        const normalizedExtraModelName = extraModelName ? normalizeModelName(extraModelName) : null;

        // TODO: support this again
        if (options.dispatch !== false) {
            this._dispatchEvent('live:update-model', {
                modelName,
                extraModelName: normalizedExtraModelName,
                value
            });
        }

        this.component.set(model, value, shouldRender, options.debounce);
    }

    private handleInputEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this.updateModelFromElementEvent(target, 'input')
    }

    private handleChangeEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this.updateModelFromElementEvent(target, 'change')
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
    private updateModelFromElementEvent(element: Element, eventName: string|null) {
        if (!elementBelongsToThisComponent(element, this.component)) {
            return;
        }

        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }

        const modelDirective = getModelDirectiveFromElement(element, false);

        // if not tied to a model, no more work to be done
        if (!modelDirective) {
            return;
        }

        let shouldRender = true;
        let targetEventName = 'input';
        let debounce: number|boolean = false;

        modelDirective.modifiers.forEach((modifier) => {
            switch (modifier.name) {
                case 'on':
                    if (!modifier.value) {
                        throw new Error(`The "on" modifier in ${modelDirective.getString()} requires a value - e.g. on(change).`);
                    }
                    if (!['input', 'change'].includes(modifier.value)) {
                        throw new Error(`The "on" modifier in ${modelDirective.getString()} only accepts the arguments "input" or "change".`);
                    }

                    targetEventName = modifier.value;

                    break;
                case 'norender':
                    shouldRender = false;

                    break;

                case 'debounce':
                    debounce = modifier.value ? parseInt(modifier.value) : true;

                    break;
                default:
                    console.warn(`Unknown modifier "${modifier.name}" in data-model="${modelDirective.getString()}".`);
            }
        });

        // rare case where the same event+element triggers a model
        // update *and* an action. The action is already scheduled
        // to occur, so we do not need to *also* trigger a re-render.
        if (this.pendingActionTriggerModelElement === element) {
            shouldRender = false;
        }

        // e.g. we are targeting "change" and this is the "input" event
        // so do *not* update the model yet
        if (eventName && targetEventName !== eventName) {
            return;
        }

        if (false === debounce) {
            if (targetEventName === 'input') {
                // true debounce will cause default to be used
                debounce = true;
            } else {
                // for change, add no debounce
                debounce = 0;
            }
        }

        const finalValue = getValueFromElement(element, this.component.valueStore);

        this.component.set(modelDirective.action, finalValue, shouldRender, debounce);
    }

    handleConnectedControllerEvent(event: LiveEvent) {
        if (event.target === this.element) {
            return;
        }

        const childController = event.detail.controller;
        if (childController.component.getParent()) {
            // child already has a parent - we are a grandparent
            return;
        }

        this.component.addChild(childController.component);

        // live:disconnect needs to be registered on the child element directly
        // that's because if the child component is removed from the DOM, then
        // the parent controller is no longer an ancestor, so the live:disconnect
        // event would not bubble up to it.
        // @ts-ignore TS doesn't like the LiveEvent arg in the listener, not sure how to fix
        childController.element.addEventListener('live:disconnect', this.handleDisconnectedChildControllerEvent);
    }

    handleDisconnectedChildControllerEvent(event: LiveEvent): void {
        const childController = event.detail.controller;

        // @ts-ignore TS doesn't like the LiveEvent arg in the listener, not sure how to fix
        childController.element.removeEventListener('live:disconnect', this.handleDisconnectedChildControllerEvent);

        // this shouldn't happen: but double-check we're the parent
        if (childController.component.getParent() !== this.component) {
            return;
        }

        this.component.removeChild(childController.component);
    }

    _dispatchEvent(name: string, detail: any = {}, canBubble = true, cancelable = false) {
        detail.controller = this;
        detail.component = this.proxiedComponent;

        return this.element.dispatchEvent(new CustomEvent(name, {
            bubbles: canBubble,
            cancelable,
            detail
        }));
    }
}
