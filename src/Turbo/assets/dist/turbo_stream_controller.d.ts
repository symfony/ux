import { Controller } from '@hotwired/stimulus';

declare class export_default extends Controller {
    static values: {
        topic: StringConstructor;
        topics: ArrayConstructor;
        hub: StringConstructor;
        withCredentials: BooleanConstructor;
    };
    es: EventSource | undefined;
    url: string | undefined;
    readonly topicValue: string;
    readonly topicsValue: string[];
    readonly withCredentialsValue: boolean;
    readonly hubValue: string;
    readonly hasHubValue: boolean;
    readonly hasTopicValue: boolean;
    readonly hasTopicsValue: boolean;
    initialize(): void;
    connect(): void;
    disconnect(): void;
}

export { export_default as default };
