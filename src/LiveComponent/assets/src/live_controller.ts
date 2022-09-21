import { Controller } from '@hotwired/stimulus';
import morphdom from 'morphdom';
import { parseDirectives, Directive } from './directives_parser';
import { combineSpacedArray, normalizeModelName } from './string_utils';
import { haveRenderedValuesChanged } from './have_rendered_values_changed';
import { normalizeAttributesForComparison } from './normalize_attributes_for_comparison';
import ValueStore from './ValueStore';
import {
    elementBelongsToThisController,
    getModelDirectiveFromElement,
    getValueFromElement,
    cloneHTMLElement,
    htmlToElement,
    getElementAsTagText,
    setValueOnElement
} from './dom_utils';
import UnsyncedInputContainer from './UnsyncedInputContainer';

interface ElementLoadingDirectives {
    element: HTMLElement|SVGElement,
    directives: Directive[]
}

interface UpdateModelOptions {
    dispatch?: boolean;
    debounce?: number|null;
}

declare const Turbo: any;

const DEFAULT_DEBOUNCE = 150;

export interface LiveController {
    dataValue: any;
    element: Element,
    childComponentControllers: Array<LiveController>
}

export default class extends Controller implements LiveController {
    static values = {
        url: String,
        data: Object,
        csrf: String,
        /**
         * The Debounce timeout.
         *
         * Default: 150
         */
        debounce: Number,
    }

    readonly urlValue!: string;
    dataValue!: any;
    readonly csrfValue!: string;
    readonly debounceValue!: number;
    readonly hasDebounceValue: boolean;

    backendRequest: BackendRequest|null;
    valueStore!: ValueStore;

    /** Actions that are waiting to be executed */
    pendingActions: Array<{ name: string, args: Record<string, string> }> = [];
    /** Has anything requested a re-render? */
    isRerenderRequested = false;

    /**
     * Current "timeout" before the pending request should be sent.
     */
    requestDebounceTimeout: number | null = null;

    pollingIntervals: NodeJS.Timer[] = [];

    isWindowUnloaded = false;

    originalDataJSON = '{}';

    mutationObserver: MutationObserver|null = null;

    /**
     * Model form fields that have "changed", but whose model value hasn't been set yet.
     */
    unsyncedInputs!: UnsyncedInputContainer;

    childComponentControllers: Array<LiveController> = [];

    pendingActionTriggerModelElement: HTMLElement|null = null;

    initialize() {
        this.markAsWindowUnloaded = this.markAsWindowUnloaded.bind(this);
        this.handleUpdateModelEvent = this.handleUpdateModelEvent.bind(this);
        this.handleInputEvent = this.handleInputEvent.bind(this);
        this.handleChangeEvent = this.handleChangeEvent.bind(this);
        this.handleConnectedControllerEvent = this.handleConnectedControllerEvent.bind(this);
        this.handleDisconnectedControllerEvent = this.handleDisconnectedControllerEvent.bind(this);
        this.valueStore = new ValueStore(this);
        this.originalDataJSON = this.valueStore.asJson();
        this.unsyncedInputs = new UnsyncedInputContainer();
        this._exposeOriginalData();
        this.synchronizeValueOfModelFields();
    }

    connect() {
        // hide "loading" elements to begin with
        // This is done with CSS, but only for the most basic cases
        this._onLoadingFinish();

        // helps typescript be sure this is an HTMLElement, not just Element
        if (!(this.element instanceof HTMLElement)) {
            throw new Error('Invalid Element Type');
        }

        this._initiatePolling();

        window.addEventListener('beforeunload', this.markAsWindowUnloaded);
        this._startAttributesMutationObserver();
        this.element.addEventListener('live:update-model', this.handleUpdateModelEvent);
        this.element.addEventListener('input', this.handleInputEvent);
        this.element.addEventListener('change', this.handleChangeEvent);
        this.element.addEventListener('live:connect', this.handleConnectedControllerEvent);

        this._dispatchEvent('live:connect', { controller: this });
    }

