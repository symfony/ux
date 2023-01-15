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
import 'bootstrap-table';
import $ from 'jquery';

export default class extends Controller {
    readonly rowsValue: any;
    readonly columnsValue: any;
    readonly optionsValue: any;

    static values = {
        rows: Array,
        columns: Array,
        options: {},
    };

    readonly bootstrapTable: (options: any) => void;

    connect() {
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('invalid Element');
        }

        this._dispatchEvent('bootstrap-table:pre-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });

        $(this.element).bootstrapTable({
            columns: this.columnsValue,
            data: this.rowsValue,
            ...this.optionsValue,
        });

        $(this).on('page-change.bs.table', (event) => {
            console.log(event)
        })

        this._dispatchEvent('bootstrap-table:post-connect', {
            data: this.rowsValue,
            columns: this.columnsValue,
            options: this.optionsValue,
        });
    }

    _dispatchEvent(name: string, payload: any) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
