import { Controller } from '@hotwired/stimulus';
import DataTable from 'datatables.net-dt';

class default_1 extends Controller {
    constructor() {
        super(...arguments);
        this.table = null;
        this.isDataTableInitialized = false;
    }
    connect() {
        if (this.isDataTableInitialized) {
            return;
        }
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('Invalid element');
        }
        const payload = this.viewValue;
        this.dispatchEvent('pre-connect', {
            config: payload,
        });
        this.table = new DataTable(this.element, payload);
        this.dispatchEvent('connect', { table: this.table });
        this.isDataTableInitialized = true;
    }
    dispatchEvent(name, payload) {
        this.dispatch(name, { detail: payload, prefix: 'datatables' });
    }
}
default_1.values = {
    view: Object,
};

export { default_1 as default };
