'use strict';
import { Controller } from '@hotwired/stimulus';
import 'bootstrap-table';
import $ from 'jquery';
export default class default_1 extends Controller {
    connect() {
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('invalid Element');
        }
        this._dispatchEvent('bootstrap-table:pre-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });
        $(this.element).bootstrapTable(Object.assign({ columns: this.columnsValue, data: this.rowsValue }, this.optionsValue));
        $(this).on('page-change.bs.table', (event) => {
            console.log(event);
        });
        this._dispatchEvent('bootstrap-table:post-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
default_1.values = {
    rows: Array,
    columns: Array,
    options: {},
};
