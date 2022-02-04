import { Controller } from '@hotwired/stimulus';
import morphdom from 'morphdom';
import { parseDirectives, Directive } from './directives_parser';
import { combineSpacedArray } from './string_utils';
import { setDeepData, doesDeepPropertyExist, normalizeModelName } from './set_deep_data';
import { haveRenderedValuesChanged } from './have_rendered_values_changed';
import { normalizeAttributesForComparison } from './normalize_attributes_for_comparison';
import { cloneHTMLElement } from './clone_html_element';

interface ElementLoadingDirectives {
    element: HTMLElement|SVGElement,
    directives: Directive[]
}

declare const Turbo: any;

const DEFAULT_DEBOUNCE = 150;

export default class extends Controller {
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

    /**
     * The current "timeout" that's waiting before a model update
     * triggers a re-render.
     */
    renderDebounceTimeout: number | null = null;

    /**
     * The current "timeout" that's waiting before an action should
     * be taken.
     */
    actionDebounceTimeout: number | null = null;

    /**
     * A stack of all current AJAX Promises for re-rendering.
     *
     * @type {PromiseStack}
     */
    renderPromiseStack = new PromiseStack();

    pollingIntervals: NodeJS.Timer[] = [];

    isWindowUnloaded = false;

    originalDataJSON = '{}';

    initialize() {
        this.markAsWindowUnloaded = this.markAsWindowUnloaded.bind(this);
        this.originalDataJSON = JSON.stringify(this.dataValue);
        this._exposeOriginalData();
    }

    connect() {
        // hide "loading" elements to begin with
        // This is done with CSS, but only for the most basic cases
        this._onLoadingFinish();

        // helps typescript be sure this is an HTMLElement, not just Element
        if (!(this.element instanceof HTMLElement)) {
            throw new Error('Invalid Element Type');
        }

        if (this.element.dataset.poll !== undefined) {
            this._initiatePolling(this.element.dataset.poll);
        }

        window.addEventListener('beforeunload', this.markAsWindowUnloaded);

        this.element.addEventListener('live:update-model', (event) => {
            // ignore events that we dispatched
            if (event.target === this.element) {
                return;
            }

            this._handleChildComponentUpdateModel(event);
        });

        this._dispatchEvent('live:connect');
    }

    disconnect() {
        this.pollingIntervals.forEach((interval) => {
            clearInterval(interval);
        });

        window.removeEventListener('beforeunload', this.markAsWindowUnloaded);
    }

    /**
     * Called to update one piece of the model
     */
    update(event: any) {
        const value = this._getValueFromElement(event.target);

        this._updateModelFromElement(event.target, value, true);
    }

    updateDefer(event: any) {
        const value = this._getValueFromElement(event.target);

        this._updateModelFromElement(event.target, value, false);
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
            // set here so it can be delayed with debouncing below
            const _executeAction = () => {
                // if any normal renders are waiting to start, cancel them
                // allow the action to start and finish
                // this covers a case where you "blur" a field to click "save"
                // the "change" event will trigger first & schedule a re-render
                // then the action Ajax will start. We want to avoid the
                // re-render request from starting after the debounce and
                // taking precedence
                this._clearWaitingDebouncedRenders();

                this._makeRequest(directive.action, directive.named);
            }

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
                        const length: number = modifier.value ? parseInt(modifier.value) : DEFAULT_DEBOUNCE;

                        // clear any pending renders
                        if (this.actionDebounceTimeout) {
                            clearTimeout(this.actionDebounceTimeout);
                            this.actionDebounceTimeout = null;
                        }

                        this.actionDebounceTimeout = window.setTimeout(() => {
                            this.actionDebounceTimeout = null;
                            _executeAction();
                        }, length);

                        handled = true;

                        break;
                    }