    disconnect() {
        this._stopAllPolling();
        this.#clearRequestDebounceTimeout();

        window.removeEventListener('beforeunload', this.markAsWindowUnloaded);
        this.element.removeEventListener('live:update-model', this.handleUpdateModelEvent);
        this.element.removeEventListener('input', this.handleInputEvent);
        this.element.removeEventListener('change', this.handleChangeEvent);
        this.element.removeEventListener('live:connect', this.handleConnectedControllerEvent);
        this.element.removeEventListener('live:disconnect', this.handleDisconnectedControllerEvent);

        this._dispatchEvent('live:disconnect', { controller: this });

        if (this.mutationObserver) {
            this.mutationObserver.disconnect();
        }
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

        this._updateModelFromElement(event.target, null);
    }

    action(event: any) {
        // using currentTarget means that the data-action and data-action-name
        // must live on the same element: you can't add
        // data-action="click->live#action" on a parent element and
        // expect it to use the data-action-name from the child element
        // that actually received the click
        const rawAction = event.currentTarget.dataset.actionName;

        // data-action-name="prevent|debounce(1000)|save"
        const directives = parseDirectives(rawAction);

        directives.forEach((directive) => {
            this.pendingActions.push({
                name: directive.action,
                args: directive.named
            });

            let handled = false;
            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'prevent':
                        event.preventDefault();
                        break;
                    case 'stop':
                        event.stopPropagation();
                        break;
                    case 'self':
                        if (event.target !== event.currentTarget) {
                            return;
                        }
                        break;
                    case 'debounce': {
                        const length: number = modifier.value ? parseInt(modifier.value) : this.getDefaultDebounce();

                        this.#clearRequestDebounceTimeout();
                        this.requestDebounceTimeout = window.setTimeout(() => {
                            this.requestDebounceTimeout = null;
                            this.#startPendingRequest();
                        }, length);

                        handled = true;

                        break;
                    }

                    default:
                        console.warn(`Unknown modifier ${modifier.name} in action ${rawAction}`);
                }
            });

