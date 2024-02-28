import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';
import { TPluginHash } from 'tom-select/dist/types/contrib/microplugin';
import { RecursivePartial, TomSettings, TomTemplates, TomLoadCallback } from 'tom-select/dist/types/types';

export interface AutocompletePreConnectOptions {
    options: any;
}
export interface AutocompleteConnectOptions {
    tomSelect: TomSelect;
    options: any;
}

export default class extends Controller {
    static values = {
        url: String,
        optionsAsHtml: Boolean,
        loadingMoreText: String,
        noResultsFoundText: String,
        noMoreResultsText: String,
        minCharacters: Number,
        tomSelectOptions: Object,
        preload: String,
    };

    declare readonly urlValue: string;
    declare readonly optionsAsHtmlValue: boolean;
    declare readonly loadingMoreTextValue: string;
    declare readonly noMoreResultsTextValue: string;
    declare readonly noResultsFoundTextValue: string;
    declare readonly minCharactersValue: number;
    declare readonly hasMinCharactersValue: boolean;
    declare readonly tomSelectOptionsValue: object;
    declare readonly hasPreloadValue: boolean;
    declare readonly preloadValue: string;
    tomSelect: TomSelect;

    private mutationObserver: MutationObserver;
    private isObserving = false;
    private hasLoadedChoicesPreviously = false;
    private originalOptions: Array<{ value: string; text: string; group: string | null }> = [];

    initialize() {
        if (!this.mutationObserver) {
            this.mutationObserver = new MutationObserver((mutations: MutationRecord[]) => {
                this.onMutations(mutations);
            });
        }
    }

    connect() {
        if (this.selectElement) {
            this.originalOptions = this.createOptionsDataStructure(this.selectElement);
        }
        // TODO - also listen on `live:before-morph-element`
        const parentElement = this.element.parentElement;
        if (!parentElement) {
            return;
        }
        parentElement.addEventListener('turbo:before-morph-element', this.beforeMorphElement.bind(this));
        parentElement.addEventListener('turbo:before-morph-attribute', this.beforeMorphAttribute.bind(this));

        this.initializeTomSelect();
    }

    initializeTomSelect() {
        // live components support: morphing the options causes issues, due
        // to the fact that TomSelect reorders the options when you select them
        if (this.selectElement) {
            this.selectElement.setAttribute('data-skip-morph', '');
        }

        if (this.urlValue) {
            this.tomSelect = this.#createAutocompleteWithRemoteData(
                this.urlValue,
                this.hasMinCharactersValue ? this.minCharactersValue : null
            );

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

        const parentElement = this.element.parentElement;
        if (!parentElement) {
            return;
        }
        parentElement.removeEventListener('turbo:before-morph-element', this.beforeMorphElement.bind(this));
        parentElement.removeEventListener('turbo:before-morph-attribute', this.beforeMorphAttribute.bind(this));

        // TomSelect.destroy() resets the element to its original HTML. This
        // causes the selected value to be lost. We store it.
        let currentSelectedValues: string[] = [];
        if (this.selectElement) {
            if (this.selectElement.multiple) {
                // For multiple selects, store the array of selected values
                currentSelectedValues = Array.from(this.selectElement.options)
                    .filter((option) => option.selected)
                    .map((option) => option.value);
            } else {
                // For single select, store the single value
                currentSelectedValues = [this.selectElement.value];
            }
        }

        this.tomSelect.destroy();

        if (this.selectElement) {
            if (this.selectElement.multiple) {
                // Restore selections for multiple selects
                Array.from(this.selectElement.options).forEach((option) => {
                    option.selected = currentSelectedValues.includes(option.value);
                });
            } else {
                // Restore selection for single select
                this.selectElement.value = currentSelectedValues[0];
            }
        }
    }

    #getCommonConfig(): Partial<TomSettings> {
        const plugins: TPluginHash = {};

        // multiple values excepted if this is NOT A select (i.e. input) or a multiple select
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

        const render: Partial<TomTemplates> = {
            no_results: () => {
                return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
            },
        };

        const config: RecursivePartial<TomSettings> = {
            render,
            plugins,
            // clear the text input after selecting a value
            onItemAdd: () => {
                this.tomSelect.setTextboxValue('');
            },
            closeAfterSelect: true,
        };

        // for non-autocompleting input elements, avoid the "No results" message that always shows
        if (!this.selectElement && !this.urlValue) {
            config.shouldLoad = () => false;
        }

