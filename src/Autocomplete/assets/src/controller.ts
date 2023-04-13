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
        noResultsFoundText: String,
        noMoreResultsText: String,
        minCharacters: { type: Number, default: 3 },
        tomSelectOptions: Object,
        preload: String,
    };

    declare readonly urlValue: string;
    declare readonly optionsAsHtmlValue: boolean;
    declare readonly noMoreResultsTextValue: string;
    declare readonly noResultsFoundTextValue: string;
    declare readonly minCharactersValue: number;
    declare readonly tomSelectOptionsValue: object;
    declare readonly hasPreloadValue: boolean;
    declare readonly preloadValue: string;
    tomSelect: TomSelect;

    private mutationObserver: MutationObserver;
    private isObserving = false;

    initialize() {
        if (this.requiresLiveIgnore()) {
            // unfortunately, TomSelect does enough weird things that, for a
            // multi select, if the HTML in the `<select>` element changes,
            // we can't reliably update TomSelect to see those changes. So,
            // as a workaround, we tell LiveComponents to entirely ignore trying
            // to update this item
            this.element.setAttribute('data-live-ignore', '');
            if (this.element.id) {
                const label = document.querySelector(`label[for="${this.element.id}"]`);
                if (label) {
                    label.setAttribute('data-live-ignore', '');
                }
            }
        } else {
            // for non-multiple selects, we use a MutationObserver to update
            // the TomSelect instance if the options themselves change
            if (!this.mutationObserver) {
                this.mutationObserver = new MutationObserver((mutations: MutationRecord[]) => {
                    this.onMutations(mutations);
                });
            }
        }
    }

    connect() {
        if (this.urlValue) {
            this.tomSelect = this.#createAutocompleteWithRemoteData(this.urlValue, this.minCharactersValue);

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
        this.tomSelect.destroy();
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

        const requiresLiveIgnore = this.requiresLiveIgnore();

        const config: RecursivePartial<TomSettings> = {
            render,
            plugins,
            // clear the text input after selecting a value
            onItemAdd: () => {
                this.tomSelect.setTextboxValue('');
            },
            // see initialize() method for explanation
            onInitialize: function () {
                if (requiresLiveIgnore) {
                    const tomSelect = this as any;
                    tomSelect.wrapper.setAttribute('data-live-ignore', '');
                }
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

    #createAutocompleteWithRemoteData(autocompleteEndpointUrl: string, minCharacterLength: number): TomSelect {
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
            shouldLoad: function (query: string) {
                return query.length >= minCharacterLength;
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
            this.stopMutationObserver();
            this.tomSelect.clearOptions();
            this.tomSelect.settings.maxOptions = this.getMaxOptions();
            this.tomSelect.sync();
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

    /**
     * TomSelect doesn't give us a way to update the placeholder, so most of
     * this code is copied from TomSelect's source code.
     *
     * @private
     */
    private updateTomSelectPlaceholder(): void {
        const input = this.element;
        let placeholder = input.getAttribute('placeholder') || input.getAttribute('data-placeholder');
        if (!placeholder && !this.tomSelect.allowEmptyOption) {
            const option = input.querySelector('option[value=""]');

            if (option) {
                placeholder = option.textContent;
            }
        }

        if (placeholder) {
            this.stopMutationObserver();
            // override settings so it's used again later
            this.tomSelect.settings.placeholder = placeholder;
            // and set it right now
            this.tomSelect.control_input.setAttribute('placeholder', placeholder);
            this.startMutationObserver();
        }
    }

    private startMutationObserver(): void {
        if (!this.isObserving && this.mutationObserver) {
            this.mutationObserver.observe(this.element, {
                childList: true,
                subtree: true,
                attributes: true,
                characterData: true,
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
        const addedOptionElements: HTMLOptionElement[] = [];
        const removedOptionElements: HTMLOptionElement[] = [];
        let hasAnOptionChanged = false;
        let changeDisabledState = false;
        let changePlaceholder = false;

        mutations.forEach((mutation) => {
            switch (mutation.type) {
                case 'childList':
                    // look for changes to any <option> elements - e.g. text
                    if (mutation.target instanceof HTMLOptionElement) {
                        if (mutation.target.value === '') {
                            changePlaceholder = true;

                            break;
                        }

                        hasAnOptionChanged = true;
                        break;
                    }

                    // look for new or removed <option> elements
                    mutation.addedNodes.forEach((node) => {
                        if (node instanceof HTMLOptionElement) {
                            // check if a previously-removed is being added back
                            if (removedOptionElements.includes(node)) {
                                removedOptionElements.splice(removedOptionElements.indexOf(node), 1);
                                return;
                            }

                            addedOptionElements.push(node);
                        }
                    });
                    mutation.removedNodes.forEach((node) => {
                        if (node instanceof HTMLOptionElement) {
                            // check if a previously-added is being removed
                            if (addedOptionElements.includes(node)) {
                                addedOptionElements.splice(addedOptionElements.indexOf(node), 1);
                                return;
                            }

                            removedOptionElements.push(node);
                        }
                    });
                    break;
                case 'attributes':
                    // look for changes to any <option> elements (e.g. value attribute)
                    if (mutation.target instanceof HTMLOptionElement) {
                        hasAnOptionChanged = true;
                        break;
                    }

                    if (mutation.target === this.element && mutation.attributeName === 'disabled') {
                        changeDisabledState = true;

                        break;
                    }

                    break;
                case 'characterData':
                    // an alternative way for an option's text to change
                    if (mutation.target instanceof Text && mutation.target.parentElement instanceof HTMLOptionElement) {
                        if (mutation.target.parentElement.value === '') {
                            changePlaceholder = true;

                            break;
                        }

                        hasAnOptionChanged = true;
                    }
            }
        });

        if (hasAnOptionChanged || addedOptionElements.length > 0 || removedOptionElements.length > 0) {
            this.resetTomSelect();
        }

        if (changeDisabledState) {
            this.changeTomSelectDisabledState(this.formElement.disabled);
        }

        if (changePlaceholder) {
            this.updateTomSelectPlaceholder();
        }
    }

    private requiresLiveIgnore(): boolean {
        return this.element instanceof HTMLSelectElement && this.element.multiple;
    }
}
