import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    readonly viewValue: any;
    static values: {
        view: ObjectConstructor;
    };
    private chart;
    initialize(): void;
    viewValueChanged(): void;
    private dispatchEvent;
}
