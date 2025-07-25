var __typeError = (msg) => {
  throw TypeError(msg);
};
var __accessCheck = (obj, member, msg) => member.has(obj) || __typeError("Cannot " + msg);
var __privateGet = (obj, member, getter) => (__accessCheck(obj, member, "read from private field"), getter ? getter.call(obj) : member.get(obj));
var __privateAdd = (obj, member, value) => member.has(obj) ? __typeError("Cannot add the same private member more than once") : member instanceof WeakSet ? member.add(obj) : member.set(obj, value);
var __privateMethod = (obj, member, method) => (__accessCheck(obj, member, "access private method"), method);

// src/controller.ts
import { Controller } from "@hotwired/stimulus";
import TomSelect from "tom-select";
var _instances, getCommonConfig_fn, createAutocomplete_fn, createAutocompleteWithHtmlContents_fn, createAutocompleteWithRemoteData_fn, stripTags_fn, mergeConfigs_fn, _normalizePluginsToHash, normalizePlugins_fn, createTomSelect_fn;
var controller_default = class extends Controller {
  constructor() {
    super(...arguments);
    __privateAdd(this, _instances);
    this.isObserving = false;
    this.hasLoadedChoicesPreviously = false;
    this.originalOptions = [];
    /**
     * Normalizes the plugins to a hash, so that we can merge them easily.
     */
    __privateAdd(this, _normalizePluginsToHash, (plugins) => {
      if (Array.isArray(plugins)) {
        return plugins.reduce((acc, plugin) => {
          if (typeof plugin === "string") {
            acc[plugin] = {};
          }
          if (typeof plugin === "object" && plugin.name) {
            acc[plugin.name] = plugin.options || {};
          }
          return acc;
        }, {});
      }
      return plugins;
    });
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
      this.selectElement.setAttribute("data-skip-morph", "");
    }
    if (this.urlValue) {
      this.tomSelect = __privateMethod(this, _instances, createAutocompleteWithRemoteData_fn).call(this, this.urlValue, this.hasMinCharactersValue ? this.minCharactersValue : null);
      return;
    }
    if (this.optionsAsHtmlValue) {
      this.tomSelect = __privateMethod(this, _instances, createAutocompleteWithHtmlContents_fn).call(this);
      return;
    }
    this.tomSelect = __privateMethod(this, _instances, createAutocomplete_fn).call(this);
    this.startMutationObserver();
  }
  disconnect() {
    this.stopMutationObserver();
    let currentSelectedValues = [];
    if (this.selectElement) {
      if (this.selectElement.multiple) {
        currentSelectedValues = Array.from(this.selectElement.options).filter((option) => option.selected).map((option) => option.value);
      } else {
        currentSelectedValues = [this.selectElement.value];
      }
    }
    this.tomSelect.destroy();
    if (this.selectElement) {
      if (this.selectElement.multiple) {
        Array.from(this.selectElement.options).forEach((option) => {
          option.selected = currentSelectedValues.includes(option.value);
        });
      } else {
        this.selectElement.value = currentSelectedValues[0];
      }
    }
  }
  urlValueChanged() {
    this.resetTomSelect();
  }
  getMaxOptions() {
    return this.selectElement ? this.selectElement.options.length : 50;
  }
  /**
   * Returns the element, but only if it's a select element.
   */
  get selectElement() {
    if (!(this.element instanceof HTMLSelectElement)) {
      return null;
    }
    return this.element;
  }
  /**
   * Getter to help typing.
   */
  get formElement() {
    if (!(this.element instanceof HTMLInputElement) && !(this.element instanceof HTMLSelectElement)) {
      throw new Error("Autocomplete Stimulus controller can only be used on an <input> or <select>.");
    }
    return this.element;
  }
  dispatchEvent(name, payload) {
    this.dispatch(name, { detail: payload, prefix: "autocomplete" });
  }
  get preload() {
    if (!this.hasPreloadValue) {
      return "focus";
    }
    if (this.preloadValue === "false") {
      return false;
    }
    if (this.preloadValue === "true") {
      return true;
    }
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
    if (isDisabled) {
      this.tomSelect.disable();
    } else {
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
    if (originalPlaceholderOption && newPlaceholderOption && originalPlaceholderOption.text !== newPlaceholderOption.text) {
      return false;
    }
    if (filteredOriginalOptions.length !== filteredNewOptions.length) {
      return false;
    }
    const normalizeOption = (option) => `${option.value}-${option.text}`;
    const originalOptionsSet = new Set(filteredOriginalOptions.map(normalizeOption));
    const newOptionsSet = new Set(filteredNewOptions.map(normalizeOption));
    return originalOptionsSet.size === newOptionsSet.size && [...originalOptionsSet].every((option) => newOptionsSet.has(option));
  }
};
_instances = new WeakSet();
getCommonConfig_fn = function() {
  const plugins = {};
  const isMultiple = !this.selectElement || this.selectElement.multiple;
  if (!this.formElement.disabled && !isMultiple) {
    plugins.clear_button = { title: "" };
  }
  if (isMultiple) {
    plugins.remove_button = { title: "" };
  }
  if (this.urlValue) {
    plugins.virtual_scroll = {};
  }
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
    // clear the text input after selecting a value
    onItemAdd: () => {
      this.tomSelect.setTextboxValue("");
    },
    closeAfterSelect: true,
    // fix positioning (in the dropdown) of options added through addOption()
    onOptionAdd: (value, data) => {
      let parentElement = this.tomSelect.input;
      let optgroupData = null;
      const optgroup = data[this.tomSelect.settings.optgroupField];
      if (optgroup && this.tomSelect.optgroups) {
        optgroupData = this.tomSelect.optgroups[optgroup];
        if (optgroupData) {
          const optgroupElement = parentElement.querySelector(`optgroup[label="${optgroupData.label}"]`);
          if (optgroupElement) {
            parentElement = optgroupElement;
          }
        }
      }
      const optionElement = document.createElement("option");
      optionElement.value = value;
      optionElement.text = data[this.tomSelect.settings.labelField];
      const optionOrder = data.$order;
      let orderedOption = null;
      for (const [, tomSelectOption] of Object.entries(this.tomSelect.options)) {
        if (tomSelectOption.$order === optionOrder) {
          orderedOption = parentElement.querySelector(
            `:scope > option[value="${CSS.escape(tomSelectOption[this.tomSelect.settings.valueField])}"]`
          );
          break;
        }
      }
      if (orderedOption) {
        orderedOption.insertAdjacentElement("afterend", optionElement);
      } else if (optionOrder >= 0) {
        parentElement.append(optionElement);
      } else {
        parentElement.prepend(optionElement);
      }
    }
  };
  if (!this.selectElement && !this.urlValue) {
    config.shouldLoad = () => false;
  }
  return __privateMethod(this, _instances, mergeConfigs_fn).call(this, config, this.tomSelectOptionsValue);
};
createAutocomplete_fn = function() {
  const config = __privateMethod(this, _instances, mergeConfigs_fn).call(this, __privateMethod(this, _instances, getCommonConfig_fn).call(this), {
    maxOptions: this.getMaxOptions()
  });
  return __privateMethod(this, _instances, createTomSelect_fn).call(this, config);
};
createAutocompleteWithHtmlContents_fn = function() {
  const commonConfig = __privateMethod(this, _instances, getCommonConfig_fn).call(this);
  const labelField = commonConfig.labelField ?? "text";
  const config = __privateMethod(this, _instances, mergeConfigs_fn).call(this, commonConfig, {
    maxOptions: this.getMaxOptions(),
    score: (search) => {
      const scoringFunction = this.tomSelect.getScoreFunction(search);
      return (item) => {
        return scoringFunction({ ...item, text: __privateMethod(this, _instances, stripTags_fn).call(this, item[labelField]) });
      };
    },
    render: {
      item: (item) => `<div>${item[labelField]}</div>`,
      option: (item) => `<div>${item[labelField]}</div>`
    }
  });
  return __privateMethod(this, _instances, createTomSelect_fn).call(this, config);
};
createAutocompleteWithRemoteData_fn = function(autocompleteEndpointUrl, minCharacterLength) {
  const commonConfig = __privateMethod(this, _instances, getCommonConfig_fn).call(this);
  const labelField = commonConfig.labelField ?? "text";
  const config = __privateMethod(this, _instances, mergeConfigs_fn).call(this, commonConfig, {
    firstUrl: (query) => {
      const separator = autocompleteEndpointUrl.includes("?") ? "&" : "?";
      return `${autocompleteEndpointUrl}${separator}query=${encodeURIComponent(query)}`;
    },
    // VERY IMPORTANT: use 'function (query, callback) { ... }' instead of the
    // '(query, callback) => { ... }' syntax because, otherwise,
    // the 'this.XXX' calls inside this method fail
    load: function(query, callback) {
      const url = this.getUrl(query);
      fetch(url).then((response) => response.json()).then((json) => {
        this.setNextUrl(query, json.next_page);
        callback(json.results.options || json.results, json.results.optgroups || []);
      }).catch(() => callback([], []));
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
    optgroupField: "group_by",
    // avoid extra filtering after results are returned
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
  return __privateMethod(this, _instances, createTomSelect_fn).call(this, config);
};
stripTags_fn = function(string) {
  return string.replace(/(<([^>]+)>)/gi, "");
};
mergeConfigs_fn = function(config1, config2) {
  return {
    ...config1,
    ...config2,
    // Plugins from both configs should be merged together.
    plugins: __privateMethod(this, _instances, normalizePlugins_fn).call(this, {
      ...__privateGet(this, _normalizePluginsToHash).call(this, config1.plugins || {}),
      ...__privateGet(this, _normalizePluginsToHash).call(this, config2.plugins || {})
    })
  };
};
_normalizePluginsToHash = new WeakMap();
normalizePlugins_fn = function(plugins) {
  return Object.entries(plugins).reduce((acc, [pluginName, pluginOptions]) => {
    if (pluginOptions !== false) {
      acc[pluginName] = pluginOptions;
    }
    return acc;
  }, {});
};
createTomSelect_fn = function(options) {
  const preConnectPayload = { options };
  this.dispatchEvent("pre-connect", preConnectPayload);
  const tomSelect = new TomSelect(this.formElement, options);
  const connectPayload = { tomSelect, options };
  this.dispatchEvent("connect", connectPayload);
  return tomSelect;
};
controller_default.values = {
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
export {
  controller_default as default
};
