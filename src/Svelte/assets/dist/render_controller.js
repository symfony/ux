import { Controller } from '@hotwired/stimulus';

class default_1 extends Controller {
    connect() {
        var _a, _b;
        this.element.innerHTML = '';
        this.props = (_a = this.propsValue) !== null && _a !== void 0 ? _a : undefined;
        this.intro = (_b = this.introValue) !== null && _b !== void 0 ? _b : undefined;
        this._dispatchEvent('svelte:connect');
        const Component = window.resolveSvelteComponent(this.componentValue);
        this._destroyIfExists();
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
    _dispatchEvent(name, payload = {}) {
        const detail = Object.assign({ componentName: this.componentValue, props: this.props, intro: this.intro }, payload);
        this.element.dispatchEvent(new CustomEvent(name, { detail, bubbles: true }));
    }
}
default_1.values = {
    component: String,
    props: Object,
    intro: Boolean,
};

export { default_1 as default };
