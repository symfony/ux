import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values: {
        hub: StringConstructor;
        topics: ArrayConstructor;
    };
    hubValue: string;
    topicsValue: Array<string>;
    readonly hasHubValue: boolean;
    readonly hasTopicsValue: boolean;
    eventSources: Array<EventSource>;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    _notify(content: string | undefined): void;
    private dispatchEvent;
}
