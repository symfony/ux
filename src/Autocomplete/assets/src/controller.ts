import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';
import { TPluginHash } from 'tom-select/dist/types/contrib/microplugin';
import { RecursivePartial, TomSettings, TomTemplates } from 'tom-select/dist/types/types';

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
            this.tomSelect = this.#createAutocompleteWithRemoteData(this.urlValue, this.minCharactersValue);

            return;
        }

        if (this.optionsAsHtmlValue) {
            this.tomSelect = this.#createAutocompleteWithHtmlContents();

            return;
        }

        this.tomSelect = this.#createAutocomplete();
    }

    disconnect() {
        // make sure it will "revert" to the latest innerHTML
        this.tomSelect.revertSettings.innerHTML = this.element.innerHTML;
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

        const config: RecursivePartial<TomSettings> = {
            render,
            plugins,
            // clear the text input after selecting a value
            onItemAdd: () => {
                this.tomSelect.setTextboxValue('');
            },
            onInitialize: function () {
                const tomSelect = this as any;
                tomSelect.wrapper.setAttribute('data-live-ignore', '');
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
            maxOptions: this.selectElement ? this.selectElement.options.length : 50,
        });

        return this.#createTomSelect(config);
    }

    #createAutocompleteWithHtmlContents(): TomSelect {
        const config = this.#mergeObjects(this.#getCommonConfig(), {
            maxOptions: this.selectElement ? this.selectElement.options.length : 50,
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
            load: function (query: string, callback: (results?: any) => void) {
                const url = this.getUrl(query);
                fetch(url)
                    .then((response) => response.json())
                    // important: next_url must be set before invoking callback()
                    .then((json) => {
                        this.setNextUrl(query, json.next_page);
                        callback(json.results);
                    })
                    .catch(() => callback());
            },
            shouldLoad: function (query: string) {
                return query.length >= minCharacterLength;
            },
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
        this.#dispatchEvent('autocomplete:pre-connect', { options });
        const tomSelect = new TomSelect(this.formElement, options);
        this.#dispatchEvent('autocomplete:connect', { tomSelect, options });

        return tomSelect;
    }

    #dispatchEvent(name: string, payload: any): void {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
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
