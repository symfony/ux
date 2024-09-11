/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import DataTable from 'datatables.net-dt';

let isDataTableInitialized = false;

export default class extends Controller {
    declare readonly viewValue: any;

    static values = {
        view: Object,
    };

    private table: DataTable | null = null;

    connect() {
        if (isDataTableInitialized) {
            return;
        }

        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('Invalid element');
        }

        const payload = this.viewValue;

        this.dispatchEvent('pre-connect', {
            config: payload,
        })

        new DataTable(this.element as HTMLElement, payload);

        this.dispatchEvent('connect', { table: this.table });

        isDataTableInitialized = true;
    }

    private dispatchEvent(name: string, payload: any) {
        this.dispatch(name, { detail: payload, prefix: 'datatables' });
    }
}