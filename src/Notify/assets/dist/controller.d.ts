import { Controller } from '@hotwired/stimulus';

declare class export_default extends Controller {
    static values: {
        hub: StringConstructor;
        topics: ArrayConstructor;
    };
    hubValue: string;
    topicsValue: Array<string>;
    readonly hasHubValue: boolean;
    readonly hasTopicsValue: boolean;
    eventSources: Array<EventSource>;
    listeners: WeakMap<EventSource, (event: MessageEvent) => void>;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    _notify(title: string | undefined, options: NotificationOptions | undefined): void;
    private dispatchEvent;
}

export { export_default as default };
