import { Controller } from '@hotwired/stimulus';
import { createApp } from 'vue';

class default_1 extends Controller {
    connect() {
        var _a;
        this.props = (_a = this.propsValue) !== null && _a !== void 0 ? _a : null;
        this._dispatchEvent('vue:connect', { componentName: this.componentValue, props: this.props });
        const component = window.resolveVueComponent(this.componentValue);
        this.app = createApp(component, this.props);
        this.app.mount(this.element);
        this._dispatchEvent('vue:mount', { componentName: this.componentValue, component: component, props: this.props });
    }
    disconnect() {
        this.app.unmount();
        this._dispatchEvent('vue:unmount', {
            componentName: this.componentValue,
            props: this.props,
        });
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload, bubbles: true }));
    }
}
default_1.values = {
    component: String,
    props: Object,
};

export { default_1 as default };
