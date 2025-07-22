import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";

//#region src/controller.ts
var controller_default = class extends Controller {
	static values = {
		url: String,
		optionsAsHtml: Boolean,
		loadingMoreText: String,
		noResultsFoundText: String,
		noMoreResultsText: String,
		createOptionText: String,
		minCharacters: Number,
		tomSelectOptions: Object,
		preload: String
	};
	tomSelect;
	mutationObserver;
	isObserving = false;
	hasLoadedChoicesPreviously = false;
	originalOptions = [];
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
			this.tomSelect = this.#createAutocompleteWithRemoteData(this.urlValue, this.hasMinCharactersValue ? this.minCharactersValue : null);
			return;
		}
		if (this.optionsAsHtmlValue) {
			this.tomSelect = this.#createAutocompleteWithHtmlContents();
			return;
		}
		this.tomSelect = this.#createAutocomplete();
		this.startMutationObserver();
	}
	disconnect() {
		this.stopMutationObserver();
		let currentSelectedValues = [];
		if (this.selectElement) if (this.selectElement.multiple) currentSelectedValues = Array.from(this.selectElement.options).filter((option) => option.selected).map((option) => option.value);
		else currentSelectedValues = [this.selectElement.value];
		this.tomSelect.destroy();
		if (this.selectElement) if (this.selectElement.multiple) Array.from(this.selectElement.options).forEach((option) => {
			option.selected = currentSelectedValues.includes(option.value);
		});
		else this.selectElement.value = currentSelectedValues[0];
	}
	urlValueChanged() {
		this.resetTomSelect();
	}
	#getCommonConfig() {
		const plugins = {};
		const isMultiple = !this.selectElement || this.selectElement.multiple;
		if (!this.formElement.disabled && !isMultiple) plugins.clear_button = { title: "" };
		if (isMultiple) plugins.remove_button = { title: "" };
		if (this.urlValue) plugins.virtual_scroll = {};
		const render = {
			no_results: () => {
				return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
			},
			option_create: (data, escapeData) => {
				return `<div class="create">${this.createOptionTextValue.replace("%placeholder%", `<strong>${escapeData(data.input)}</strong>`)}</div>`;
			}
		};
		const config = {
			render,
			plugins,
			onItemAdd: () => {
				this.tomSelect.setTextboxValue("");
			},
			closeAfterSelect: true,
			onOptionAdd: (value, data) => {
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
		return this.#mergeConfigs(config, this.tomSelectOptionsValue);
	}
	#createAutocomplete() {
		const config = this.#mergeConfigs(this.#getCommonConfig(), { maxOptions: this.getMaxOptions() });
		return this.#createTomSelect(config);
	}
	#createAutocompleteWithHtmlContents() {
		const commonConfig = this.#getCommonConfig();
		const labelField = commonConfig.labelField ?? "text";
		const config = this.#mergeConfigs(commonConfig, {
			maxOptions: this.getMaxOptions(),
			score: (search) => {
				const scoringFunction = this.tomSelect.getScoreFunction(search);
				return (item) => {
					return scoringFunction({
						...item,
						text: this.#stripTags(item[labelField])
					});
				};
			},
			render: {
				item: (item) => `<div>${item[labelField]}</div>`,
				option: (item) => `<div>${item[labelField]}</div>`
			}
		});
		return this.#createTomSelect(config);
	}
	#createAutocompleteWithRemoteData(autocompleteEndpointUrl, minCharacterLength) {
		const commonConfig = this.#getCommonConfig();
		const labelField = commonConfig.labelField ?? "text";
		const config = this.#mergeConfigs(commonConfig, {
			firstUrl: (query) => {
				const separator = autocompleteEndpointUrl.includes("?") ? "&" : "?";
				return `${autocompleteEndpointUrl}${separator}query=${encodeURIComponent(query)}`;
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
			score: (search) => (item) => 1,
			render: {
				option: (item) => `<div>${item[labelField]}</div>`,
				item: (item) => `<div>${item[labelField]}</div>`,
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
			preload: this.preload
		});
		return this.#createTomSelect(config);
	}
	getMaxOptions() {
		return this.selectElement ? this.selectElement.options.length : 50;
	}
	#stripTags(string) {
		return string.replace(/(<([^>]+)>)/gi, "");
	}
	#mergeConfigs(config1, config2) {
		return {
			...config1,
			...config2,
			plugins: {
				...this.#normalizePluginsToHash(config1.plugins || {}),
				...this.#normalizePluginsToHash(config2.plugins || {})
			}
		};
	}
	/**
	* Normalizes the plugins to a hash, so that we can merge them easily.
	*/
	#normalizePluginsToHash = (plugins) => {
		if (Array.isArray(plugins)) return plugins.reduce((acc, plugin) => {
			if (typeof plugin === "string") acc[plugin] = {};
			if (typeof plugin === "object" && plugin.name) acc[plugin.name] = plugin.options || {};
			return acc;
		}, {});
		return plugins;
	};
	/**
	* Returns the element, but only if it's a select element.
	*/
	get selectElement() {
		if (!(this.element instanceof HTMLSelectElement)) return null;
		return this.element;
	}
	/**
	* Getter to help typing.
	*/
	get formElement() {
		if (!(this.element instanceof HTMLInputElement) && !(this.element instanceof HTMLSelectElement)) throw new Error("Autocomplete Stimulus controller can only be used on an <input> or <select>.");
		return this.element;
	}
	#createTomSelect(options) {
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
						const isNowMultiple = this.element.hasAttribute("multiple");
						const wasMultiple = mutation.oldValue === "multiple";
						if (isNowMultiple !== wasMultiple) requireReset = true;
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

//#endregion
export { controller_default as default };