        return this.#mergeObjects(config, this.tomSelectOptionsValue);
    }

    #createAutocomplete(): TomSelect {
        const config = this.#mergeObjects(this.#getCommonConfig(), {
            maxOptions: this.getMaxOptions(),
        });

        return this.#createTomSelect(config);
    }

    #createAutocompleteWithHtmlContents(): TomSelect {
        const config = this.#mergeObjects(this.#getCommonConfig(), {
            maxOptions: this.getMaxOptions(),
            score: (search: string) => {
                const scoringFunction = this.tomSelect.getScoreFunction(search);
                return (item: any) => {
                    // strip HTML tags from each option's searchable text
                    return scoringFunction({ ...item, text: this.#stripTags(item.text) });
                };
            },
            render: {
                item: function (item: any) {
                    return `<div>${item.text}</div>`;
                },
                option: function (item: any) {
                    return `<div>${item.text}</div>`;
                },
            },
        });

        return this.#createTomSelect(config);
    }

    #createAutocompleteWithRemoteData(autocompleteEndpointUrl: string, minCharacterLength: number | null): TomSelect {
        const config: RecursivePartial<TomSettings> = this.#mergeObjects(this.#getCommonConfig(), {
            firstUrl: (query: string) => {
                const separator = autocompleteEndpointUrl.includes('?') ? '&' : '?';

                return `${autocompleteEndpointUrl}${separator}query=${encodeURIComponent(query)}`;
            },
            // VERY IMPORTANT: use 'function (query, callback) { ... }' instead of the
            // '(query, callback) => { ... }' syntax because, otherwise,
            // the 'this.XXX' calls inside this method fail
            load: function (query: string, callback: TomLoadCallback) {
                const url = this.getUrl(query);
                fetch(url)
                    .then((response) => response.json())
                    // important: next_url must be set before invoking callback()
                    .then((json) => {
                        this.setNextUrl(query, json.next_page);
                        callback(json.results.options || json.results, json.results.optgroups || []);
                    })
                    .catch(() => callback([], []));
            },
            shouldLoad: (query: string) => {
                // if min length is specified, always use it
                if (null !== minCharacterLength) {
                    return query.length >= minCharacterLength;
                }

                // otherwise, default to 3, but always load after the first request
                // this gives nice behavior when the user deletes characters and
                // goes below the minimum length, it will still load fresh choices

                if (this.hasLoadedChoicesPreviously) {
                    return true;
                }

                // mark that the choices have loaded (but avoid initial load)
                if (query.length > 0) {
                    this.hasLoadedChoicesPreviously = true;
                }

                return query.length >= 3;
            },
            optgroupField: 'group_by',
            // avoid extra filtering after results are returned
            score: function (search: string) {
                return function (item: any) {
                    return 1;
                };
            },
            render: {
                option: function (item: any) {
                    return `<div>${item.text}</div>`;
                },
                item: function (item: any) {
                    return `<div>${item.text}</div>`;
                },
                loading_more: (): string => {
                    return `<div class="loading-more-results">${this.loadingMoreTextValue}</div>`;
                },
                no_more_results: (): string => {
                    return `<div class="no-more-results">${this.noMoreResultsTextValue}</div>`;
                },
                no_results: (): string => {
                    return `<div class="no-results">${this.noResultsFoundTextValue}</div>`;
                },
            },
            preload: this.preload,
        });

        return this.#createTomSelect(config);
    }

    private getMaxOptions(): number {
        return this.selectElement ? this.selectElement.options.length : 50;
    }

    #stripTags(string: string): string {
        return string.replace(/(<([^>]+)>)/gi, '');
    }

    #mergeObjects(object1: any, object2: any): any {
        return { ...object1, ...object2 };
    }

    /**
     * Returns the element, but only if it's a select element.
     */
    get selectElement(): HTMLSelectElement | null {
        if (!(this.element instanceof HTMLSelectElement)) {
            return null;
        }

        return this.element;
    }

    /**
     * Getter to help typing.
     */
    get formElement(): HTMLInputElement | HTMLSelectElement {
        if (!(this.element instanceof HTMLInputElement) && !(this.element instanceof HTMLSelectElement)) {
            throw new Error('Autocomplete Stimulus controller can only be used on an <input> or <select>.');
        }

        return this.element;
    }

    #createTomSelect(options: RecursivePartial<TomSettings>): TomSelect {
        const preConnectPayload: AutocompletePreConnectOptions = { options };
        this.dispatchEvent('pre-connect', preConnectPayload);
        const tomSelect = new TomSelect(this.formElement, options);
        const connectPayload: AutocompleteConnectOptions = { tomSelect, options };
        this.dispatchEvent('connect', connectPayload);

        return tomSelect;
    }

    private dispatchEvent(name: string, payload: any): void {
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

    private resetTomSelect(): void {
        if (this.tomSelect) {
            this.dispatchEvent('before-reset', { tomSelect: this.tomSelect });
            this.stopMutationObserver();

            // Grab the current HTML then restore it after destroying TomSelect
            // This is needed because TomSelect's destroy revert the element to
            // its original HTML.
            const currentHtml = this.element.innerHTML;
            const currentValue: any = this.tomSelect.getValue();
            this.tomSelect.destroy();
            this.element.innerHTML = currentHtml;
            this.initializeTomSelect();
            this.tomSelect.setValue(currentValue);

            this.startMutationObserver();
        }
    }

    private changeTomSelectDisabledState(isDisabled: boolean): void {
        this.stopMutationObserver();
        if (isDisabled) {
            this.tomSelect.disable();
        } else {
            this.tomSelect.enable();
        }
        this.startMutationObserver();
    }

    private startMutationObserver(): void {
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

    private stopMutationObserver(): void {
        if (this.isObserving && this.mutationObserver) {
            this.mutationObserver.disconnect();
            this.isObserving = false;
        }
    }

    private onMutations(mutations: MutationRecord[]): void {
        let requireReset = false;

        if (this.tomSelect.isDisabled !== this.formElement.disabled) {
            this.changeTomSelectDisabledState(this.formElement.disabled);
        }

        mutations.forEach((mutation) => {
            switch (mutation.type) {
                case 'attributes':
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

        // TODO: test this: look for changes in the "value" of the select element
        const value = this.selectElement ? Array.from(this.selectElement.options || []).map((option) => option.value) : this.formElement.value;
        const didValueChange = value !== this.tomSelect.getValue();

        if (!areOptionsEquivalent || requireReset || didValueChange) {
            this.originalOptions = newOptions;
            this.resetTomSelect();
        }
    }

    private createOptionsDataStructure(
        selectElement: HTMLSelectElement
    ): Array<{ value: string; text: string; group: string | null }> {
        return Array.from(selectElement.options).map((option) => {
            const optgroup = option.closest('optgroup');
            return {
                value: option.value,
                text: option.text,
                group: optgroup ? optgroup.label : null,
            };
        });
    }

    private areOptionsEquivalent(newOptions: Array<{ value: string; text: string; group: string | null }>): boolean {
        // remove the empty option, which is added by TomSelect so may be missing from new options
        const filteredOriginalOptions = this.originalOptions.filter((option) => option.value !== '');
        const filteredNewOptions = newOptions.filter((option) => option.value !== '');

        const originalPlaceholderOption = this.originalOptions.find((option) => option.value === '');
        const newPlaceholderOption = newOptions.find((option) => option.value === '');

        if (
            originalPlaceholderOption &&
            newPlaceholderOption &&
            originalPlaceholderOption.text !== newPlaceholderOption.text
        ) {
            return false;
        }

        if (filteredOriginalOptions.length !== filteredNewOptions.length) {
            return false;
        }

        const normalizeOption = (option: { value: string; text: string; group: string | null }) =>
            `${option.value}-${option.text}-${option.group}`;
        const originalOptionsSet = new Set(filteredOriginalOptions.map(normalizeOption));
        const newOptionsSet = new Set(filteredNewOptions.map(normalizeOption));

        return (
            originalOptionsSet.size === newOptionsSet.size &&
            [...originalOptionsSet].every((option) => newOptionsSet.has(option))
        );
    }

    private beforeMorphElement(event: any) {
        // TomSelect adds this element to the DOM. Keep it.
        if (event.target.classList.contains('ts-wrapper')) {
            event.preventDefault();

            return;
        }

        if (event.target === this.element) {
            const newOptions = this.selectElement ? this.createOptionsDataStructure(this.selectElement) : [];
            if (this.areOptionsEquivalent(newOptions)) {
                // prevent the <option> elements from being mutated, as this will
                // change their order, which befuddles TomSelect (who changes the
                // order of the <option> elements when you select them)
                // this will, unfortunately, prevent any changes attributed on
                // the select element from being updated.
                event.preventDefault();

                return;
            }
        }
    }

    private beforeMorphAttribute(event: any) {
        if (event.target.tagName === 'LABEL') {
            console.log('before morph attribute', event.detail);
        }
        // TomSelect changes the label "for". Keep that change.
        if (event.target.tagName === 'LABEL' && ['for', 'id'].includes(event.detail.attributeName)) {
            event.preventDefault();

            return;
        }

        // TomSelect changes the root element's class. Keep that change.
        if (event.target === this.element && event.detail.attributeName === 'class') {
            event.preventDefault();

            return;
        }
    }
}