            if (!handled) {
                // possible case where this element is also a "model" element
                // if so, to be safe, slightly delay the action so that the
                // change/input listener on LiveController can process the
                // model change *before* sending the action
                if (getModelDirectiveFromElement(event.currentTarget, false)) {
                    this.pendingActionTriggerModelElement = event.currentTarget;
                    this.#clearRequestDebounceTimeout();
                    window.setTimeout(() => {
                        this.pendingActionTriggerModelElement = null;
                        this.#startPendingRequest();
                    }, 10);

                    return;
                }

                this.#startPendingRequest();
            }
        })
    }

    $render() {
        this.isRerenderRequested = true;
        this.#startPendingRequest();
    }

    /**
     * @param element
     * @param eventName If specified (e.g. "input" or "change"), the model may
     *                  skip updating if the on() modifier is passed (e.g. on(change)).
     *                  If not passed, the model will always be updated.
     */
    _updateModelFromElement(element: Element, eventName: string|null) {
        if (!elementBelongsToThisController(element, this)) {
            return;
        }

        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }

        const modelDirective = getModelDirectiveFromElement(element, false);
        if (eventName === 'input') {
            const modelName = modelDirective ? modelDirective.action : null;
            // track any inputs that are "unsynced"
            this.unsyncedInputs.add(element, modelName);
        }

        // if not tied to a model, no more work to be done
        if (!modelDirective) {
            return;
        }

        let shouldRender = true;
        let targetEventName = 'input';
        let debounce: number|null = null;

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
                    debounce = modifier.value ? parseInt(modifier.value) : this.getDefaultDebounce();

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

        if (null === debounce) {
            if (targetEventName === 'input') {
                // for the input event, add a debounce by default
                debounce = this.getDefaultDebounce();
            } else {
                // for change, add no debounce
                debounce = 0;
            }
        }

        const finalValue = getValueFromElement(element, this.valueStore);

        this.$updateModel(
            modelDirective.action,
            finalValue,
            shouldRender,
            element.hasAttribute('name') ? element.getAttribute('name') : null,
            {
                debounce
            }
        );
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

        // if there is a "validatedFields" data, it means this component wants
        // to track which fields have been / should be validated.
        // in that case, when the model is updated, mark that it should be validated
        if (this.valueStore.has('validatedFields')) {
            const validatedFields = [...this.valueStore.get('validatedFields')];
            if (validatedFields.indexOf(modelName) === -1) {
                validatedFields.push(modelName);
            }
            this.valueStore.set('validatedFields', validatedFields);
        }

        if (options.dispatch !== false) {
            this._dispatchEvent('live:update-model', {
                modelName,
                extraModelName: normalizedExtraModelName,
                value
            });
        }

        // we do not send old and new data to the server
        // we merge in the new data now
        // TODO: handle edge case for top-level of a model with "exposed" props
        // For example, suppose there is a "post" field but "post.title" is exposed.
        // If there is a data-model="post", then the "post" data - which was
        // previously an array with "id" and "title" fields - will now be set
        // directly to the new post id (e.g. 4). From a saving standpoint,
        // that is fine: the server sees the "4" and uses it for the post data.
        // However, there is an edge case where the user changes data-model="post"
        // and then, for some reason, they don't want an immediate re-render.
        // Then, they modify the data-model="post.title" field. In theory,
        // we should be smart enough to convert the post data - which is now
        // the string "4" - back into an array with [id=4, title=new_title].
        this.valueStore.set(modelName, value);

        // the model's data is no longer unsynced
        this.unsyncedInputs.markModelAsSynced(modelName);

        // skip rendering if there is an action Ajax call processing
        if (shouldRender) {
            let debounce: number = this.getDefaultDebounce();
            if (options.debounce !== undefined && options.debounce !== null) {
                debounce = options.debounce;
            }

            this.#clearRequestDebounceTimeout();
            // debouncing even with a 0 value is enough to allow any other potential
            // events happening right now (e.g. from custom user JavaScript) to
            // finish setting other models before making the request.
            this.requestDebounceTimeout = window.setTimeout(() => {
                this.requestDebounceTimeout = null;
                this.isRerenderRequested = true;
                this.#startPendingRequest();
            }, debounce);
        }
    }

    /**
     * Makes a request to the server with all pending actions/updates, if any.
     */
    #startPendingRequest(): void {
        if (!this.backendRequest && (this.pendingActions.length > 0 || this.isRerenderRequested)) {
            this.#makeRequest();
        }
    }

    #makeRequest() {
        const splitUrl = this.urlValue.split('?');
        let [url] = splitUrl
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');

        const actions = this.pendingActions;
        this.pendingActions = [];
        this.isRerenderRequested = false;
        // we're making a request NOW, so no need to make another one after debouncing
        this.#clearRequestDebounceTimeout();

        const fetchOptions: RequestInit = {};
        fetchOptions.headers = {
            'Accept': 'application/vnd.live-component+html',
        };

        const updatedModels = this.valueStore.updatedModels;
        this.valueStore.updatedModels = [];
        if (actions.length === 0 && this._willDataFitInUrl(this.valueStore.asJson(), params)) {
            params.set('data', this.valueStore.asJson());
            updatedModels.forEach((model) => {
                params.append('updatedModels[]', model);
            });
            fetchOptions.method = 'GET';
        } else {
            fetchOptions.method = 'POST';
            fetchOptions.headers['Content-Type'] = 'application/json';
            const requestData: any = { data: this.valueStore.all() };
            requestData.updatedModels = updatedModels;

            if (actions.length > 0) {
                // one or more ACTIONs
                if (this.csrfValue) {
                    fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfValue;
                }

                if (actions.length === 1) {
                    // simple, single action
                    requestData.args = actions[0].args;

                    url += `/${encodeURIComponent(actions[0].name)}`;
                } else {
                    url += '/_batch';
                    requestData.actions = actions;
                }
            }

            fetchOptions.body = JSON.stringify(requestData);
        }

        this._onLoadingStart();
        const paramsString = params.toString();
        const thisPromise = fetch(`${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`, fetchOptions);
        this.backendRequest = new BackendRequest(thisPromise);
        thisPromise.then(async (response) => {
            // if the response does not contain a component, render as an error
            const html = await response.text();
            if (response.headers.get('Content-Type') !== 'application/vnd.live-component+html') {
                this.renderError(html);

                return;
            }

            this.#processRerender(html, response);

            this.backendRequest = null;
            this.#startPendingRequest();
        })
    }

    /**
     * Processes the response from an AJAX call and uses it to re-render.
     *
     * @private
     */
    #processRerender(html: string, response: Response) {
        // check if the page is navigating away
        if (this.isWindowUnloaded) {
            return;
        }

        if (response.headers.get('Location')) {
            // action returned a redirect
            if (typeof Turbo !== 'undefined') {
                Turbo.visit(response.headers.get('Location'));
            } else {
                window.location.href = response.headers.get('Location') || '';
            }

            return;
        }

        // remove the loading behavior now so that when we morphdom
        // "diffs" the elements, any loading differences will not cause
        // elements to appear different unnecessarily
        this._onLoadingFinish();

        if (!this._dispatchEvent('live:render', html, true, true)) {
            // preventDefault() was called
            return;
        }

        /**
         * For any models modified since the last request started, grab
         * their value now: we will re-set them after the new data from
         * the server has been processed.
         */
        const modifiedModelValues: any = {};
        this.valueStore.updatedModels.forEach((modelName) => {
            modifiedModelValues[modelName] = this.valueStore.get(modelName);
        });

        // merge/patch in the new HTML
        this._executeMorphdom(html, this.unsyncedInputs.all());

        // reset the modified values back to their client-side version
        Object.keys(modifiedModelValues).forEach((modelName) => {
            this.valueStore.set(modelName, modifiedModelValues[modelName]);
        });

        this.synchronizeValueOfModelFields();
    }

    _onLoadingStart() {
        this._handleLoadingToggle(true);
    }

    _onLoadingFinish() {
        this._handleLoadingToggle(false);
    }

    _handleLoadingToggle(isLoading: boolean) {
        this._getLoadingDirectives().forEach(({ element, directives }) => {
            // so we can track, at any point, if an element is in a "loading" state
            if (isLoading) {
                this._addAttributes(element, ['data-live-is-loading']);
            } else {
                this._removeAttributes(element, ['data-live-is-loading']);
            }

            directives.forEach((directive) => {
                this._handleLoadingDirective(element, isLoading, directive)
            });
        });
    }

    /**
     * @private
     */
    _handleLoadingDirective(element: HTMLElement|SVGElement, isLoading: boolean, directive: Directive) {
        const finalAction = parseLoadingAction(directive.action, isLoading);

        let loadingDirective: (() => void);

        switch (finalAction) {
            case 'show':
                loadingDirective = () => {
                    this._showElement(element)
                };
                break;

            case 'hide':
                loadingDirective = () => this._hideElement(element);
                break;

            case 'addClass':
                loadingDirective = () => this._addClass(element, directive.args);
                break;

            case 'removeClass':
                loadingDirective = () => this._removeClass(element, directive.args);
                break;

            case 'addAttribute':
                loadingDirective = () => this._addAttributes(element, directive.args);
                break;

            case 'removeAttribute':
                loadingDirective = () => this._removeAttributes(element, directive.args);
                break;

            default:
                throw new Error(`Unknown data-loading action "${finalAction}"`);
        }

        let isHandled = false;
        directive.modifiers.forEach((modifier => {
            switch (modifier.name) {
                case 'delay': {
                    // if loading has *stopped*, the delay modifier has no effect
                    if (!isLoading) {
                        break;
                    }

                    const delayLength = modifier.value ? parseInt(modifier.value) : 200;
                    window.setTimeout(() => {
                        if (element.hasAttribute('data-live-is-loading')) {
                            loadingDirective();
                        }
                    }, delayLength);

                    isHandled = true;

                    break;
                }
                default:
                    throw new Error(`Unknown modifier ${modifier.name} used in the loading directive ${directive.getString()}`)
            }
        }));

        // execute the loading directive
        if (!isHandled) {
            loadingDirective();
        }
    }

    _getLoadingDirectives() {
        const loadingDirectives: ElementLoadingDirectives[] = [];

        this.element.querySelectorAll('[data-loading]').forEach((element => {
            if (!(element instanceof HTMLElement) && !(element instanceof SVGElement)) {
                throw new Error('Invalid Element Type');
            }

            // use "show" if the attribute is empty
            const directives = parseDirectives(element.dataset.loading || 'show');

            loadingDirectives.push({
                element,
                directives,
            });
        }));

        return loadingDirectives;
    }

    _showElement(element: HTMLElement|SVGElement) {
        element.style.display = 'inline-block';
    }

    _hideElement(element: HTMLElement|SVGElement) {
        element.style.display = 'none';
    }

    _addClass(element: HTMLElement|SVGElement, classes: string[]) {
        element.classList.add(...combineSpacedArray(classes));
    }

    _removeClass(element: HTMLElement|SVGElement, classes: string[]) {
        element.classList.remove(...combineSpacedArray(classes));

        // remove empty class="" to avoid morphdom "diff" problem
        if (element.classList.length === 0) {
            this._removeAttributes(element, ['class']);
        }
    }

    _addAttributes(element: Element, attributes: string[]) {
        attributes.forEach((attribute) => {
            element.setAttribute(attribute, '');
        })
    }

    _removeAttributes(element: Element, attributes: string[]) {
        attributes.forEach((attribute) => {
            element.removeAttribute(attribute);
        })
    }

    _willDataFitInUrl(dataJson: string, params: URLSearchParams) {
        const urlEncodedJsonData = new URLSearchParams(dataJson).toString();

        // if the URL gets remotely close to 2000 chars, it may not fit
        return (urlEncodedJsonData + params.toString()).length < 1500;
    }

    _executeMorphdom(newHtml: string, modifiedElements: Array<HTMLElement>) {
        const newElement = htmlToElement(newHtml);
        morphdom(this.element, newElement, {
            getNodeKey: (node: Node) => {
              if (!(node instanceof HTMLElement)) {
                  return;
              }

              return node.dataset.liveId;
            },
            onBeforeElUpdated: (fromEl, toEl) => {
                if (!(fromEl instanceof HTMLElement) || !(toEl instanceof HTMLElement)) {
                    return false;
                }

                // if this field's value has been modified since this HTML was
                // requested, set the toEl's value to match the fromEl
                if (modifiedElements.includes(fromEl)) {
                    setValueOnElement(toEl, getValueFromElement(fromEl, this.valueStore))
                }

                // https://github.com/patrick-steele-idem/morphdom#can-i-make-morphdom-blaze-through-the-dom-tree-even-faster-yes
                if (fromEl.isEqualNode(toEl)) {
                    // the nodes are equal, but the "value" on some might differ
                    // lets try to quickly compare a bit more deeply
                    const normalizedFromEl = cloneHTMLElement(fromEl);
                    normalizeAttributesForComparison(normalizedFromEl);

                    const normalizedToEl = cloneHTMLElement(toEl);
                    normalizeAttributesForComparison(normalizedToEl);

                    if (normalizedFromEl.isEqualNode(normalizedToEl)) {
                        // don't bother updating
                        return false;
                    }
                }

                // avoid updating child components: they will handle themselves
                const controllerName = fromEl.hasAttribute('data-controller') ? fromEl.getAttribute('data-controller') : null;
                if (controllerName
                    && controllerName.split(' ').indexOf('live') !== -1
                    && fromEl !== this.element
                    && !this._shouldChildLiveElementUpdate(fromEl, toEl)
                ) {
                    return false;
                }

                // look for data-live-ignore, and don't update
                if (fromEl.hasAttribute('data-live-ignore')) {
                    return false;
                }

                return true;
            },

            onBeforeNodeDiscarded(node) {
                if (!(node instanceof HTMLElement)) {
                    // text element
                    return true;
                }

                if (node.hasAttribute('data-live-ignore')) {
                    return false;
                }
                return true;
            }
        });
        // restore the data-original-data attribute
        this._exposeOriginalData();
    }

    markAsWindowUnloaded = () => {
        this.isWindowUnloaded = true;
    };

    handleConnectedControllerEvent(event: any) {
        if (event.target === this.element) {
            return;
        }

        this.childComponentControllers.push(event.detail.controller);
        // live:disconnect needs to be registered on the child element directly
        // that's because if the child component is removed from the DOM, then
        // the parent controller is no longer an ancestor, so the live:disconnect
        // event would not bubble up to it.
        event.detail.controller.element.addEventListener('live:disconnect', this.handleDisconnectedControllerEvent);
    }

    handleDisconnectedControllerEvent(event: any) {
        if (event.target === this.element) {
            return;
        }

        const index = this.childComponentControllers.indexOf(event.detail.controller);

        // Remove value from an array
        if (index > -1) {
            this.childComponentControllers.splice(index, 1);
        }
    }

    handleUpdateModelEvent(event: any) {
        // ignore events that we dispatched
        if (event.target === this.element) {
            return;
        }

        this._handleChildComponentUpdateModel(event);
    }

    handleInputEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this._updateModelFromElement(target, 'input')
    }

    handleChangeEvent(event: Event) {
        const target = event.target as Element;
        if (!target) {
            return;
        }

        this._updateModelFromElement(target, 'change')
    }

    _initiatePolling() {
        this._stopAllPolling();

        if ((this.element as HTMLElement).dataset.poll === undefined) {
            return;
        }

        const rawPollConfig = (this.element as HTMLElement).dataset.poll;
        const directives = parseDirectives(rawPollConfig || '$render');

        directives.forEach((directive) => {
            let duration = 2000;

            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'delay':
                        if (modifier.value) {
                            duration = parseInt(modifier.value);
                        }

                         break;
                    default:
                        console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
                }
            });

            this._startPoll(directive.action, duration);
        })
    }

    _startPoll(actionName: string, duration: number) {
        let callback: () => void;
        if (actionName.charAt(0) === '$') {
            callback = () => {
                (this as any)[actionName]();
            }
        } else {
            callback = () => {
                this.pendingActions.push({ name: actionName, args: {}})
                this.#startPendingRequest();
            }
        }

        const timer = setInterval(() => {
            callback();
        }, duration);
        this.pollingIntervals.push(timer);
    }

    _dispatchEvent(name: string, payload: object | string | null = null, canBubble = true, cancelable = false) {
        return this.element.dispatchEvent(new CustomEvent(name, {
            bubbles: canBubble,
            cancelable,
            detail: payload
        }));
    }

    _handleChildComponentUpdateModel(event: any) {
        const mainModelName = event.detail.modelName;
        const potentialModelNames = [
            { name: mainModelName, required: false },
        ];
        if (event.detail.extraModelName) {
            potentialModelNames.push({ name: event.detail.extraModelName, required: false });
        }

        const modelMapElement = event.target.closest('[data-model-map]');
        if (this.element.contains(modelMapElement)) {
            const directives = parseDirectives(modelMapElement.dataset.modelMap);

            directives.forEach((directive) => {
                let from = null;
                directive.modifiers.forEach((modifier) => {
                    switch (modifier.name) {
                        case 'from':
                            if (!modifier.value) {
                                throw new Error(`The from() modifier requires a model name in data-model-map="${modelMapElement.dataset.modelMap}"`);
                            }
                            from = modifier.value;

                            break;
                        default:
                            console.warn(`Unknown modifier "${modifier.name}" in data-model-map="${modelMapElement.dataset.modelMap}".`);
                    }
                });

                if (!from) {
                    throw new Error(`Missing from() modifier in data-model-map="${modelMapElement.dataset.modelMap}". The format should be "from(childModelName)|parentModelName"`);
                }

                // only look maps for the model currently being updated
                if (from !== mainModelName) {
                    return;
                }

                potentialModelNames.push({ name: directive.action, required: true });
            });
        }

        potentialModelNames.reverse();
        let foundModelName: string | null = null;
        potentialModelNames.forEach((potentialModel) => {
            if (foundModelName) {
                return;
            }

            if (this.valueStore.hasAtTopLevel(potentialModel.name)) {
                foundModelName = potentialModel.name;

                return;
            }

            if (potentialModel.required) {
                throw new Error(`The model name "${potentialModel.name}" does not exist! Found in data-model-map="from(${mainModelName})|${potentialModel.name}"`);
            }
        });

        if (!foundModelName) {
            return;
        }

        this.$updateModel(
            foundModelName,
            event.detail.value,
            false,
            null,
            {
                dispatch: false
            }
        );
    }

    /**
     * Determines if a child live element should be re-rendered.
     *
     * This is called when this element re-renders and detects that
     * a child element is inside. Normally, in that case, we do not
     * re-render the child element. However, if we detect that the
     * "data" on the child element has changed from its initial data,
     * then this will trigger a re-render.
     */
    _shouldChildLiveElementUpdate(fromEl: HTMLElement, toEl: HTMLElement): boolean {
        if (!fromEl.dataset.originalData) {
            throw new Error('Missing From Element originalData');
        }
        if (!fromEl.dataset.liveDataValue) {
            throw new Error('Missing From Element liveDataValue');
        }
        if (!toEl.dataset.liveDataValue) {
            throw new Error('Missing To Element liveDataValue');
        }

        return haveRenderedValuesChanged(
            fromEl.dataset.originalData,
            fromEl.dataset.liveDataValue,
            toEl.dataset.liveDataValue
        );
    }

    _exposeOriginalData() {
        if (!(this.element instanceof HTMLElement)) {
            throw new Error('Invalid Element Type');
        }

        this.element.dataset.originalData = this.originalDataJSON;
    }

    /**
     * Helps "re-normalize" certain root element attributes after a re-render.
     *
     *      1) Re-establishes the data-original-data attribute if missing.
     *      2) Stops or re-initializes data-poll
     *
     * This happens if a parent component re-renders a child component
     * and morphdom *updates* child. This commonly happens if a parent
     * component is around a list of child components, and changing
     * something in the parent causes the list to change. In that case,
     * the a child component might be removed while another is added.
     * But to morphdom, this sometimes looks like an "update". The result
     * is that the child component is re-rendered, but the child component
     * is not re-initialized. And so, the data-original-data attribute
     * is missing and never re-established.
     */
    _startAttributesMutationObserver() {
        if (!(this.element instanceof HTMLElement)) {
            throw new Error('Invalid Element Type');
        }
        const element : HTMLElement = this.element;

        this.mutationObserver = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes') {
                    if (!element.dataset.originalData) {
                        this.originalDataJSON = this.valueStore.asJson();
                        this._exposeOriginalData();
                    }

                    this._initiatePolling();
                }
            });
        });

        this.mutationObserver.observe(this.element, {
            attributes: true
        });
    }

    private getDefaultDebounce(): number {
        return this.hasDebounceValue ? this.debounceValue : DEFAULT_DEBOUNCE;
    }

    private _stopAllPolling() {
        this.pollingIntervals.forEach((interval) => {
            clearInterval(interval);
        });
    }

    // inspired by Livewire!
    private async renderError(html: string) {
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

    #clearRequestDebounceTimeout() {
        // clear any pending renders
        if (this.requestDebounceTimeout) {
            clearTimeout(this.requestDebounceTimeout);
            this.requestDebounceTimeout = null;
        }
    }

    /**
     * Sets the "value" of all model fields to the component data.
     *
     * This is called when the component initializes and after re-render.
     * Take the following element:
     *
     *      <input data-model="firstName">
     *
     * This method will set the "value" of that element to the value of
     * the "firstName" model.
     */
    private synchronizeValueOfModelFields(): void {
        this.element.querySelectorAll('[data-model]').forEach((element) => {
            if (!(element instanceof HTMLElement)) {
                throw new Error('Invalid element using data-model.');
            }

            if (element instanceof HTMLFormElement) {
                return;
            }

            const modelDirective = getModelDirectiveFromElement(element);
            if (!modelDirective) {
                return;
            }

            const modelName = modelDirective.action;

            // skip any elements whose model name is currently in an unsynced state
            if (this.unsyncedInputs.getModifiedModels().includes(modelName)) {
                return;
            }

            if (this.valueStore.has(modelName)) {
                setValueOnElement(element, this.valueStore.get(modelName))
            }

            // for select elements without a blank value, one might be selected automatically
            // https://github.com/symfony/ux/issues/469
            if (element instanceof HTMLSelectElement && !element.multiple) {
                this.valueStore.set(modelName, getValueFromElement(element, this.valueStore));
            }
        })
    }
}

class BackendRequest {
    promise: Promise<any>;

    constructor(promise: Promise<any>) {
        this.promise = promise;
    }
}

const parseLoadingAction = function(action: string, isLoading: boolean) {
    switch (action) {
        case 'show':
            return isLoading ? 'show' : 'hide';
        case 'hide':
            return isLoading ? 'hide' : 'show';
        case 'addClass':
            return isLoading ? 'addClass' : 'removeClass';
        case 'removeClass':
            return isLoading ? 'removeClass' : 'addClass';
        case 'addAttribute':
            return isLoading ? 'addAttribute' : 'removeAttribute';
        case 'removeAttribute':
            return isLoading ? 'removeAttribute' : 'addAttribute';
    }

    throw new Error(`Unknown data-loading action "${action}"`);
}
