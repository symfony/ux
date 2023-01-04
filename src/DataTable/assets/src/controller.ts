/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

'use strict';

import { Controller } from '@hotwired/stimulus';
import { DataTable } from 'simple-datatables';

interface tableData {
    headings: string[];
    data: string[][];
}

export default class extends Controller {
    declare readonly rowsValue: any;
    declare readonly columnsValue: any;
    declare readonly optionsValue: any;

    static values = {
        rows: Array,
        columns: Array,
        options: { string: Option },
    };

    connect() {
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('invalid Element, please provide a HTMLTableElement');
        }

        this._dispatchEvent('datatable:pre-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });

        new DataTable(this.element, {
            data: this._buildTableData(this.rowsValue),
            ...this.optionsValue,
        });

        if (this.optionsValue?.withPluginCss) {
            this._addCssLink('https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css');
        }

        if (this.optionsValue?.withCss) {
            this._addCssLink(this.optionsValue.withCss);
        }

        this._dispatchEvent('datatable:post-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });
    }

    _buildTableData(rowsValue: any): tableData {
        const tableData: tableData = {
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

    _addCssLink(url: string) {
        const link = document.createElement('link');
        link.type = 'text/css';
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
