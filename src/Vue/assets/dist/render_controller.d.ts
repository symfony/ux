import { Controller } from '@hotwired/stimulus';
import { type App } from 'vue';
export default class extends Controller<Element & {
    __vue_app__?: App<Element>;
}> {
    private props;
    private app;
    readonly componentValue: string;
    readonly propsValue: Record<string, unknown> | null | undefined;
    static values: {
        component: StringConstructor;
        props: ObjectConstructor;
    };
    connect(): void;
    disconnect(): void;
    private dispatchEvent;
}
