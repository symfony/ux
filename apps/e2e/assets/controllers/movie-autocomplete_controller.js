import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    initialize() {
        this._onPreConnect = this._onPreConnect.bind(this);
        this._onConnect = this._onConnect.bind(this);
    }

    connect() {
        this.element.addEventListener('autocomplete:pre-connect', this._onPreConnect);
        this.element.addEventListener('autocomplete:connect', this._onConnect);
    }

    disconnect() {
        this.element.removeEventListener('autocomplete:pre-connect', this._onPreConnect);
        this.element.removeEventListener('autocomplete:connect', this._onConnect);
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

        tomSelect.on('item_add', async (value, item) => {
            const component = await getComponent(this.element.closest('[data-controller~="live"]'));
            if (!this.element.isConnected) {
                return;
            }
            const title = item.getAttribute('data-title') || item.textContent;
            component.emit('movie-selected', { title });
        });
    }
}
