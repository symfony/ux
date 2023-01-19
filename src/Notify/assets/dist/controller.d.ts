import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values: {
        hub: StringConstructor;
        topics: ArrayConstructor;
    };
    eventSources: Array<EventSource>;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    _notify(content: string | undefined): void;
    private dispatchEvent;
}
