import { Controller } from '@hotwired/stimulus';
import { DataTable } from 'simple-datatables';

class default_1 extends Controller {
    connect() {
        var _a, _b;
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('invalid Element, please provide a HTMLTableElement');
        }
        this._dispatchEvent('datatable:pre-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });
        new DataTable(this.element, Object.assign({ data: this._buildTableData(this.rowsValue) }, this.optionsValue));
        if ((_a = this.optionsValue) === null || _a === void 0 ? void 0 : _a.withPluginCss) {
            this._addCssLink('https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css');
        }
        if ((_b = this.optionsValue) === null || _b === void 0 ? void 0 : _b.withCss) {
            this._addCssLink(this.optionsValue.withCss);
        }
        this._dispatchEvent('datatable:post-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });
    }
    _buildTableData(rowsValue) {
        const tableData = {
            headings: Object.keys(rowsValue[0]),
            data: [],
        };
        for (let i = 0; i < rowsValue.length; i++) {
            tableData.data[i] = [];
            for (const p in rowsValue[i]) {
                if (Object.prototype.hasOwnProperty.call(rowsValue[i], p)) {
                    tableData.data[i].push(rowsValue[i][p]);
                }
            }
        }
        return tableData;
    }
    _addCssLink(url) {
        const link = document.createElement('link');
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
default_1.values = {
    rows: Array,
    columns: Array,
    options: { string: Option },
};

export { default_1 as default };
