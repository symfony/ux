import { Controller } from '@hotwired/stimulus';
import type { SvelteComponent, ComponentConstructorOptions, ComponentType } from 'svelte';

export default class extends Controller<Element & { root?: SvelteComponent }> {
    private app: SvelteComponent;
    declare readonly componentValue: string;

    private props: Record<string, any> | undefined;
    private intro: boolean | undefined;

    declare readonly propsValue: Record<string, unknown> | null | undefined;
    declare readonly introValue: boolean | undefined;

    static values = {
        component: String,
        props: Object,
        intro: Boolean,
    };

    async connect() {
        this.element.innerHTML = '';

        this.props = this.propsValue ?? undefined;
        this.intro = this.introValue ?? undefined;

        this.dispatchEvent('connect');

        const Component = window.resolveSvelteComponent(this.componentValue);

        await this._destroyIfExists();

        this.app = await this.mountSvelteComponent(Component, {
            target: this.element,
            props: this.props,
            intro: this.intro,
        });

        this.element.root = this.app;

        this.dispatchEvent('mount', {
            component: Component,
        });
    }

    async disconnect() {
        await this._destroyIfExists();
        this.dispatchEvent('unmount');
    }

    async _destroyIfExists() {
        if (this.element.root !== undefined) {
            // @ts-ignore
            const { unmount } = await import('svelte');
            if (unmount) {
                // `unmount` is only available in Svelte >= 5
                unmount(this.element.root);
            } else {
                this.element.root.$destroy();
            }
            delete this.element.root;
        }
    }

    private dispatchEvent(name: string, payload: object = {}) {
        const detail = {
            componentName: this.componentValue,
            props: this.props,
            intro: this.intro,
            ...payload,
        };
        this.dispatch(name, { detail, prefix: 'svelte' });
    }

    // @see https://svelte.dev/docs#run-time-client-side-component-api-creating-a-component
    private async mountSvelteComponent(
        Component: ComponentType,
        options: ComponentConstructorOptions
    ): Promise<SvelteComponent> {
        // @ts-ignore
        const { mount } = await import('svelte');
        if (mount) {
            // `mount` is only available in Svelte >= 5
            return mount(Component, options);
        }

        return new Component(options);
    }
}
