import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";
function _checkPrivateRedeclaration(e, t) {
	if (t.has(e)) throw new TypeError("Cannot initialize the same private elements twice on an object");
}
function _classPrivateMethodInitSpec(e, a) {
	_checkPrivateRedeclaration(e, a), a.add(e);
}
function _classPrivateFieldInitSpec(e, t, a) {
	_checkPrivateRedeclaration(e, t), t.set(e, a);
}
function _assertClassBrand(e, t, n) {
	if ("function" == typeof e ? e === t : e.has(t)) return arguments.length < 3 ? t : n;
	throw new TypeError("Private element is not present on this object");
}
function _classPrivateFieldGet2(s, a) {
	return s.get(_assertClassBrand(s, a));
}
var _Class_brand = /* @__PURE__ */ new WeakSet();
var _normalizePluginsToHash = /* @__PURE__ */ new WeakMap();
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		_classPrivateMethodInitSpec(this, _Class_brand);
		this.isObserving = false;
		this.hasLoadedChoicesPreviously = false;
		this.originalOptions = [];
		_classPrivateFieldInitSpec(this, _normalizePluginsToHash, (plugins) => {
			if (Array.isArray(plugins)) return plugins.reduce((acc, plugin) => {
				if (typeof plugin === "string") acc[plugin] = {};
				if (typeof plugin === "object" && plugin.name) acc[plugin.name] = plugin.options || {};
				return acc;
			}, {});
			return plugins;
		});
	}
	initialize() {
		if (!this.mutationObserver) this.mutationObserver = new MutationObserver((mutations) => {
			this.onMutations(mutations);
		});
	}
	connect() {
		if (this.selectElement) this.originalOptions = this.createOptionsDataStructure(this.selectElement);
		this.initializeTomSelect();
	}
	initializeTomSelect() {
		if (this.selectElement) this.selectElement.setAttribute("data-skip-morph", "");
		if (this.urlValue) {
			this.tomSelect = _assertClassBrand(_Class_brand, this, _createAutocompleteWithRemoteData).call(this, this.urlValue, this.hasMinCharactersValue ? this.minCharactersValue : null);
			return;
		}
		if (this.optionsAsHtmlValue) {
			this.tomSelect = _assertClassBrand(_Class_brand, this, _createAutocompleteWithHtmlContents).call(this);
			return;
		}
		this.tomSelect = _assertClassBrand(_Class_brand, this, _createAutocomplete).call(this);
		this.startMutationObserver();
	}
	disconnect() {
		this.stopMutationObserver();
		if (!this.tomSelect) return;
		let currentSelectedValues = [];
		if (this.selectElement) if (this.selectElement.multiple) currentSelectedValues = Array.from(this.selectElement.options).filter((option) => option.selected).map((option) => option.value);
		else currentSelectedValues = [this.selectElement.value];
		this.tomSelect.destroy();
		this.tomSelect = void 0;
		if (this.selectElement) if (this.selectElement.multiple) Array.from(this.selectElement.options).forEach((option) => {
			option.selected = currentSelectedValues.includes(option.value);
		});
		else this.selectElement.value = currentSelectedValues[0];
	}
	urlValueChanged() {
		this.resetTomSelect();
	}
	getMaxOptions() {
		return this.selectElement ? this.selectElement.options.length : 50;
	}
	get selectElement() {
		if (!(this.element instanceof HTMLSelectElement)) return null;
		return this.element;
	}
	get formElement() {
		if (!(this.element instanceof HTMLInputElement) && !(this.element instanceof HTMLSelectElement)) throw new Error("Autocomplete Stimulus controller can only be used on an <input> or <select>.");
		return this.element;
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "autocomplete"
		});
	}
	get preload() {
		if (!this.hasPreloadValue) return "focus";
		if (this.preloadValue === "false") return false;
		if (this.preloadValue === "true") return true;
		return this.preloadValue;
	}
	resetTomSelect() {
		if (this.tomSelect) {
			this.dispatchEvent("before-reset", { tomSelect: this.tomSelect });
			this.stopMutationObserver();
			const currentHtml = this.element.innerHTML;
			const currentValue = this.tomSelect.getValue();
			this.tomSelect.destroy();
			this.element.innerHTML = currentHtml;
			this.initializeTomSelect();
			this.tomSelect.setValue(currentValue);
		}
	}
	changeTomSelectDisabledState(isDisabled) {
		if (!this.tomSelect) return;
		this.stopMutationObserver();
		if (isDisabled) this.tomSelect.disable();
		else this.tomSelect.enable();
		this.startMutationObserver();
	}
	startMutationObserver() {
		if (!this.isObserving && this.mutationObserver) {
			this.mutationObserver.observe(this.element, {
				childList: true,
				subtree: true,
				attributes: true,
				characterData: true,
				attributeOldValue: true
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
				case "attributes":
					if (mutation.target === this.element && mutation.attributeName === "disabled") {
						changeDisabledState = true;
						break;
					}
					if (mutation.target === this.element && mutation.attributeName === "multiple") {
						if (this.element.hasAttribute("multiple") !== (mutation.oldValue === "multiple")) requireReset = true;
						break;
					}
					break;
			}
		});
		const newOptions = this.selectElement ? this.createOptionsDataStructure(this.selectElement) : [];
		if (!this.areOptionsEquivalent(newOptions) || requireReset) {
			this.originalOptions = newOptions;
			this.resetTomSelect();
		}
		if (changeDisabledState) this.changeTomSelectDisabledState(this.formElement.disabled);
	}
	createOptionsDataStructure(selectElement) {
		return Array.from(selectElement.options).map((option) => {
			return {
				value: option.value,
				text: option.text
			};
		});
	}
	areOptionsEquivalent(newOptions) {
		const filteredOriginalOptions = this.originalOptions.filter((option) => option.value !== "");
		const filteredNewOptions = newOptions.filter((option) => option.value !== "");
		const originalPlaceholderOption = this.originalOptions.find((option) => option.value === "");
		const newPlaceholderOption = newOptions.find((option) => option.value === "");
		if (originalPlaceholderOption && newPlaceholderOption && originalPlaceholderOption.text !== newPlaceholderOption.text) return false;
		if (filteredOriginalOptions.length !== filteredNewOptions.length) return false;
		const normalizeOption = (option) => `${option.value}-${option.text}`;
		const originalOptionsSet = new Set(filteredOriginalOptions.map(normalizeOption));
		const newOptionsSet = new Set(filteredNewOptions.map(normalizeOption));
		return originalOptionsSet.size === newOptionsSet.size && [...originalOptionsSet].every((option) => newOptionsSet.has(option));
	}
};
function _getCommonConfig() {
	const plugins = {};
	const isMultiple = !this.selectElement || this.selectElement.multiple;
	if (!this.formElement.disabled && !isMultiple) plugins.clear_button = { title: "" };
	if (isMultiple) plugins.remove_button = { title: "" };
	if (this.urlValue) plugins.virtual_scroll = {};
	const config = {
		render: {
			no_results: () => {
				return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
			},
			option_create: (data, escapeData) => {
				return `<div class="create">${this.createOptionTextValue.replace("%placeholder%", `<strong>${escapeData(data.input)}</strong>`)}</div>`;
			}
		},
		plugins,
		onItemAdd: () => {
			this.tomSelect?.setTextboxValue("");
		},
		closeAfterSelect: true,
		onOptionAdd: (value, data) => {
			if (!this.tomSelect) return;
			let parentElement = this.tomSelect.input;
			let optgroupData = null;
			const optgroup = data[this.tomSelect.settings.optgroupField];
			if (optgroup && this.tomSelect.optgroups) {
				optgroupData = this.tomSelect.optgroups[optgroup];
				if (optgroupData) {
					const optgroupElement = parentElement.querySelector(`optgroup[label="${optgroupData.label}"]`);
					if (optgroupElement) parentElement = optgroupElement;
				}
			}
			const optionElement = document.createElement("option");
			optionElement.value = value;
			optionElement.text = data[this.tomSelect.settings.labelField];
			const optionOrder = data.$order;
			let orderedOption = null;
			for (const [, tomSelectOption] of Object.entries(this.tomSelect.options)) if (tomSelectOption.$order === optionOrder) {
				orderedOption = parentElement.querySelector(`:scope > option[value="${CSS.escape(tomSelectOption[this.tomSelect.settings.valueField])}"]`);
				break;
			}
			if (orderedOption) orderedOption.insertAdjacentElement("afterend", optionElement);
			else if (optionOrder >= 0) parentElement.append(optionElement);
			else parentElement.prepend(optionElement);
		}
	};
	if (!this.selectElement && !this.urlValue) config.shouldLoad = () => false;
	return _assertClassBrand(_Class_brand, this, _mergeConfigs).call(this, config, this.tomSelectOptionsValue);
}
function _createAutocomplete() {
	const config = _assertClassBrand(_Class_brand, this, _mergeConfigs).call(this, _assertClassBrand(_Class_brand, this, _getCommonConfig).call(this), { maxOptions: this.getMaxOptions() });
	return _assertClassBrand(_Class_brand, this, _createTomSelect).call(this, config);
}
function _createAutocompleteWithHtmlContents() {
	const commonConfig = _assertClassBrand(_Class_brand, this, _getCommonConfig).call(this);
	const labelField = commonConfig.labelField ?? "text";
	const config = _assertClassBrand(_Class_brand, this, _mergeConfigs).call(this, commonConfig, {
		maxOptions: this.getMaxOptions(),
		score: (search) => {
			const scoringFunction = this.tomSelect?.getScoreFunction(search);
			return (item) => {
				return scoringFunction?.({
					...item,
					text: _assertClassBrand(_Class_brand, this, _stripTags).call(this, item[labelField])
				});
			};
		},
		render: {
			...commonConfig.render,
			item: (item) => `<div>${item[labelField]}</div>`,
			option: (item) => `<div>${item[labelField]}</div>`
		}
	});
	return _assertClassBrand(_Class_brand, this, _createTomSelect).call(this, config);
}
function _createAutocompleteWithRemoteData(autocompleteEndpointUrl, minCharacterLength) {
	const commonConfig = _assertClassBrand(_Class_brand, this, _getCommonConfig).call(this);
	const labelField = commonConfig.labelField ?? "text";
	const config = _assertClassBrand(_Class_brand, this, _mergeConfigs).call(this, commonConfig, {
		firstUrl: (query) => {
			return `${autocompleteEndpointUrl}${autocompleteEndpointUrl.includes("?") ? "&" : "?"}query=${encodeURIComponent(query)}`;
		},
		load: function(query, callback) {
			const url = this.getUrl(query);
			fetch(url).then((response) => response.json()).then((json) => {
				this.setNextUrl(query, json.next_page);
				callback(json.results.options || json.results, json.results.optgroups || []);
			}).catch(() => callback([], []));
		},
		shouldLoad: (query) => {
			if (null !== minCharacterLength) return query.length >= minCharacterLength;
			if (this.hasLoadedChoicesPreviously) return true;
			if (query.length > 0) this.hasLoadedChoicesPreviously = true;
			return query.length >= 3;
		},
		optgroupField: "group_by",
		score: (_search) => (_item) => 1,
		render: {
			option: (item, escape) => `<div>${this.optionsAsHtmlValue ? item[labelField] : escape(item[labelField])}</div>`,
			item: (item, escape) => `<div>${this.optionsAsHtmlValue ? item[labelField] : escape(item[labelField])}</div>`,
			loading_more: () => {
				return `<div class="loading-more-results">${this.loadingMoreTextValue}</div>`;
			},
			no_more_results: () => {
				return `<div class="no-more-results">${this.noMoreResultsTextValue}</div>`;
			},
			no_results: () => {
				return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
			},
			option_create: (data, escapeData) => {
				return `<div class="create">${this.createOptionTextValue.replace("%placeholder%", `<strong>${escapeData(data.input)}</strong>`)}</div>`;
			}
		},
		onFocus: () => {
			if (this.resetOnFocusValue && this.tomSelect) {
				if (this.tomSelect.control_input.value.trim() === "") {
					this.tomSelect.clearOptions();
					this.tomSelect.loadedSearches = {};
					if (typeof this.tomSelect["clearPagination"] === "function") this.tomSelect["clearPagination"]();
					this.tomSelect.load("");
				}
			}
		},
		preload: this.preload
	});
	return _assertClassBrand(_Class_brand, this, _createTomSelect).call(this, config);
}
function _stripTags(string) {
	return string.replace(/(<([^>]+)>)/gi, "");
}
function _mergeConfigs(config1, config2) {
	return {
		...config1,
		...config2,
		plugins: _assertClassBrand(_Class_brand, this, _normalizePlugins).call(this, {
			..._classPrivateFieldGet2(_normalizePluginsToHash, this).call(this, config1.plugins || {}),
			..._classPrivateFieldGet2(_normalizePluginsToHash, this).call(this, config2.plugins || {})
		})
	};
}
function _normalizePlugins(plugins) {
	return Object.entries(plugins).reduce((acc, [pluginName, pluginOptions]) => {
		if (pluginOptions !== false) acc[pluginName] = pluginOptions;
		return acc;
	}, {});
}
function _createTomSelect(options) {
	const preConnectPayload = { options };
	this.dispatchEvent("pre-connect", preConnectPayload);
	const tomSelect = new TomSelect(this.formElement, options);
	const connectPayload = {
		tomSelect,
		options
	};
	this.dispatchEvent("connect", connectPayload);
	return tomSelect;
}
_Class.values = {
	url: String,
	optionsAsHtml: Boolean,
	loadingMoreText: String,
	noResultsFoundText: String,
	noMoreResultsText: String,
	createOptionText: String,
	minCharacters: Number,
	tomSelectOptions: Object,
	preload: String,
	resetOnFocus: Boolean
};
export { _Class as default };
