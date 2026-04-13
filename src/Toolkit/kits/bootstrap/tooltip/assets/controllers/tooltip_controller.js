import { Controller } from '@hotwired/stimulus';
import { Tooltip } from 'bootstrap';

export default class extends Controller {
    connect() {
        this._tooltip = new Tooltip(this.element);
    }

    disconnect() {
        this._tooltip?.dispose();
    }
}
