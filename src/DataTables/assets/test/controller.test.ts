/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Application } from '@hotwired/stimulus';
import { waitFor } from '@testing-library/dom';
import DataTableController from '../src/controller';

const startDataTableTest = async (
    tableHtml: string
): Promise<{ table: HTMLTableElement; dataTable: DataTable<any> }> => {
    let dataTable: DataTable<any> | null = null;

    document.body.addEventListener('datatables:pre-connect', () => {
        document.body.classList.add('pre-connected');
    });

    document.body.addEventListener('datatables:connect', (event: any) => {
        dataTable = event.detail.table;
        document.body.classList.add('connected');
    });

    document.body.innerHTML = tableHtml;

    const tableElement = document.querySelector('table');
    if (!tableElement) {
        throw 'Missing table element';
    }

    await waitFor(() => {
        expect(document.body).toHaveClass('pre-connected');
        expect(document.body).toHaveClass('connected');
    });

    if (!dataTable) {
        throw 'Missing DataTable instance';
    }

    return { table: tableElement, dataTable };
};

describe('DataTableController', () => {
    beforeAll(() => {
        const application = Application.start();
        application.register('datatables', DataTableController);
    });

    afterEach(() => {
        document.body.innerHTML = '';
    });

    it('connect without data', async () => {
        const { dataTable } = await startDataTableTest(`
            <table
                data-testid="datatable"
                data-controller="datatables"
                data-datatables-view-value="&#x7B;&quot;columns&quot;&#x3A;&#x20;&#x5B;&#x7B;&quot;title&quot;&#x3A;&#x20;&quot;First&#x20;name&quot;&#x7D;&#x2C;&#x7B;&quot;title&quot;&#x3A;&#x20;&quot;Last&#x20;name&quot;&#x7D;&#x5D;&#x7D;"
            ></table>
        `);

        expect(dataTable.data().toArray()).toEqual([]);
    });

    it('connect with data', async () => {
        const { dataTable } = await startDataTableTest(`
            <table
                data-testid="datatable"
                data-controller="datatables"
                data-datatables-view-value="&#x7B;&quot;columns&quot;&#x3A;&#x20;&#x5B;&#x7B;&quot;title&quot;&#x3A;&#x20;&quot;First&#x20;name&quot;&#x7D;&#x2C;&#x7B;&quot;title&quot;&#x3A;&#x20;&quot;Last&#x20;name&quot;&#x7D;&#x5D;&#x2C;&#x20;&quot;data&quot;&#x3A;&#x20;&#x5B;&#x5B;&quot;John&quot;&#x2C;&#x20;&quot;Doe&quot;&#x5D;&#x2C;&#x20;&#x5B;&quot;Jane&quot;&#x2C;&#x20;&quot;Smith&quot;&#x5D;&#x5D;&#x7D;"
            ></table>
        `);

        expect(dataTable.data().toArray()).toEqual([
            ['John', 'Doe'],
            ['Jane', 'Smith'],
        ]);
    });
});
