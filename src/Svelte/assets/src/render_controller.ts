'use strict';

import { Controller } from '@hotwired/stimulus';
import { SvelteComponent } from 'svelte';

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

    connect() {
        this.element.innerHTML = '';

        this.props = this.propsValue ?? undefined;
        this.intro = this.introValue ?? undefined;

        this._dispatchEvent('svelte:connect');

        const Component = window.resolveSvelteComponent(this.componentValue);

        this._destroyIfExists();

        // @see https://svelte.dev/docs#run-time-client-side-component-api-creating-a-component
        this.app = new Component({
            target: this.element,
            props: this.props,
            intro: this.intro,
        });

        this.element.root = this.app;

        this._dispatchEvent('svelte:mount', {
            component: Component,
        });
    }

    disconnect() {
        this._destroyIfExists();
        this._dispatchEvent('svelte:unmount');
    }

    _destroyIfExists() {
        if (this.element.root !== undefined) {
            this.element.root.$destroy();
            delete this.element.root;
        }
    }

    _dispatchEvent(name: string, payload: object = {}) {
        const detail = {
            componentName: this.componentValue,
            props: this.props,
            intro: this.intro,
            ...payload,
        };
        this.element.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }));
    }
}
