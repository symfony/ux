import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    connect() {
        this.element.innerHTML = '';
        this.props = this.propsValue ?? undefined;
        this.intro = this.introValue ?? undefined;
        this.dispatchEvent('connect');
        const Component = window.resolveSvelteComponent(this.componentValue);
        this._destroyIfExists();
        this.app = new Component({
            target: this.element,
            props: this.props,
            intro: this.intro,
        });
        this.element.root = this.app;
        this.dispatchEvent('mount', {
            component: Component,
        });
    }
    disconnect() {
        this._destroyIfExists();
        this.dispatchEvent('unmount');
    }
    _destroyIfExists() {
        if (this.element.root !== undefined) {
            this.element.root.$destroy();
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
}
default_1.values = {
    component: String,
    props: Object,
    intro: Boolean,
};

export { default_1 as default };
