import { Controller } from '@hotwired/stimulus';
import { type App } from 'vue';
export default class extends Controller<Element & {
    __vue_app__?: App<Element>;
}> {
    private props;
    private app;
    readonly componentValue: string;
    readonly hasPropsValue: boolean;
    propsValue: Record<string, unknown> | null | undefined;
    static values: {
        component: StringConstructor;
        props: ObjectConstructor;
    };
    propsValueChanged(newProps: typeof this.propsValue, oldProps: typeof this.propsValue): void;
    initialize(): void;
    connect(): void;
    disconnect(): void;
    private dispatchEvent;
    private wrapComponent;
}
