import { Controller } from '@hotwired/stimulus';
import { SvelteComponent } from 'svelte';

declare class export_default extends Controller<Element & {
    root?: SvelteComponent;
}> {
    private app;
    readonly componentValue: string;
    private props;
    private intro;
    readonly propsValue: Record<string, unknown> | null | undefined;
    readonly introValue: boolean | undefined;
    static values: {
        component: StringConstructor;
        props: ObjectConstructor;
        intro: BooleanConstructor;
    };
    connect(): void;
    disconnect(): void;
    _destroyIfExists(): void;
    private dispatchEvent;
}

export { export_default as default };
