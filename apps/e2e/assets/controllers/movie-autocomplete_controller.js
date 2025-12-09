import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    async connect() {
        this.component = await getComponent(this.element.closest('[data-controller*="live"]'));

        this.element.addEventListener('autocomplete:pre-connect', this._onPreConnect.bind(this));
        this.element.addEventListener('autocomplete:connect', this._onConnect.bind(this));
    }

    disconnect() {
        this.element.removeEventListener('autocomplete:pre-connect', this._onPreConnect.bind(this));
        this.element.removeEventListener('autocomplete:connect', this._onConnect.bind(this));
    }

    _onPreConnect(event) {
        const options = event.detail.options;
        options.render = {
            ...options.render,
            option: (item) => {
                return `<div data-test-id="autocomplete-option" data-title="${item.title || item.text}">${item.text}</div>`;
            },
        };
    }

    _onConnect(event) {
        const tomSelect = event.detail.tomSelect;

        tomSelect.on('item_add', (value, item) => {
            const title = item.getAttribute('data-title') || item.textContent;
            this.component.emit('movie-selected', { title });
        });
    }
}
