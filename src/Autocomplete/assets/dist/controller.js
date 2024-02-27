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
        this.isObserving = false;
        this.hasLoadedChoicesPreviously = false;
        this.originalOptions = [];
    }
    initialize() {
        if (!this.mutationObserver) {
            this.mutationObserver = new MutationObserver((mutations) => {
                this.onMutations(mutations);
            });
        }
    }
    connect() {
        if (this.selectElement) {
            this.originalOptions = this.createOptionsDataStructure(this.selectElement);
        }
        this.initializeTomSelect();
    }
    initializeTomSelect() {
        if (this.selectElement) {
            this.selectElement.setAttribute('data-skip-morph', '');
        }
        if (this.urlValue) {
            this.tomSelect = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createAutocompleteWithRemoteData).call(this, this.urlValue, this.hasMinCharactersValue ? this.minCharactersValue : null);
            return;
        }
        if (this.optionsAsHtmlValue) {
            this.tomSelect = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createAutocompleteWithHtmlContents).call(this);
            return;
        }
        this.tomSelect = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createAutocomplete).call(this);
        this.startMutationObserver();
    }
    disconnect() {
        this.stopMutationObserver();
        let currentSelectedValues = [];
        if (this.selectElement) {
            if (this.selectElement.multiple) {
                currentSelectedValues = Array.from(this.selectElement.options)
                    .filter((option) => option.selected)
                    .map((option) => option.value);
            }
            else {
                currentSelectedValues = [this.selectElement.value];
            }
        }
        this.tomSelect.destroy();
        if (this.selectElement) {
            if (this.selectElement.multiple) {
                Array.from(this.selectElement.options).forEach((option) => {
                    option.selected = currentSelectedValues.includes(option.value);
                });
            }
            else {
                this.selectElement.value = currentSelectedValues[0];
            }
        }
    }
    getMaxOptions() {
        return this.selectElement ? this.selectElement.options.length : 50;
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
    resetTomSelect() {
        if (this.tomSelect) {
            this.dispatchEvent('before-reset', { tomSelect: this.tomSelect });
            this.stopMutationObserver();
            const currentHtml = this.element.innerHTML;
            const currentValue = this.tomSelect.getValue();
            this.tomSelect.destroy();
            this.element.innerHTML = currentHtml;
            this.initializeTomSelect();
            this.tomSelect.setValue(currentValue);
            this.startMutationObserver();
        }
    }
    changeTomSelectDisabledState(isDisabled) {
        this.stopMutationObserver();
        if (isDisabled) {
            this.tomSelect.disable();
        }
        else {
            this.tomSelect.enable();
        }
        this.startMutationObserver();
    }
    startMutationObserver() {
        if (!this.isObserving && this.mutationObserver) {
            this.mutationObserver.observe(this.element, {
                childList: true,
                subtree: true,
                attributes: true,
                characterData: true,
                attributeOldValue: true,
            });
            this.isObserving = true;
        }
    }
    stopMutationObserver() {
        if (this.isObserving && this.mutationObserver) {
            this.mutationObserver.disconnect();
            this.isObserving = false;
        }
    }
    onMutations(mutations) {
        let changeDisabledState = false;
        let requireReset = false;
        mutations.forEach((mutation) => {
            switch (mutation.type) {
                case 'attributes':
                    if (mutation.target === this.element && mutation.attributeName === 'disabled') {
                        changeDisabledState = true;
                        break;
                    }
                    if (mutation.target === this.element && mutation.attributeName === 'multiple') {
                        const isNowMultiple = this.element.hasAttribute('multiple');
                        const wasMultiple = mutation.oldValue === 'multiple';
                        if (isNowMultiple !== wasMultiple) {
                            requireReset = true;
                        }
                        break;
                    }
                    break;
            }
        });
        const newOptions = this.selectElement ? this.createOptionsDataStructure(this.selectElement) : [];
        const areOptionsEquivalent = this.areOptionsEquivalent(newOptions);
        if (!areOptionsEquivalent || requireReset) {
            this.originalOptions = newOptions;
            this.resetTomSelect();
        }
        if (changeDisabledState) {
            this.changeTomSelectDisabledState(this.formElement.disabled);
        }
    }
    createOptionsDataStructure(selectElement) {
        return Array.from(selectElement.options).map((option) => {
            const optgroup = option.closest('optgroup');
            return {
                value: option.value,
                text: option.text,
                group: optgroup ? optgroup.label : null,
            };
        });
    }
    areOptionsEquivalent(newOptions) {
        const filteredOriginalOptions = this.originalOptions.filter((option) => option.value !== '');
        const filteredNewOptions = newOptions.filter((option) => option.value !== '');
        const originalPlaceholderOption = this.originalOptions.find((option) => option.value === '');
        const newPlaceholderOption = newOptions.find((option) => option.value === '');
        if (originalPlaceholderOption &&
            newPlaceholderOption &&
            originalPlaceholderOption.text !== newPlaceholderOption.text) {
            return false;
        }
        if (filteredOriginalOptions.length !== filteredNewOptions.length) {
            return false;
        }
        const normalizeOption = (option) => `${option.value}-${option.text}-${option.group}`;
        const originalOptionsSet = new Set(filteredOriginalOptions.map(normalizeOption));
        const newOptionsSet = new Set(filteredNewOptions.map(normalizeOption));
        return (originalOptionsSet.size === newOptionsSet.size &&
            [...originalOptionsSet].every((option) => newOptionsSet.has(option)));
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
        closeAfterSelect: true,
    };
    if (!this.selectElement && !this.urlValue) {
        config.shouldLoad = () => false;
    }
    return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, config, this.tomSelectOptionsValue);
}, _default_1_createAutocomplete = function _default_1_createAutocomplete() {
    const config = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_getCommonConfig).call(this), {
        maxOptions: this.getMaxOptions(),
    });
    return __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_createTomSelect).call(this, config);
}, _default_1_createAutocompleteWithHtmlContents = function _default_1_createAutocompleteWithHtmlContents() {
    const config = __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_mergeObjects).call(this, __classPrivateFieldGet(this, _default_1_instances, "m", _default_1_getCommonConfig).call(this), {
        maxOptions: this.getMaxOptions(),
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
                callback(json.results.options || json.results, json.results.optgroups || []);
            })
                .catch(() => callback([], []));
        },
        shouldLoad: (query) => {
            if (null !== minCharacterLength) {
                return query.length >= minCharacterLength;
            }
            if (this.hasLoadedChoicesPreviously) {
                return true;
            }
            if (query.length > 0) {
                this.hasLoadedChoicesPreviously = true;
            }
            return query.length >= 3;
        },
        optgroupField: 'group_by',
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
            loading_more: () => {
                return `<div class="loading-more-results">${this.loadingMoreTextValue}</div>`;
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
    const preConnectPayload = { options };
    this.dispatchEvent('pre-connect', preConnectPayload);
    const tomSelect = new TomSelect(this.formElement, options);
    const connectPayload = { tomSelect, options };
    this.dispatchEvent('connect', connectPayload);
    return tomSelect;
};
default_1.values = {
    url: String,
    optionsAsHtml: Boolean,
    loadingMoreText: String,
    noResultsFoundText: String,
    noMoreResultsText: String,
    minCharacters: Number,
    tomSelectOptions: Object,
    preload: String,
};

export { default_1 as default };
