import { Controller } from '@hotwired/stimulus';
import type { SvelteComponent } from 'svelte';
export default class extends Controller<Element & {
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
    connect(): Promise<void>;
    disconnect(): void;
    _destroyIfExists(): void;
    private dispatchEvent;
    private mountSvelteComponent;
}
