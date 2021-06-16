import { Controller } from 'stimulus';
import morphdom from 'morphdom';
import { parseDirectives } from './directives_parser';
import { combineSpacedArray } from './string_utils';
import { buildFormData, buildSearchParams } from './http_data_helper';
import { setDeepData, doesDeepPropertyExist, normalizeModelName } from './set_deep_data';
import './polyfills';

const DEFAULT_DEBOUNCE = '150';

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

    /**
     * The current "timeout" that's waiting before a model update
     * triggers a re-render.
     */
    renderDebounceTimeout = null;

    /**
     * The current "timeout" that's waiting before an action should
     * be taken.
     */
    actionDebounceTimeout = null;

    /**
     * A stack of all current AJAX Promises for re-rendering.
     *
     * @type {PromiseStack}
     */
    renderPromiseStack = new PromiseStack();

    pollingIntervals = [];

    isWindowUnloaded = false;

    initialize() {
        this.markAsWindowUnloaded = this.markAsWindowUnloaded.bind(this);
    }

    connect() {
        // hide "loading" elements to begin with
        // This is done with CSS, but only for the most basic cases
        this._onLoadingFinish();

        if (this.element.dataset.poll !== undefined) {
            this._initiatePolling(this.element.dataset.poll);
        }

        window.addEventListener('beforeunload', this.markAsWindowUnloaded);

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
    update(event) {
        const value = event.target.value;

        this._updateModelFromElement(event.target, value, true);
    }

    updateDefer(event) {
        const value = event.target.value;

        this._updateModelFromElement(event.target, value, false);
    }

    action(event) {
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

                this._makeRequest(directive.action);
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
                        const length = modifier.value ? modifier.value : DEFAULT_DEBOUNCE;

                        // clear any pending renders
                        if (this.actionDebounceTimeout) {
                            clearTimeout(this.actionDebounceTimeout);
                            this.actionDebounceTimeout = null;
                        }

                        this.actionDebounceTimeout = setTimeout(() => {
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

    _updateModelFromElement(element, value, shouldRender) {
        const model = element.dataset.model || element.getAttribute('name');

        if (!model) {
            const clonedElement = element.cloneNode();
            clonedElement.innerHTML = '';

            throw new Error(`The update() method could not be called for "${clonedElement.outerHTML}": the element must either have a "data-model" or "name" attribute set to the model name.`);
        }

        this.$updateModel(model, value, element, shouldRender);
    }

    $updateModel(model, value, element, shouldRender = true) {
        const directives = parseDirectives(model);
        if (directives.length > 1) {
            throw new Error(`The data-model="${model}" format is invalid: it does not support multiple directives (i.e. remove any spaces).`);
        }

        const directive = directives[0];

        if (directive.args.length > 0 || directive.named.length > 0) {
            throw new Error(`The data-model="${model}" format is invalid: it does not support passing arguments to the model.`);
        }

        const modelName = normalizeModelName(directive.action);

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

        if (!doesDeepPropertyExist(this.dataValue, modelName)) {
            console.warn(`Model "${modelName}" is not a valid data-model value`);
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

            this.renderDebounceTimeout = setTimeout(() => {
                this.renderDebounceTimeout = null;
                this.$render();
            }, this.debounceValue || DEFAULT_DEBOUNCE);
        }
    }

    _makeRequest(action) {
        let [url, queryString] = this.urlValue.split('?');
        const params = new URLSearchParams(queryString || '');

        const fetchOptions = {
            headers: {
                'Accept': 'application/vnd.live-component+json',
            },
        };

        if (action) {
            url += `/${encodeURIComponent(action)}`;

            if (this.csrfValue) {
                fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfValue;
            }
        }

        if (!action && this._willDataFitInUrl()) {
            buildSearchParams(params, this.dataValue);
            fetchOptions.method = 'GET';
        } else {
            fetchOptions.method = 'POST';
            fetchOptions.body = buildFormData(this.dataValue);
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
                response.json().then((data) => {
                    this._processRerender(data)
                });
            }
        })
    }

    /**
     * Processes the response from an AJAX call and uses it to re-render.
     *
     * @private
     */
    _processRerender(data) {
        // check if the page is navigating away
        if (this.isWindowUnloaded) {
            return;
        }

        if (data.redirect_url) {
            // action returned a redirect
            /* global Turbo */
            if (typeof Turbo !== 'undefined') {
                Turbo.visit(data.redirect_url);
            } else {
                window.location = data.redirect_url;
            }

            return;
        }

        if (!this._dispatchEvent('live:render', data, true, true)) {
            // preventDefault() was called
            return;
        }

        // remove the loading behavior now so that when we morphdom
        // "diffs" the elements, any loading differences will not cause
        // elements to appear different unnecessarily
        this._onLoadingFinish();

        // merge/patch in the new HTML
        this._executeMorphdom(data.html);

        // "data" holds the new, updated data
        this.dataValue = data.data;
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

    _handleLoadingToggle(isLoading) {
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
     * @param {Element} element
     * @param {boolean} isLoading
     * @param {Directive} directive
     * @private
     */
    _handleLoadingDirective(element, isLoading, directive) {
        const finalAction = parseLoadingAction(directive.action, isLoading);

        let loadingDirective = null;

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

                    const delayLength = modifier.value || 200;
                    setTimeout(() => {
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
        const loadingDirectives = [];

        this.element.querySelectorAll('[data-loading]').forEach((element => {
            // use "show" if the attribute is empty
            const directives = parseDirectives(element.dataset.loading || 'show');

            loadingDirectives.push({
                element,
                directives,
            });
        }));

        return loadingDirectives;
    }

    _showElement(element) {
        element.style.display = 'inline-block';
    }

    _hideElement(element) {
        element.style.display = 'none';
    }

    _addClass(element, classes) {
        element.classList.add(...combineSpacedArray(classes));
    }

    _removeClass(element, classes) {
        element.classList.remove(...combineSpacedArray(classes));

        // remove empty class="" to avoid morphdom "diff" problem
        if (element.classList.length === 0) {
            this._removeAttributes(element, ['class']);
        }
    }

    _addAttributes(element, attributes) {
        attributes.forEach((attribute) => {
            element.setAttribute(attribute, '');
        })
    }

    _removeAttributes(element, attributes) {
        attributes.forEach((attribute) => {
            element.removeAttribute(attribute);
        })
    }

    _willDataFitInUrl() {
        // if the URL gets remotely close to 2000 chars, it may not fit
        return Object.values(this.dataValue).join(',').length < 1500;
    }

    _executeMorphdom(newHtml) {
        // https://stackoverflow.com/questions/494143/creating-a-new-dom-element-from-an-html-string-using-built-in-dom-methods-or-pro#answer-35385518
        function htmlToElement(html) {
            const template = document.createElement('template');
            html = html.trim();
            template.innerHTML = html;

            return template.content.firstChild;
        }

        const newElement = htmlToElement(newHtml);
        morphdom(this.element, newElement, {
            onBeforeElUpdated: (fromEl, toEl) => {
                // https://github.com/patrick-steele-idem/morphdom#can-i-make-morphdom-blaze-through-the-dom-tree-even-faster-yes
                if (fromEl.isEqualNode(toEl)) {
                    return false
                }

                // avoid updating child components: they will handle themselves
                if (fromEl.hasAttribute('data-controller')
                    && fromEl.getAttribute('data-controller').split(' ').indexOf('live') !== -1
                    && fromEl !== this.element
                ) {
                    return false;
                }

                return true;
            }
        });
    }

    markAsWindowUnloaded = () => {
        this.isWindowUnloaded = true;
    };

    _initiatePolling(rawPollConfig) {
        const directives = parseDirectives(rawPollConfig || '$render');

        directives.forEach((directive) => {
            let duration = 2000;

            directive.modifiers.forEach((modifier) => {
                switch (modifier.name) {
                    case 'delay':
                        if (modifier.value) {
                            duration = modifier.value;
                        }

                         break;
                    default:
                        console.warn(`Unknown modifier "${modifier.name}" in data-poll "${rawPollConfig}".`);
                }
            });

            this.startPoll(directive.action, duration);
        })
    }

    startPoll(actionName, duration) {
        let callback;
        if (actionName.charAt(0) === '$') {
            callback = () => {
                this[actionName]();
            }
        } else {
            callback = () => {
                this._makeRequest(actionName);
            }
        }

        this.pollingIntervals.push(setInterval(() => {
            callback();
        }, duration));
    }

    _dispatchEvent(name, payload = null, canBubble = true, cancelable = false) {
        const userEvent = new CustomEvent(name, {
            bubbles: canBubble,
            cancelable,
            detail: payload,
        });

        return this.element.dispatchEvent(userEvent);
    }
}

/**
 * Tracks the current "re-render" promises.
 */
class PromiseStack {
    stack = [];

    addPromise(promise) {
        this.stack.push(promise);
    }

    /**
     * Removes the promise AND returns `true` if it is the most recent.
     *
     * @param {Promise} promise
     * @return {boolean}
     */
    removePromise(promise) {
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

    findPromiseIndex(promise) {
        return this.stack.findIndex((item) => item === promise);
    }
}

const parseLoadingAction = function(action, isLoading) {
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
