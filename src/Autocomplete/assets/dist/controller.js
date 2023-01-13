import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/******************************************************************************
Copyright (c) Microsoft Corporation.

Permission to use, copy, modify, and/or distribute this software for any
purpose with or without fee is hereby granted.

THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES WITH
REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF MERCHANTABILITY
AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY SPECIAL, DIRECT,
INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES WHATSOEVER RESULTING FROM
LOSS OF USE, DATA OR PROFITS, WHETHER IN AN ACTION OF CONTRACT, NEGLIGENCE OR
OTHER TORTIOUS ACTION, ARISING OUT OF OR IN CONNECTION WITH THE USE OR
PERFORMANCE OF THIS SOFTWARE.
***************************************************************************** */

function __classPrivateFieldGet(receiver, state, kind, f) {
    if (kind === "a" && !f) throw new TypeError("Private accessor was defined without a getter");
    if (typeof state === "function" ? receiver !== state || !f : !state.has(receiver)) throw new TypeError("Cannot read private member from an object whose class did not declare it");
    return kind === "m" ? f : kind === "a" ? f.call(receiver) : f ? f.value : state.get(receiver);
}

var _instances, _getCommonConfig, _createAutocomplete, _createAutocompleteWithHtmlContents, _createAutocompleteWithRemoteData, _stripTags, _mergeObjects, _createTomSelect, _dispatchEvent;
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        _instances.add(this);
    }
    initialize() {
        this.element.setAttribute('data-live-ignore', '');
        if (this.element.id) {
            const label = document.querySelector(`label[for="${this.element.id}"]`);
            if (label) {
                label.setAttribute('data-live-ignore', '');
            }
        }
    }
    connect() {
        if (this.urlValue) {
            this.tomSelect = __classPrivateFieldGet(this, _instances, "m", _createAutocompleteWithRemoteData).call(this, this.urlValue, this.minCharactersValue);
            return;
        }
        if (this.optionsAsHtmlValue) {
            this.tomSelect = __classPrivateFieldGet(this, _instances, "m", _createAutocompleteWithHtmlContents).call(this);
            return;
        }
        this.tomSelect = __classPrivateFieldGet(this, _instances, "m", _createAutocomplete).call(this);
    }
    disconnect() {
        this.tomSelect.revertSettings.innerHTML = this.element.innerHTML;
        this.tomSelect.destroy();
    }
    get selectElement() {
        if (!(this.element instanceof HTMLSelectElement)) {
            return null;
        }
        return this.element;
    }
    get formElement() {
        if (!(this.element instanceof HTMLInputElement) && !(this.element instanceof HTMLSelectElement)) {
            throw new Error('Autocomplete Stimulus controller can only be used on an <input> or <select>.');
        }
        return this.element;
    }
    get preload() {
        if (!this.hasPreloadValue) {
            return 'focus';
        }
        if (this.preloadValue == 'false') {
            return false;
        }
        if (this.preloadValue == 'true') {
            return true;
        }
        return this.preloadValue;
    }
}
_instances = new WeakSet(), _getCommonConfig = function _getCommonConfig() {
    const plugins = {};
    const isMultiple = !this.selectElement || this.selectElement.multiple;
    if (!this.formElement.disabled && !isMultiple) {
        plugins.clear_button = { title: '' };
    }
    if (isMultiple) {
        plugins.remove_button = { title: '' };
    }
    if (this.urlValue) {
        plugins.virtual_scroll = {};
    }
    const render = {
        no_results: () => {
            return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
        },
    };
    const config = {
        render,
        plugins,
        onItemAdd: () => {
            this.tomSelect.setTextboxValue('');
        },
        onInitialize: function () {
            const tomSelect = this;
            tomSelect.wrapper.setAttribute('data-live-ignore', '');
        },
        closeAfterSelect: true,
    };
    if (!this.selectElement && !this.urlValue) {
        config.shouldLoad = () => false;
    }
    return __classPrivateFieldGet(this, _instances, "m", _mergeObjects).call(this, config, this.tomSelectOptionsValue);
}, _createAutocomplete = function _createAutocomplete() {
    const config = __classPrivateFieldGet(this, _instances, "m", _mergeObjects).call(this, __classPrivateFieldGet(this, _instances, "m", _getCommonConfig).call(this), {
        maxOptions: this.selectElement ? this.selectElement.options.length : 50,
    });
    return __classPrivateFieldGet(this, _instances, "m", _createTomSelect).call(this, config);
}, _createAutocompleteWithHtmlContents = function _createAutocompleteWithHtmlContents() {
    const config = __classPrivateFieldGet(this, _instances, "m", _mergeObjects).call(this, __classPrivateFieldGet(this, _instances, "m", _getCommonConfig).call(this), {
        maxOptions: this.selectElement ? this.selectElement.options.length : 50,
        score: (search) => {
            const scoringFunction = this.tomSelect.getScoreFunction(search);
            return (item) => {
                return scoringFunction(Object.assign(Object.assign({}, item), { text: __classPrivateFieldGet(this, _instances, "m", _stripTags).call(this, item.text) }));
            };
        },
        render: {
            item: function (item) {
                return `<div>${item.text}</div>`;
            },
            option: function (item) {
                return `<div>${item.text}</div>`;
            },
        },
    });
    return __classPrivateFieldGet(this, _instances, "m", _createTomSelect).call(this, config);
}, _createAutocompleteWithRemoteData = function _createAutocompleteWithRemoteData(autocompleteEndpointUrl, minCharacterLength) {
    const config = __classPrivateFieldGet(this, _instances, "m", _mergeObjects).call(this, __classPrivateFieldGet(this, _instances, "m", _getCommonConfig).call(this), {
        firstUrl: (query) => {
            const separator = autocompleteEndpointUrl.includes('?') ? '&' : '?';
            return `${autocompleteEndpointUrl}${separator}query=${encodeURIComponent(query)}`;
        },
        load: function (query, callback) {
            const url = this.getUrl(query);
            fetch(url)
                .then((response) => response.json())
                .then((json) => {
                this.setNextUrl(query, json.next_page);
                callback(json.results);
            })
                .catch(() => callback());
        },
        shouldLoad: function (query) {
            return query.length >= minCharacterLength;
        },
        score: function (search) {
            return function (item) {
                return 1;
            };
        },
        render: {
            option: function (item) {
                return `<div>${item.text}</div>`;
            },
            item: function (item) {
                return `<div>${item.text}</div>`;
            },
            no_more_results: () => {
                return `<div class="no-more-results">${this.noMoreResultsTextValue}</div>`;
            },
            no_results: () => {
                return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
            },
        },
        preload: this.preload,
    });
    return __classPrivateFieldGet(this, _instances, "m", _createTomSelect).call(this, config);
}, _stripTags = function _stripTags(string) {
    return string.replace(/(<([^>]+)>)/gi, '');
}, _mergeObjects = function _mergeObjects(object1, object2) {
    return Object.assign(Object.assign({}, object1), object2);
}, _createTomSelect = function _createTomSelect(options) {
    __classPrivateFieldGet(this, _instances, "m", _dispatchEvent).call(this, 'autocomplete:pre-connect', { options });
    const tomSelect = new TomSelect(this.formElement, options);
    __classPrivateFieldGet(this, _instances, "m", _dispatchEvent).call(this, 'autocomplete:connect', { tomSelect, options });
    return tomSelect;
}, _dispatchEvent = function _dispatchEvent(name, payload) {
    this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
};
default_1.values = {
    url: String,
    optionsAsHtml: Boolean,
    noResultsFoundText: String,
    noMoreResultsText: String,
    minCharacters: { type: Number, default: 3 },
    tomSelectOptions: Object,
    preload: String,
};

export { default_1 as default };
