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

var _default_1_instances, _default_1_getCommonConfig, _default_1_createAutocomplete, _default_1_createAutocompleteWithHtmlContents, _default_1_createAutocompleteWithRemoteData, _default_1_stripTags, _default_1_mergeObjects, _default_1_createTomSelect;
class default_1 extends Controller {
    constructor() {
        super(...arguments);
        _default_1_instances.add(this);
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
            this.tomSelect = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createAutocompleteWithRemoteData).call(this, this.urlValue, this.minCharactersValue);
            return;
        }
        if (this.optionsAsHtmlValue) {
            this.tomSelect = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createAutocompleteWithHtmlContents).call(this);
            return;
        }
        this.tomSelect = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createAutocomplete).call(this);
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
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'autocomplete' });
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
_default_1_instances = new WeakSet(), _default_1_getCommonConfig = function _default_1_getCommonConfig() {
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
    return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, config, this.tomSelectOptionsValue);
}, _default_1_createAutocomplete = function _default_1_createAutocomplete() {
    const config = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_getCommonConfig).call(this), {
        maxOptions: this.selectElement ? this.selectElement.options.length : 50,
    });
    return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createTomSelect).call(this, config);
}, _default_1_createAutocompleteWithHtmlContents = function _default_1_createAutocompleteWithHtmlContents() {
    const config = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_getCommonConfig).call(this), {
        maxOptions: this.selectElement ? this.selectElement.options.length : 50,
        score: (search) => {
            const scoringFunction = this.tomSelect.getScoreFunction(search);
            return (item) => {
                return scoringFunction(Object.assign(Object.assign({}, item), { text: __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_stripTags).call(this, item.text) }));
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
    return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createTomSelect).call(this, config);
}, _default_1_createAutocompleteWithRemoteData = function _default_1_createAutocompleteWithRemoteData(autocompleteEndpointUrl, minCharacterLength) {
    const config = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_getCommonConfig).call(this), {
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
    return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createTomSelect).call(this, config);
}, _default_1_stripTags = function _default_1_stripTags(string) {
    return string.replace(/(<([^>]+)>)/gi, '');
}, _default_1_mergeObjects = function _default_1_mergeObjects(object1, object2) {
    return Object.assign(Object.assign({}, object1), object2);
}, _default_1_createTomSelect = function _default_1_createTomSelect(options) {
    this.dispatchEvent('pre-connect', { options });
    const tomSelect = new TomSelect(this.formElement, options);
    this.dispatchEvent('connect', { tomSelect, options });
    return tomSelect;
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