                    default:
                        console.warn(`Unknown modifier ${modifier.name} in action ${rawAction}`);
                }
            });

            if (!handled) {
                _executeAction();
            }
        })
    }

    $render() {
        this._makeRequest(null);
    }

    _getValueFromElement(element: HTMLElement|SVGElement) {
        return element.dataset.value || (element as any).value;
    }

    _updateModelFromElement(element: Element, value: string, shouldRender: boolean) {
        if (!(element instanceof HTMLElement)) {
            throw new Error('Could not update model for non HTMLElement');
        }

        const model = element.dataset.model || element.getAttribute('name');

        if (!model) {
            const clonedElement = cloneHTMLElement(element);

            throw new Error(`The update() method could not be called for "${clonedElement.outerHTML}": the element must either have a "data-model" or "name" attribute set to the model name.`);
        }

        this.$updateModel(model, value, shouldRender, element.hasAttribute('name') ? element.getAttribute('name') : null);
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
     * @param {string} model The model update, which could include modifiers
     * @param {any} value The new value
     * @param {boolean} shouldRender Whether a re-render should be triggered
     * @param {string|null} extraModelName Another model name that this might go by in a parent component.
     * @param {Object} options Options include: {bool} dispatch
     */
    $updateModel(model: string, value: any, shouldRender = true, extraModelName: string | null = null, options: any = {}) {
        const directives = parseDirectives(model);
        if (directives.length > 1) {
            throw new Error(`The data-model="${model}" format is invalid: it does not support multiple directives (i.e. remove any spaces).`);
        }

        const directive = directives[0];

        if (directive.args.length > 0 || directive.named.length > 0) {
            throw new Error(`The data-model="${model}" format is invalid: it does not support passing arguments to the model.`);
        }

        const modelName = normalizeModelName(directive.action);
        const normalizedExtraModelName = extraModelName ? normalizeModelName(extraModelName) : null;

        // if there is a "validatedFields" data, it means this component wants
        // to track which fields have been / should be validated.
        // in that case, when the model is updated, mark that it should be validated
        if (this.dataValue.validatedFields !== undefined) {
            const validatedFields = [...this.dataValue.validatedFields];
            if (validatedFields.indexOf(modelName) === -1) {
                validatedFields.push(modelName);
            }
            this.dataValue = setDeepData(this.dataValue, 'validatedFields', validatedFields);
        }

        if (options.dispatch !== false) {
            this._dispatchEvent('live:update-model', {
                modelName,
                extraModelName: normalizedExtraModelName,
                value,
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
        // Then, then modify the data-model="post.title" field. In theory,
        // we should be smart enough to convert the post data - which is now
        // the string "4" - back into an array with [id=4, title=new_title].
        this.dataValue = setDeepData(this.dataValue, modelName, value);

        directive.modifiers.forEach((modifier => {
            switch (modifier.name) {
                // there are currently no data-model modifiers
                default:
                    throw new Error(`Unknown modifier ${modifier.name} used in data-model="${model}"`)
            }
        }));

        if (shouldRender) {
            // clear any pending renders
            this._clearWaitingDebouncedRenders();

            this.renderDebounceTimeout = window.setTimeout(() => {
                this.renderDebounceTimeout = null;
                this.$render();
            }, this.debounceValue || DEFAULT_DEBOUNCE);
        }
    }

    _makeRequest(action: string|null, args: Record<string,unknown>) {
        const splitUrl = this.urlValue.split('?');
        let [url] = splitUrl
        const [, queryString] = splitUrl;
        const params = new URLSearchParams(queryString || '');

        if (typeof args === 'object' && Object.keys(args).length > 0) {
            params.set('args', new URLSearchParams(args).toString());
        }

        const fetchOptions: RequestInit = {};
        fetchOptions.headers = {
            'Accept': 'application/vnd.live-component+html',
        };

        if (action) {
            url += `/${encodeURIComponent(action)}`;

            if (this.csrfValue) {
                fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfValue;
            }
        }

        let dataAdded = false;
        if (!action) {
            const dataJson = JSON.stringify(this.dataValue);
            if (this._willDataFitInUrl(dataJson, params)) {
                params.set('data', dataJson);
                fetchOptions.method = 'GET';
                dataAdded = true;
            }
        }

        // if GET can't be used, fallback to POST
        if (!dataAdded) {
            fetchOptions.method = 'POST';
            fetchOptions.body = JSON.stringify(this.dataValue);
            fetchOptions.headers['Content-Type'] = 'application/json';
        }

        this._onLoadingStart();
        const paramsString = params.toString();
        const thisPromise = fetch(`${url}${paramsString.length > 0 ? `?${paramsString}` : ''}`, fetchOptions);
        this.renderPromiseStack.addPromise(thisPromise);
        thisPromise.then((response) => {
            // if another re-render is scheduled, do not "run it over"
            if (this.renderDebounceTimeout) {
                return;
            }

            const isMostRecent = this.renderPromiseStack.removePromise(thisPromise);
            if (isMostRecent) {
                response.text().then((html) => {
                    this._processRerender(html, response);
                });
            }
        })
    }

    /**
     * Processes the response from an AJAX call and uses it to re-render.
     *
     * @private
     */
    _processRerender(html: string, response: Response) {
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

        if (!this._dispatchEvent('live:render', html, true, true)) {
            // preventDefault() was called
            return;
        }

        // remove the loading behavior now so that when we morphdom
        // "diffs" the elements, any loading differences will not cause
        // elements to appear different unnecessarily
        this._onLoadingFinish();

        // merge/patch in the new HTML
        this._executeMorphdom(html);
    }

    _clearWaitingDebouncedRenders() {
        if (this.renderDebounceTimeout) {
            clearTimeout(this.renderDebounceTimeout);
            this.renderDebounceTimeout = null;
        }
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
        if(!isHandled) {
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

    _executeMorphdom(newHtml: string) {
        // https://stackoverflow.com/questions/494143/creating-a-new-dom-element-from-an-html-string-using-built-in-dom-methods-or-pro#answer-35385518
        function htmlToElement(html: string): Node {
            const template = document.createElement('template');
            html = html.trim();
            template.innerHTML = html;

            const child = template.content.firstChild;
            if (!child) {
                throw new Error('Child not found');
            }

            return child;
        }

        const newElement = htmlToElement(newHtml);
        morphdom(this.element, newElement, {
            onBeforeElUpdated: (fromEl, toEl) => {
                if (!(fromEl instanceof HTMLElement) || !(toEl instanceof HTMLElement)) {
                    return false;
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
            }
        });
        // restore the data-original-data attribute
        this._exposeOriginalData();
    }

    markAsWindowUnloaded = () => {
        this.isWindowUnloaded = true;
    };

    _initiatePolling(rawPollConfig: string) {
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
                this._makeRequest(actionName);
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
            detail: payload,
        }));
    }

    _handleChildComponentUpdateModel(event: any) {
        const mainModelName = event.detail.modelName;
        const potentialModelNames = [
            { name: mainModelName, required: false },
            { name: event.detail.extraModelName, required: false },
        ]

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

            if (doesDeepPropertyExist(this.dataValue, potentialModel.name)) {
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
     * Determines of a child live element should be re-rendered.
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
}

/**
 * Tracks the current "re-render" promises.
 */
class PromiseStack {
    stack: Promise<any>[] = [];

    addPromise(promise: Promise<any>) {
        this.stack.push(promise);
    }

    /**
     * Removes the promise AND returns `true` if it is the most recent.
     */
    removePromise(promise: Promise<any>): boolean {
        const index = this.findPromiseIndex(promise);

        // promise was not found - it was removed because a new Promise
        // already resolved before it
        if (index === -1) {
            return false;
        }

        // "save" whether this is the most recent or not
        const isMostRecent = this.stack.length === (index + 1);

        // remove all promises starting from the oldest up through this one
        this.stack.splice(0, index + 1);

        return isMostRecent;
    }

    findPromiseIndex(promise: Promise<any>) {
        return this.stack.findIndex((item) => item === promise);
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
