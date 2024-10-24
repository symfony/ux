import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
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
            const { unmount } = await import('svelte');
            if (unmount) {
                unmount(this.element.root);
            }
            else {
                this.element.root.$destroy();
            }
            delete this.element.root;
        }
    }
    dispatchEvent(name, payload = {}) {
        const detail = {
            componentName: this.componentValue,
            props: this.props,
            intro: this.intro,
            ...payload,
        };
        this.dispatch(name, { detail, prefix: 'svelte' });
    }
    async mountSvelteComponent(Component, options) {
        const { mount } = await import('svelte');
        if (mount) {
            return mount(Component, options);
        }
        return new Component(options);
    }
}
default_1.values = {
    component: String,
    props: Object,
    intro: Boolean,
};

export { default_1 as default };
