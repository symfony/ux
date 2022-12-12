import { Controller } from '@hotwired/stimulus';
export default class extends Controller {
    static values: {
        topic: StringConstructor;
        hub: StringConstructor;
    };
    es: EventSource | undefined;
    url: string | undefined;
    readonly topicValue: string;
    readonly hubValue: string;
    readonly hasHubValue: boolean;
    readonly hasTopicValue: boolean;
    initialize(): void;
    connect(): void;
    disconnect(): void;
}
