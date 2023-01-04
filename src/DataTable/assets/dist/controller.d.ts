import { Controller } from '@hotwired/stimulus';
interface tableData {
    headings: string[];
    data: string[][];
}
export default class extends Controller {
    readonly rowsValue: any;
    readonly columnsValue: any;
    readonly optionsValue: any;
    static values: {
        rows: ArrayConstructor;
        columns: ArrayConstructor;
        options: {
            string: new (text?: string | undefined, value?: string | undefined, defaultSelected?: boolean | undefined, selected?: boolean | undefined) => HTMLOptionElement;
        };
    };
    connect(): void;
    _buildTableData(rowsValue: any): tableData;
    _addCssLink(url: string): void;
    _dispatchEvent(name: string, payload: any): void;
}
export {};
